<?php
require_once __DIR__ . '/../../includes/common.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function jsonResponse($data, int $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonErrorResponse(string $message, int $status = 400) {
    jsonResponse(['success' => false, 'message' => $message], $status);
}

try {
    $action = $_REQUEST['action'] ?? 'list';

    if ($action === 'create') {
        $code = $_POST['code'] ?? '';
    $created_at = $_POST['created_at'] ?? date('Y-m-d H:i:s');
    $note = $_POST['note'] ?? '';
    $products = json_decode($_POST['products'] ?? '[]', true);

    $createdAtDate = substr($created_at, 0, 10);
    if ($createdAtDate < date('Y-m-d')) {
        jsonResponse(['success' => false, 'message' => 'Ngày nhập phải là hôm nay hoặc sau hôm nay.']);
    }

    $stmt = db_prepare("INSERT INTO import_receipts (code, created_at, status, note) VALUES (?, ?, 'draft', ?)");
    $stmt->bind_param('sss', $code, $created_at, $note);
    $stmt->execute();
    $receipt_id = db_insert_id();

    foreach ($products as $prod) {
        $quantity = (int)($prod['quantity'] ?? 0);
        $unit_cost = (float)($prod['unit_cost'] ?? 0);
        if ($quantity <= 0 || $unit_cost < 0) {
            jsonResponse(['success' => false, 'message' => 'Số lượng phải lớn hơn 0 và giá nhập không được âm.']);
        }
        $stmt = db_prepare("INSERT INTO import_items (receipt_id, product_id, quantity, unit_cost) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iiid', $receipt_id, $prod['product_id'], $quantity, $unit_cost);
        $stmt->execute();
    }
    jsonResponse(['success' => true, 'message' => 'Đã tạo phiếu nhập', 'receipt_id' => $receipt_id]);
}

if ($action === 'update') {
    $receipt_id = (int)$_POST['receipt_id'];
    $note = $_POST['note'] ?? '';
    $created_at = $_POST['created_at'] ?? date('Y-m-d H:i:s');
    $products = json_decode($_POST['products'] ?? '[]', true);

    $createdAtDate = substr($created_at, 0, 10);
    if ($createdAtDate < date('Y-m-d')) {
        jsonResponse(['success' => false, 'message' => 'Ngày nhập phải là hôm nay hoặc sau hôm nay.']);
    }

    $receipt = db_fetch_one("SELECT * FROM import_receipts WHERE id = $receipt_id");
    if (!$receipt || $receipt['status'] !== 'draft') {
        jsonResponse(['success' => false, 'message' => 'Phiếu đã hoàn thành, không thể sửa.']);
    }
    $stmt = db_prepare("UPDATE import_receipts SET note = ?, created_at = ? WHERE id = ?");
    $stmt->bind_param('ssi', $note, $created_at, $receipt_id);
    $stmt->execute();

    $deleteStmt = db_prepare("DELETE FROM import_items WHERE receipt_id = ?");
    $deleteStmt->bind_param('i', $receipt_id);
    $deleteStmt->execute();
    foreach ($products as $prod) {
        $quantity = (int)($prod['quantity'] ?? 0);
        $unit_cost = (float)($prod['unit_cost'] ?? 0);
        if ($quantity <= 0 || $unit_cost < 0) {
            jsonResponse(['success' => false, 'message' => 'Số lượng phải lớn hơn 0 và giá nhập không được âm.']);
        }
        $stmt = db_prepare("INSERT INTO import_items (receipt_id, product_id, quantity, unit_cost) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iiid', $receipt_id, $prod['product_id'], $quantity, $unit_cost);
        $stmt->execute();
    }
    jsonResponse(['success' => true, 'message' => 'Đã cập nhật phiếu nhập']);
}

if ($action === 'complete') {
    $receipt_id = (int)$_POST['receipt_id'];
    if ($receipt_id <= 0) {
        jsonResponse(['success' => false, 'message' => 'Mã phiếu nhập không hợp lệ.']);
    }

    $conn = db_connect();
    $conn->begin_transaction();
    try {
        // 1. Kiểm tra và khóa phiếu nhập
        $receiptStmt = db_prepare("SELECT id, status FROM import_receipts WHERE id = ? FOR UPDATE");
        $receiptStmt->bind_param('i', $receipt_id);
        $receiptStmt->execute();
        $receiptResult = $receiptStmt->get_result();
        $receipt = $receiptResult ? $receiptResult->fetch_assoc() : null;

        if (!$receipt) {
            throw new RuntimeException('Không tìm thấy phiếu nhập.');
        }

        if ($receipt['status'] === 'completed') {
            $conn->rollback();
            jsonResponse(['success' => true, 'message' => 'Phiếu nhập đã hoàn thành trước đó.']);
        }

        if ($receipt['status'] !== 'draft') {
            throw new RuntimeException('Trạng thái phiếu nhập không hợp lệ để hoàn thành.');
        }

        // 2. Lấy các dòng nhập
        $itemsStmt = db_prepare("SELECT id, product_id, quantity, unit_cost FROM import_items WHERE receipt_id = ?");
        $itemsStmt->bind_param('i', $receipt_id);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();
        $items = $itemsResult ? $itemsResult->fetch_all(MYSQLI_ASSOC) : [];

        if (!$items) {
            throw new RuntimeException('Phiếu nhập chưa có sản phẩm nào.');
        }

        // 3. Gộp items theo product_id để xử lý đúng khi có nhiều dòng cùng sản phẩm
        $itemsByProduct = [];
        foreach ($items as $item) {
            $pid = (int)$item['product_id'];
            if (!isset($itemsByProduct[$pid])) {
                $itemsByProduct[$pid] = [];
            }
            $itemsByProduct[$pid][] = $item;
        }

        // 4. Lấy metadata của phiếu
        $metaStmt = db_prepare("SELECT code, created_at FROM import_receipts WHERE id = ? LIMIT 1");
        $metaStmt->bind_param('i', $receipt_id);
        $metaStmt->execute();
        $metaResult = $metaStmt->get_result();
        $meta = $metaResult ? $metaResult->fetch_assoc() : null;

        $receiptCode = (string)($meta['code'] ?? '');
        $occurredAt = (string)($meta['created_at'] ?? date('Y-m-d H:i:s'));
        $note = 'Nhập kho từ phiếu nhập hoàn thành';

        // 5. Chuẩn bị prepared statements
        $productStmt = db_prepare("SELECT id, quantity, cost_price, profit_margin FROM products WHERE id = ? FOR UPDATE");
        $updateStmt = db_prepare("UPDATE products SET quantity = ?, cost_price = ?, sale_price = ? WHERE id = ?");
        $logStmt = db_prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, occurred_at, source_type, source_item_id, ref_code, note) VALUES (?, 'import', ?, ?, 'import_item', ?, ?, ?)");

        // 6. Cập nhật từng sản phẩm
        foreach ($itemsByProduct as $productId => $productItems) {
            // Lấy dữ liệu sản phẩm hiện tại (WITH LOCK)
            $productStmt->bind_param('i', $productId);
            $productStmt->execute();
            $productResult = $productStmt->get_result();
            $product = $productResult ? $productResult->fetch_assoc() : null;

            if (!$product) {
                throw new RuntimeException('Sản phẩm ID ' . $productId . ' không tồn tại.');
            }

            $oldQty = (int)$product['quantity'];
            $oldCostPrice = (float)$product['cost_price'];
            $profitMargin = (float)$product['profit_margin'];

            // Tính tổng số lượng và chi phí nhập cho sản phẩm này
            $totalImportQty = 0;
            $totalImportCost = 0;
            foreach ($productItems as $item) {
                $importQty = (int)$item['quantity'];
                $importUnitCost = (float)$item['unit_cost'];
                $totalImportQty += $importQty;
                $totalImportCost += $importQty * $importUnitCost;
            }

            // Tính giá vốn mới theo BÌNH QUÂN
            $newQty = $oldQty + $totalImportQty;
            if ($oldQty > 0) {
                // newCostPrice = (oldQty * oldCostPrice + totalImportCost) / newQty
                $newCostPrice = (($oldQty * $oldCostPrice) + $totalImportCost) / $newQty;
            } else {
                // Nếu không có tồn cũ, giá vốn mới = giá nhập trung bình
                $newCostPrice = $totalImportCost / $totalImportQty;
            }

            // Tính giá bán từ giá vốn mới
            $newSalePrice = round($newCostPrice * (1 + $profitMargin / 100), 2);

            // Cập nhật sản phẩm (SET ... = ? NOT SET ... = ... + ?)
            $updateStmt->bind_param('iddi', $newQty, $newCostPrice, $newSalePrice, $productId);
            $updateStmt->execute();
            if ($updateStmt->affected_rows <= 0) {
                throw new RuntimeException('Không thể cập nhật tồn kho cho sản phẩm ID ' . $productId . '.');
            }

            // Log stock movement cho từng dòng (giữ chi tiết nguồn)
            foreach ($productItems as $item) {
                $sourceItemId = (int)$item['id'];
                $importQty = (int)$item['quantity'];

                $logStmt->bind_param('iisiss', $productId, $importQty, $occurredAt, $sourceItemId, $receiptCode, $note);
                $logStmt->execute();
            }
        }

        // 7. Cập nhật trạng thái phiếu nhập
        $completeStmt = db_prepare("UPDATE import_receipts SET status = 'completed' WHERE id = ?");
        $completeStmt->bind_param('i', $receipt_id);
        $completeStmt->execute();

        $conn->commit();
        jsonResponse(['success' => true, 'message' => 'Đã hoàn thành phiếu nhập và cập nhật tồn kho.']);
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}


if ($action === 'search') {
    $sql = 'SELECT * FROM import_receipts WHERE 1';
    $types = '';
    $params = [];
    if (!empty($_GET['code'])) {
        $sql .= ' AND code = ?';
        $types .= 's';
        $params[] = trim((string)$_GET['code']);
    }
    if (!empty($_GET['created_at'])) {
        $sql .= ' AND DATE(created_at) = ?';
        $types .= 's';
        $params[] = trim((string)$_GET['created_at']);
    }
    $receipts = db_fetch_all_prepared($sql, $types, $params);
    jsonResponse(['success' => true, 'receipts' => $receipts]);
}

// Lấy danh sách phiếu nhập hàng kèm chi tiết sản phẩm
if ($action === 'list_detail') {
    $filters = [];
    $types = '';
    $params = [];
    if (!empty($_GET['status'])) {
        $filters[] = 'ir.status = ?';
        $types .= 's';
        $params[] = trim((string)$_GET['status']);
    }
    if (!empty($_GET['created_at'])) {
        $filters[] = 'DATE(ir.created_at) = ?';
        $types .= 's';
        $params[] = trim((string)$_GET['created_at']);
    }
    if (!empty($_GET['from'])) {
        $filters[] = 'DATE(ir.created_at) >= ?';
        $types .= 's';
        $params[] = trim((string)$_GET['from']);
    }
    if (!empty($_GET['to'])) {
        $filters[] = 'DATE(ir.created_at) <= ?';
        $types .= 's';
        $params[] = trim((string)$_GET['to']);
    }
    if (!empty($_GET['q'])) {
        $q = trim((string)$_GET['q']) . '%';
        $filters[] = '(
            ir.code LIKE ? OR
            ir.supplier LIKE ? OR
            p.name LIKE ? OR
            p.sku LIKE ?
        )';
        $types .= 'ssss';
        $params[] = $q;
        $params[] = $q;
        $params[] = $q;
        $params[] = $q;
    }
    $where = '';
    if ($filters) {
        $where = ' WHERE ' . implode(' AND ', $filters);
    }

    $sql = "SELECT
        ir.id AS receipt_id,
        ir.code AS receipt_code,
        ir.supplier,
        ir.created_at AS receipt_date,
        ir.status,
        ii.id AS item_id,
        ii.product_id,
        p.sku,
        p.name AS product_name,
        ii.quantity,
        ii.unit_cost,
        ii.created_at AS import_time
    FROM import_receipts ir
    JOIN import_items ii ON ir.id = ii.receipt_id
    JOIN products p ON ii.product_id = p.id
    $where
    ORDER BY ir.id DESC, ii.id ASC";
    $rows = db_fetch_all_prepared($sql, $types, $params);
    // Gom nhóm theo receipt_id
    $result = [];
    foreach ($rows as $row) {
        $rid = $row['receipt_id'];
        if (!isset($result[$rid])) {
            $result[$rid] = [
                'receipt_id' => $row['receipt_id'],
                'receipt_code' => $row['receipt_code'],
                'supplier' => $row['supplier'],
                'receipt_date' => $row['receipt_date'],
                'status' => $row['status'],
                'items' => []
            ];
        }
        $result[$rid]['items'][] = [
            'item_id' => $row['item_id'],
            'product_id' => $row['product_id'],
            'sku' => $row['sku'],
            'product_name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'unit_cost' => $row['unit_cost'],
            'import_time' => $row['import_time']
        ];
    }
    // Reset key về dạng mảng tuần tự
    $result = array_values($result);
    jsonResponse(['success' => true, 'receipts' => $result]);
}

    jsonResponse(['success' => false, 'message' => 'Hành động không hợp lệ.'], 400);
} catch (Throwable $e) {
    jsonErrorResponse('Lỗi server: ' . $e->getMessage(), 500);
}