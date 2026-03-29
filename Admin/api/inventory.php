<?php
require_once __DIR__ . '/../../includes/common.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');

function jsonResponse($data, int $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_REQUEST['action'] ?? 'list';

// ============ ACTION: all-data - Tất cả dữ liệu cho trang inventory.php cũ ============
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'all-data') {
    // Sản phẩm
    $products = db_fetch_all("
        SELECT id, sku as code, name, category_id as categoryId, quantity 
        FROM products 
        WHERE status = 'selling'
        ORDER BY name ASC
    ");

    // Danh mục
    $categories = db_fetch_all("
        SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC
    ");

    // Lịch sử giao dịch kho (nguồn chuẩn)
    $transactions = db_fetch_all(" 
        SELECT
            sm.id,
            sm.movement_type AS type,
            sm.product_id AS productId,
            sm.quantity AS qty,
            CASE
                WHEN sm.source_type = 'order_item' AND o.created_at IS NOT NULL THEN o.created_at
                ELSE sm.occurred_at
            END AS createdAt,
            UNIX_TIMESTAMP(
                CASE
                    WHEN sm.source_type = 'order_item' AND o.created_at IS NOT NULL THEN o.created_at
                    ELSE sm.occurred_at
                END
            ) AS occurredTs,
            sm.ref_code AS code
        FROM stock_movements sm
        LEFT JOIN order_items oi
            ON sm.source_type = 'order_item'
           AND oi.id = sm.source_item_id
        LEFT JOIN orders o
            ON o.id = oi.order_id
        ORDER BY createdAt DESC, sm.id DESC
    ");

    jsonResponse([
        'success' => true,
        'products' => array_map(function ($p) {
            return [
                'id' => (int)$p['id'],
                'code' => $p['code'],
                'name' => $p['name'],
                'categoryId' => (int)$p['categoryId'],
                'qty' => (int)$p['quantity'],  // ← Tên là qty để match JS
            ];
        }, $products),
        'categories' => array_map(function ($c) {
            return [
                'id' => (int)$c['id'],
                'name' => $c['name'],
                'status' => 'active',  // ← Thêm status để match JS
            ];
        }, $categories),
        'transactions' => array_map(function ($t) {
            return [
                'id' => (int)$t['id'],
                'type' => $t['type'],
                'productId' => (int)$t['productId'],
                'qty' => (int)$t['qty'],
                'createdAt' => $t['createdAt'],
                'occurredTs' => (int)$t['occurredTs'],
                'date' => $t['createdAt'],
                'code' => $t['code'],
            ];
        }, $transactions),
    ]);
}

// ============ ACTION: products - Danh sách sản phẩm ============
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'products') {
    $sql = "SELECT id, sku, name FROM products WHERE status = 'selling' ORDER BY name ASC";
    $rows = db_fetch_all($sql);
    $products = array_map(function ($r) {
        return [
            'id' => (int)$r['id'],
            'sku' => $r['sku'],
            'name' => $r['name'],
        ];
    }, $rows);

    jsonResponse(['success' => true, 'products' => $products]);
}

// ============ ACTION: at-moment - Tồn kho tại thời điểm cụ thể ============
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'at-moment') {
    $productId = (int)($_GET['product_id'] ?? 0);
    $atMoment = trim($_GET['at_moment'] ?? '');

    if ($productId <= 0 || $atMoment === '') {
        jsonResponse(['success' => false, 'message' => 'Thiếu ID sản phẩm hoặc thời điểm'], 400);
    }

    // Parse datetime - chuẩn hóa format
    // Expected format: "2026-03-30 09:05" hoặc "2026-03-30T09:05"
    $atMoment = str_replace('T', ' ', $atMoment);

    $product = db_fetch_one_prepared('SELECT id, sku, name, quantity as current_qty FROM products WHERE id = ? LIMIT 1', 'i', [$productId]);
    if (!$product) {
        jsonResponse(['success' => false, 'message' => 'Không tìm thấy sản phẩm'], 404);
    }

    $currentQty = (int)$product['current_qty'];

    // Tính nhập/xuất SAU thời điểm đó từ bảng log giao dịch kho
    $movementAfterSql = "
        SELECT
            COALESCE(SUM(CASE WHEN movement_type = 'import' THEN quantity ELSE 0 END), 0) AS total_import,
            COALESCE(SUM(CASE WHEN movement_type = 'export' THEN quantity ELSE 0 END), 0) AS total_export,
            COALESCE(SUM(CASE WHEN movement_type = 'adjust' AND quantity > 0 THEN quantity ELSE 0 END), 0) AS total_adjust_in,
            COALESCE(SUM(CASE WHEN movement_type = 'adjust' AND quantity < 0 THEN ABS(quantity) ELSE 0 END), 0) AS total_adjust_out
        FROM stock_movements
        WHERE product_id = ?
          AND occurred_at > ?
    ";
    $movementAfter = db_fetch_one_prepared($movementAfterSql, 'is', [$productId, $atMoment]);
    $importQty = (int)($movementAfter['total_import'] ?? 0) + (int)($movementAfter['total_adjust_in'] ?? 0);
    $exportQty = (int)($movementAfter['total_export'] ?? 0) + (int)($movementAfter['total_adjust_out'] ?? 0);

    // Công thức chuẩn: Tồn tại thời điểm X = Tồn hiện tại - Nhập sau X + Xuất sau X
    $stockAtMoment = $currentQty - $importQty + $exportQty;

    jsonResponse([
        'success' => true,
        'product' => [
            'id' => (int)$product['id'],
            'sku' => $product['sku'],
            'name' => $product['name'],
        ],
        'at_moment' => $atMoment,
        'current_qty' => $currentQty,
        'exported_after' => $exportQty,
        'imported_after' => $importQty,
        'stock_at_moment' => $stockAtMoment,
        'debug' => [
            'movement_sql' => $movementAfterSql,
        ],
    ]);
}

// ============ ACTION: list - Danh sách sản phẩm sắp hết hàng ============
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $threshold = (int)($_GET['threshold'] ?? 5);
    $search = trim($_GET['q'] ?? '');

    $filters = ['p.quantity <= ?'];
    $types = 'i';
    $params = [$threshold];
    
    if ($search !== '') {
        $like = '%' . $search . '%';
        $filters[] = '(p.sku LIKE ? OR p.name LIKE ?)';
        $types .= 'ss';
        $params[] = $like;
        $params[] = $like;
    }

    $whereSql = implode(' AND ', $filters);

    $sql = "
        SELECT 
            p.id,
            p.sku,
            p.name,
            p.quantity,
            p.cost_price,
            p.sale_price,
            p.supplier,
            c.name as category_name,
            p.created_at
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE $whereSql
        ORDER BY p.quantity ASC, p.name ASC
    ";

    $rows = db_fetch_all_prepared($sql, $types, $params);
    $products = array_map(function ($r) {
        return [
            'id' => (int)$r['id'],
            'sku' => $r['sku'],
            'name' => $r['name'],
            'quantity' => (int)$r['quantity'],
            'cost_price' => (float)$r['cost_price'],
            'sale_price' => (float)$r['sale_price'],
            'supplier' => $r['supplier'],
            'category' => $r['category_name'] ?? '',
        ];
    }, $rows);

    jsonResponse(['success' => true, 'products' => $products]);
}

// ============ ACTION: history - Báo cáo nhập/xuất theo khoảng thời gian ============
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'history') {
    $productId = (int)($_GET['product_id'] ?? 0);
    $from = trim($_GET['from'] ?? '');
    $to = trim($_GET['to'] ?? '');

    if ($productId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Thiếu ID sản phẩm'], 400);
    }

    // Lấy thông tin sản phẩm
    $product = db_fetch_one_prepared('SELECT id, sku, name, quantity FROM products WHERE id = ? LIMIT 1', 'i', [$productId]);
    if (!$product) {
        jsonResponse(['success' => false, 'message' => 'Không tìm thấy sản phẩm'], 404);
    }

    $dateFilters = [];
    $types = 'i';
    $params = [$productId];
    if ($from !== '') {
        $dateFilters[] = 'DATE(occurred_at) >= ?';
        $types .= 's';
        $params[] = $from;
    }
    if ($to !== '') {
        $dateFilters[] = 'DATE(occurred_at) <= ?';
        $types .= 's';
        $params[] = $to;
    }

    $dateWhere = !empty($dateFilters) ? ' AND ' . implode(' AND ', $dateFilters) : '';

    $movementsSql = "
        SELECT
            movement_type,
            quantity,
            ref_code,
            occurred_at,
            note
        FROM stock_movements
        WHERE product_id = ?$dateWhere
        ORDER BY occurred_at DESC, id DESC
    ";
    $movements = db_fetch_all_prepared($movementsSql, $types, $params);

    $imports = [];
    $exports = [];
    foreach ($movements as $mv) {
        $mvType = strtolower((string)($mv['movement_type'] ?? ''));
        $mvQty = (int)($mv['quantity'] ?? 0);
        $item = [
            'quantity' => abs($mvQty),
            'ref_code' => $mv['ref_code'] ?? '',
            'created_at' => $mv['occurred_at'] ?? '',
            'note' => $mv['note'] ?? '',
        ];

        if ($mvType === 'import' || ($mvType === 'adjust' && $mvQty > 0)) {
            $imports[] = $item;
        } elseif ($mvType === 'export' || ($mvType === 'adjust' && $mvQty < 0)) {
            $exports[] = $item;
        }
    }

    // Tính tổng nhập/xuất
    $totalImport = 0;
    $totalExport = 0;

    foreach ($imports as $i) {
        $totalImport += (int)$i['quantity'];
    }

    foreach ($exports as $e) {
        $totalExport += (int)$e['quantity'];
    }

    $data = [
        'product' => [
            'id' => (int)$product['id'],
            'sku' => $product['sku'],
            'name' => $product['name'],
            'current_quantity' => (int)$product['quantity'],
        ],
        'imports' => array_map(function ($i) {
            return [
                'quantity' => (int)$i['quantity'],
                'ref_code' => $i['ref_code'],
                'created_at' => $i['created_at'],
                'supplier' => $i['supplier'] ?? '',
            ];
        }, $imports),
        'exports' => array_map(function ($e) {
            return [
                'quantity' => (int)$e['quantity'],
                'ref_code' => $e['ref_code'],
                'created_at' => $e['created_at'],
                'customer' => $e['customer'] ?? '',
            ];
        }, $exports),
        'summary' => [
            'total_import' => $totalImport,
            'total_export' => $totalExport,
            'net' => $totalImport - $totalExport,
        ],
    ];

    jsonResponse(['success' => true, 'data' => $data]);
}

// ============ ACTION: at-risk - Danh sách sản phẩm cảnh báo theo ngưỡng ============
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'at-risk') {
    $threshold = (int)($_GET['threshold'] ?? 5);

    $sql = "
        SELECT 
            p.id,
            p.sku,
            p.name,
            p.quantity,
            p.sale_price,
            c.name as category_name
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.quantity <= $threshold AND p.status = 'selling'
        ORDER BY p.quantity ASC, p.name ASC
    ";

    $rows = db_fetch_all($sql);
    $products = array_map(function ($r) {
        return [
            'id' => (int)$r['id'],
            'sku' => $r['sku'],
            'name' => $r['name'],
            'quantity' => (int)$r['quantity'],
            'sale_price' => (float)$r['sale_price'],
            'category' => $r['category_name'] ?? '',
            'risk_level' => (int)$r['quantity'] === 0 ? 'critical' : 'warning',
        ];
    }, $rows);

    jsonResponse(['success' => true, 'products' => $products, 'threshold' => $threshold]);
}

jsonResponse(['success' => false, 'message' => 'Hành động không hợp lệ'], 400);
