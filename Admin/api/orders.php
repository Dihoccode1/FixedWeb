<?php
require_once __DIR__ . '/../../includes/common.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');

function jsonResponse($data, int $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function mapDbStatusToUi(string $status): string {
    $s = strtolower(trim($status));
    if ($s === 'shipped') return 'delivered';
    if ($s === 'cancelled') return 'canceled';
    return $s;
}

function mapUiStatusToDb(string $status): string {
    $s = strtolower(trim($status));
    if ($s === 'delivered') return 'shipped';
    if ($s === 'canceled') return 'cancelled';
    return $s;
}

function statusText(string $status): string {
    $s = mapDbStatusToUi($status);
    if ($s === 'new') return 'Chưa xử lý';
    if ($s === 'confirmed') return 'Đã xác nhận';
    if ($s === 'delivered') return 'Đã giao thành công';
    if ($s === 'canceled') return 'Đã huỷ';
    return $status;
}

function canTransition(string $currentDbStatus, string $nextDbStatus): bool {
    $flow = [
        'new' => ['confirmed', 'cancelled'],
        'confirmed' => ['shipped', 'cancelled'],
        'shipped' => [],
        'cancelled' => [],
    ];

    $cur = strtolower(trim($currentDbStatus));
    $next = strtolower(trim($nextDbStatus));
    if ($cur === $next) return true;
    return in_array($next, $flow[$cur] ?? [], true);
}

function buildWardExpr(): string {
    return "TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(o.shipping_address, ',', 2), ',', -1))";
}

function commitStockOnDelivered(int $orderId): ?string {
    $conn = db_connect();
    $conn->begin_transaction();

    try {
        $orderMetaStmt = db_prepare('SELECT order_number, created_at FROM orders WHERE id = ? LIMIT 1');
        $orderMetaStmt->bind_param('i', $orderId);
        $orderMetaStmt->execute();
        $orderMetaResult = $orderMetaStmt->get_result();
        $orderMeta = $orderMetaResult ? $orderMetaResult->fetch_assoc() : null;
        if (!$orderMeta) {
            throw new RuntimeException('Khong tim thay don hang.');
        }

        $items = db_fetch_all("SELECT oi.id AS order_item_id, oi.product_id, oi.quantity, p.name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = " . (int)$orderId . " ORDER BY oi.id ASC");

        if (empty($items)) {
            throw new RuntimeException('Don hang khong co san pham de xuat kho.');
        }

        $checkStmt = db_prepare('SELECT quantity FROM products WHERE id = ? LIMIT 1 FOR UPDATE');
        $deductStmt = db_prepare('UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?');
        $movementStmt = db_prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, occurred_at, source_type, source_item_id, ref_code, note) VALUES (?, 'export', ?, CURRENT_TIMESTAMP, 'order_item', ?, ?, ?) ON DUPLICATE KEY UPDATE product_id = VALUES(product_id), quantity = VALUES(quantity), occurred_at = VALUES(occurred_at), ref_code = VALUES(ref_code), note = VALUES(note)");
        $orderNumber = (string)($orderMeta['order_number'] ?? '');
        $note = 'Xuat kho khi don hang da giao';

        foreach ($items as $it) {
            $productId = (int)$it['product_id'];
            $qty = (int)$it['quantity'];
            $orderItemId = (int)($it['order_item_id'] ?? 0);

            $checkStmt->bind_param('i', $productId);
            $checkStmt->execute();
            $stockResult = $checkStmt->get_result();
            $stockRow = $stockResult ? $stockResult->fetch_assoc() : null;
            $currentQty = (int)($stockRow['quantity'] ?? 0);

            if ($currentQty < $qty) {
                throw new RuntimeException('Khong du ton kho cho san pham: ' . ($it['name'] ?? ('#' . $productId)));
            }

            $deductStmt->bind_param('iii', $qty, $productId, $qty);
            $deductStmt->execute();
            if ($deductStmt->affected_rows <= 0) {
                throw new RuntimeException('Xuat kho that bai cho san pham: ' . ($it['name'] ?? ('#' . $productId)));
            }

            if ($orderItemId <= 0) {
                throw new RuntimeException('Du lieu chi tiet don hang khong hop le.');
            }
            $movementStmt->bind_param('iiiss', $productId, $qty, $orderItemId, $orderNumber, $note);
            $movementStmt->execute();
        }

        $conn->commit();
        return null;
    } catch (Throwable $e) {
        $conn->rollback();
        return $e->getMessage();
    }
}

$action = $_REQUEST['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $q = trim($_GET['q'] ?? '');
    $from = trim($_GET['from'] ?? '');
    $to = trim($_GET['to'] ?? '');
    $statusUi = trim($_GET['status'] ?? '');
    $sortWard = strtolower(trim($_GET['sort_ward'] ?? ''));

    $filters = ['1=1'];
    $types = '';
    $params = [];

    if ($q !== '') {
        $like = '%' . $q . '%';
        $filters[] = '(o.order_number LIKE ? OR o.full_name LIKE ? OR o.phone LIKE ? OR o.shipping_address LIKE ?)';
        $types .= 'ssss';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if ($from !== '') {
        $filters[] = 'DATE(o.created_at) >= ?';
        $types .= 's';
        $params[] = $from;
    }

    if ($to !== '') {
        $filters[] = 'DATE(o.created_at) <= ?';
        $types .= 's';
        $params[] = $to;
    }

    if ($statusUi !== '') {
        $statusDb = mapUiStatusToDb($statusUi);
        if (!in_array($statusDb, ['new', 'confirmed', 'shipped', 'cancelled'], true)) {
            jsonResponse(['success' => false, 'message' => 'Trạng thái lọc không hợp lệ.'], 400);
        }
        $filters[] = 'o.status = ?';
        $types .= 's';
        $params[] = $statusDb;
    }

    $whereSql = implode(' AND ', $filters);
    $wardExpr = buildWardExpr();

    $orderBySql = 'o.created_at DESC';
    if ($sortWard === 'asc') {
        $orderBySql = "ward_name ASC, o.created_at DESC";
    } elseif ($sortWard === 'desc') {
        $orderBySql = "ward_name DESC, o.created_at DESC";
    }

    $sql = "
        SELECT
            o.id,
            o.order_number,
            o.created_at,
            o.full_name,
            o.phone,
            o.shipping_address,
            o.total_amount,
            o.status,
            $wardExpr AS ward_name,
            COALESCE(SUM(oi.quantity), 0) AS total_qty
        FROM orders o
        LEFT JOIN order_items oi ON oi.order_id = o.id
        WHERE $whereSql
        GROUP BY o.id
        ORDER BY $orderBySql
    ";

    $rows = db_fetch_all_prepared($sql, $types, $params);
    $orders = array_map(function ($r) {
        return [
            'id' => (int)$r['id'],
            'code' => $r['order_number'],
            'created_at' => $r['created_at'],
            'full_name' => $r['full_name'],
            'phone' => $r['phone'],
            'shipping_address' => $r['shipping_address'],
            'ward_name' => $r['ward_name'] ?? '',
            'total_amount' => (float)$r['total_amount'],
            'total_qty' => (int)$r['total_qty'],
            'status' => mapDbStatusToUi($r['status']),
            'status_text' => statusText($r['status']),
        ];
    }, $rows);

    jsonResponse(['success' => true, 'orders' => $orders]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'detail') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'Thiếu ID đơn hàng.'], 400);
    }

    $wardExpr = buildWardExpr();
    $order = db_fetch_one_prepared("SELECT o.*, $wardExpr AS ward_name FROM orders o WHERE o.id = ? LIMIT 1", 'i', [$id]);
    if (!$order) {
        jsonResponse(['success' => false, 'message' => 'Không tìm thấy đơn hàng.'], 404);
    }

    $items = db_fetch_all_prepared('SELECT oi.*, p.sku, p.name FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ? ORDER BY oi.id ASC', 'i', [$id]);

    $mapped = [
        'id' => (int)$order['id'],
        'code' => $order['order_number'],
        'created_at' => $order['created_at'],
        'status' => mapDbStatusToUi($order['status']),
        'status_text' => statusText($order['status']),
        'full_name' => $order['full_name'],
        'phone' => $order['phone'],
        'email' => $order['email'],
        'shipping_address' => $order['shipping_address'],
        'ward_name' => $order['ward_name'] ?? '',
        'payment_method' => $order['payment_method'],
        'total_amount' => (float)$order['total_amount'],
        'note' => '',
        'items' => array_map(function ($it) {
            return [
                'sku' => $it['sku'] ?? '',
                'name' => $it['name'] ?? '',
                'unit_price' => (float)$it['unit_price'],
                'quantity' => (int)$it['quantity'],
                'line_total' => (float)$it['total_price'],
            ];
        }, $items),
    ];

    jsonResponse(['success' => true, 'order' => $mapped]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_status') {
    $id = (int)($_POST['id'] ?? 0);
    $statusUi = trim($_POST['status'] ?? '');

    if ($id <= 0 || $statusUi === '') {
        jsonResponse(['success' => false, 'message' => 'Thiếu dữ liệu cập nhật trạng thái.'], 400);
    }

    $nextDb = mapUiStatusToDb($statusUi);
    if (!in_array($nextDb, ['new', 'confirmed', 'shipped', 'cancelled'], true)) {
        jsonResponse(['success' => false, 'message' => 'Trạng thái không hợp lệ.'], 400);
    }

    $order = db_fetch_one_prepared('SELECT id, status FROM orders WHERE id = ? LIMIT 1', 'i', [$id]);
    if (!$order) {
        jsonResponse(['success' => false, 'message' => 'Không tìm thấy đơn hàng.'], 404);
    }

    $currentDb = strtolower(trim($order['status']));
    if (!canTransition($currentDb, $nextDb)) {
        jsonResponse([
            'success' => false,
            'message' => 'Không thể quay lui trạng thái hoặc chuyển sai luồng.',
        ], 400);
    }

    if ($currentDb !== $nextDb) {
        if ($nextDb === 'shipped') {
            $stockError = commitStockOnDelivered($id);
            if ($stockError !== null) {
                jsonResponse([
                    'success' => false,
                    'message' => $stockError,
                ], 400);
            }
        }

        $stmt = db_prepare('UPDATE orders SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $nextDb, $id);
        $stmt->execute();
    }

    jsonResponse([
        'success' => true,
        'message' => 'Đã cập nhật trạng thái đơn hàng.',
        'status' => mapDbStatusToUi($nextDb),
        'status_text' => statusText($nextDb),
    ]);
}

jsonResponse(['success' => false, 'message' => 'Hành động không hợp lệ.'], 400);
