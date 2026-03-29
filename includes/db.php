<?php
require_once __DIR__ . '/config.php';

function db_connect() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_errno) {
            die('Kết nối CSDL thất bại: ' . $conn->connect_error);
        }
        $conn->set_charset(DB_CHARSET);
        db_initialize_schema_if_missing($conn);
        db_ensure_column_exists($conn, 'categories', 'description', "description TEXT NOT NULL DEFAULT ''");
        db_ensure_column_exists($conn, 'users', 'street_address', "street_address VARCHAR(255) NOT NULL DEFAULT ''");
        db_ensure_column_exists($conn, 'users', 'ward', "ward VARCHAR(120) NOT NULL DEFAULT ''");
        db_ensure_column_exists($conn, 'users', 'district', "district VARCHAR(120) NOT NULL DEFAULT ''");
        db_ensure_column_exists($conn, 'users', 'city_province', "city_province VARCHAR(120) NOT NULL DEFAULT ''");
        db_ensure_shipping_profiles_schema($conn);
        db_ensure_column_exists($conn, 'user_shipping_profiles', 'slot_number', "slot_number TINYINT UNSIGNED NOT NULL DEFAULT 1");
        db_ensure_column_exists($conn, 'user_shipping_profiles', 'email', "email VARCHAR(191) NOT NULL DEFAULT ''");
        db_ensure_column_exists($conn, 'user_shipping_profiles', 'note', "note VARCHAR(255) NOT NULL DEFAULT ''");
        db_ensure_stock_movement_schema($conn);
        db_backfill_stock_movements($conn);
    }
    return $conn;
}

function db_ensure_shipping_profiles_schema($conn) {
    if (!db_table_exists($conn, 'user_shipping_profiles')) {
        $sql = "
            CREATE TABLE `user_shipping_profiles` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT UNSIGNED NOT NULL,
                `slot_number` TINYINT UNSIGNED NOT NULL DEFAULT 1,
                `full_name` VARCHAR(191) NOT NULL,
                `phone` VARCHAR(20) NOT NULL,
                `email` VARCHAR(191) NOT NULL DEFAULT '',
                `shipping_address` TEXT NOT NULL,
                `note` VARCHAR(255) NOT NULL DEFAULT '',
                `is_default` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY `idx_shipping_profiles_user` (`user_id`),
                CONSTRAINT `fk_shipping_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";
        if (!$conn->query($sql)) {
            die('Lỗi tạo bảng user_shipping_profiles: ' . $conn->error);
        }
    }
}

function db_ensure_stock_movement_schema($conn) {
    if (!db_table_exists($conn, 'stock_movements')) {
        $sql = "
            CREATE TABLE `stock_movements` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `product_id` INT UNSIGNED NOT NULL,
                `movement_type` ENUM('import','export','adjust') NOT NULL,
                `quantity` INT NOT NULL,
                `occurred_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `source_type` VARCHAR(32) NOT NULL DEFAULT '',
                `source_item_id` INT UNSIGNED NULL,
                `ref_code` VARCHAR(80) NOT NULL DEFAULT '',
                `note` VARCHAR(255) NOT NULL DEFAULT '',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_stock_source` (`source_type`, `source_item_id`),
                KEY `idx_stock_product_time` (`product_id`, `occurred_at`),
                KEY `idx_stock_type_time` (`movement_type`, `occurred_at`),
                CONSTRAINT `fk_stock_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";
        if (!$conn->query($sql)) {
            die('Lỗi tạo bảng stock_movements: ' . $conn->error);
        }
    }
}

function db_backfill_stock_movements($conn) {
    if (!db_table_exists($conn, 'stock_movements')) {
        return;
    }

    $sqlImportBackfill = "
        INSERT INTO stock_movements (product_id, movement_type, quantity, occurred_at, source_type, source_item_id, ref_code, note)
        SELECT
            ii.product_id,
            'import' AS movement_type,
            ii.quantity,
            ir.created_at,
            'import_item' AS source_type,
            ii.id AS source_item_id,
            ir.code,
            'Backfill từ phiếu nhập hoàn thành'
        FROM import_items ii
        INNER JOIN import_receipts ir ON ir.id = ii.receipt_id
        LEFT JOIN stock_movements sm
            ON sm.source_type = 'import_item'
           AND sm.source_item_id = ii.id
        WHERE ir.status = 'completed'
          AND sm.id IS NULL
    ";
    $conn->query($sqlImportBackfill);

    $sqlExportBackfill = "
        INSERT INTO stock_movements (product_id, movement_type, quantity, occurred_at, source_type, source_item_id, ref_code, note)
        SELECT
            oi.product_id,
            'export' AS movement_type,
            oi.quantity,
            o.created_at,
            'order_item' AS source_type,
            oi.id AS source_item_id,
            o.order_number,
            'Backfill từ đơn hàng đã giao'
        FROM order_items oi
        INNER JOIN orders o ON o.id = oi.order_id
        LEFT JOIN stock_movements sm
            ON sm.source_type = 'order_item'
           AND sm.source_item_id = oi.id
        WHERE o.status = 'shipped'
          AND sm.id IS NULL
    ";
    $conn->query($sqlExportBackfill);
}

function db_table_exists($conn, $tableName) {
    $tableNameEsc = $conn->real_escape_string($tableName);
    $result = $conn->query("SHOW TABLES LIKE '$tableNameEsc'");
    return $result && $result->num_rows > 0;
}

function db_ensure_column_exists($conn, $tableName, $columnName, $definition) {
    $tableNameEsc = $conn->real_escape_string($tableName);
    $columnNameEsc = $conn->real_escape_string($columnName);
    $result = $conn->query("SHOW COLUMNS FROM `$tableNameEsc` LIKE '$columnNameEsc'");
    if ($result && $result->num_rows === 0) {
        $conn->query("ALTER TABLE `$tableNameEsc` ADD COLUMN $definition");
    }
}

function db_initialize_schema_if_missing($conn) {
    if (db_table_exists($conn, 'orders') && db_table_exists($conn, 'order_items')) {
        return;
    }
    $schemaFile = __DIR__ . '/../db.sql';
    if (!is_readable($schemaFile)) {
        die('Không thể đọc file schema CSDL: ' . $schemaFile);
    }
    $schemaSql = file_get_contents($schemaFile);
    if ($schemaSql === false) {
        die('Không thể đọc nội dung schema CSDL.');
    }
    if (!$conn->multi_query($schemaSql)) {
        die('Lỗi khởi tạo schema CSDL: ' . $conn->error);
    }
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
}

function db_fetch_all($sql) {
    $conn = db_connect();
    $result = $conn->query($sql);
    if (!$result) {
        die('Lỗi truy vấn CSDL: ' . $conn->error);
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

function db_fetch_one($sql) {
    $conn = db_connect();
    $result = $conn->query($sql);
    if (!$result) {
        die('Lỗi truy vấn CSDL: ' . $conn->error);
    }
    return $result->fetch_assoc();
}

function db_prepare($sql) {
    $conn = db_connect();
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die('Lỗi prepare CSDL: ' . $conn->error);
    }
    return $stmt;
}

function db_escape($value) {
    return db_connect()->real_escape_string($value);
}

function db_fetch_all_prepared($sql, $types = '', $params = []) {
    $stmt = db_prepare($sql);
    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function db_fetch_one_prepared($sql, $types = '', $params = []) {
    $rows = db_fetch_all_prepared($sql, $types, $params);
    return $rows[0] ?? null;
}

function db_insert_id() {
    return db_connect()->insert_id;
}
