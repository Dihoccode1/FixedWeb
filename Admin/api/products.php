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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        $rows = db_fetch_all("SELECT * FROM products ORDER BY created_at ASC");
        jsonResponse(['success' => true, 'products' => $rows]);
    }
    if ($action === 'get') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $row = db_fetch_one("SELECT * FROM products WHERE id = $id LIMIT 1");
        if (!$row) jsonResponse(['success' => false, 'message' => 'Không tìm thấy sản phẩm.'], 404);
        jsonResponse(['success' => true, 'product' => $row]);
    }
    jsonResponse(['success' => false, 'message' => 'Phương thức GET không hợp lệ cho hành động này.'], 400);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $_POST;
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $sku = trim($input['code'] ?? '');
    $name = trim($input['name'] ?? '');
    $category_id = (int)($input['category_id'] ?? 0);
    $description = trim($input['description'] ?? '');
    $unit = trim($input['unit'] ?? '');
    $hasQuantity = array_key_exists('quantity', $input);
    $hasCostPrice = array_key_exists('cost_price', $input);
    $hasSalePrice = array_key_exists('sale_price', $input);
    $quantity = (int)($input['quantity'] ?? 0);
    $cost_price = isset($input['cost_price']) ? (float)$input['cost_price'] : 0;
    $profit_margin = isset($input['profit_margin']) ? (float)$input['profit_margin'] : 0;
    $sale_price = isset($input['sale_price']) ? (float)$input['sale_price'] : 0;
    $supplier = trim($input['supplier'] ?? '');
    $status = trim($input['status'] ?? 'selling');
    $image = $_FILES['image'] ?? null;

    if ($action === 'save') {
        if (!$name || !$sku) {
            jsonResponse(['success' => false, 'message' => 'Thiếu thông tin bắt buộc.'], 400);
        }
        // Xử lý upload ảnh nếu có
        $imagePath = '';
        if ($image && $image['tmp_name']) {
            // Validate upload directory
            $uploadDir = __DIR__ . '/../../assets/images/product/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            if (!is_writable($uploadDir)) {
                jsonResponse(['success' => false, 'message' => 'Thư mục upload không có quyền ghi. Vui lòng liên hệ quản trị viên.'], 500);
            }
            
            // Validate file
            $ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($ext, $allowed)) {
                jsonResponse(['success' => false, 'message' => 'Định dạng ảnh không hỗ trợ. Chỉ cho phép: jpg, png, gif, webp'], 400);
            }
            if ($image['size'] > $maxSize) {
                jsonResponse(['success' => false, 'message' => 'Kích thước ảnh không được vượt quá 5MB'], 400);
            }
            if ($image['error'] !== UPLOAD_ERR_OK) {
                jsonResponse(['success' => false, 'message' => 'Lỗi upload: ' . $image['error']], 400);
            }
            
            $imagePath = 'assets/images/product/' . uniqid('prod_') . '.' . $ext;
            if (!move_uploaded_file($image['tmp_name'], $uploadDir . basename($imagePath))) {
                jsonResponse(['success' => false, 'message' => 'Không thể lưu ảnh.'], 500);
            }
        }
        if ($id) {
            // Sửa sản phẩm
            $product = db_fetch_one("SELECT * FROM products WHERE id = $id LIMIT 1");
            if (!$product) jsonResponse(['success' => false, 'message' => 'Không tìm thấy sản phẩm.'], 404);

            if (!$hasQuantity) {
                $quantity = (int)($product['quantity'] ?? 0);
            }
            if (!$hasCostPrice) {
                $cost_price = (float)($product['cost_price'] ?? 0);
            }
            if (!$hasSalePrice) {
                $sale_price = (float)($product['sale_price'] ?? 0);
            }

            $sql = "UPDATE products SET sku=?, name=?, category_id=?, description=?, unit=?, quantity=?, cost_price=?, profit_margin=?, sale_price=?, supplier=?, status=?";
            $params = [$sku, $name, $category_id, $description, $unit, $quantity, $cost_price, $profit_margin, $sale_price, $supplier, $status];
            if ($imagePath !== '') {
                $sql .= ", image=?";
                $params[] = $imagePath;
            }
            $sql .= " WHERE id=?";
            $params[] = $id;
            $stmt = db_prepare($sql);
            $types = 'ssissidddssi';
            if ($imagePath !== '') {
                $types = 'ssissidddsssi';
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            jsonResponse(['success' => true, 'message' => 'Đã cập nhật sản phẩm.']);
        } else {
            // Thêm sản phẩm mới
            $quantity = 0;
            $cost_price = 0;
            $sale_price = 0;
            $sql = "INSERT INTO products (sku, name, category_id, description, unit, quantity, cost_price, profit_margin, sale_price, supplier, status, image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = db_prepare($sql);
            $stmt->bind_param('ssissidddsss', $sku, $name, $category_id, $description, $unit, $quantity, $cost_price, $profit_margin, $sale_price, $supplier, $status, $imagePath);
            $stmt->execute();
            jsonResponse(['success' => true, 'message' => 'Đã thêm sản phẩm mới.']);
        }
    }
    if ($action === 'delete') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if (!$id) jsonResponse(['success' => false, 'message' => 'ID không hợp lệ.'], 400);
        $product = db_fetch_one("SELECT * FROM products WHERE id = $id LIMIT 1");
        if (!$product) jsonResponse(['success' => false, 'message' => 'Không tìm thấy sản phẩm.'], 404);
        $imported = db_fetch_one("SELECT 1 FROM import_items WHERE product_id = $id LIMIT 1");
        if ($imported) {
            // Đánh dấu ẩn
            $stmt = db_prepare('UPDATE products SET status = ? WHERE id = ?');
            $hidden = 'hidden';
            $stmt->bind_param('si', $hidden, $id);
            $stmt->execute();
            jsonResponse(['success' => true, 'message' => 'Sản phẩm đã được ẩn.', 'status' => 'hidden']);
        } else {
            // Xoá hẳn
            $stmt = db_prepare('DELETE FROM products WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            jsonResponse(['success' => true, 'message' => 'Đã xoá sản phẩm.', 'status' => 'deleted']);
        }
    }
}

jsonResponse(['success' => false, 'message' => 'Phương thức không được hỗ trợ.'], 405);
