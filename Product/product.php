<?php
require_once __DIR__ . '/../includes/common.php';

$categorySlug = $_GET['category'] ?? 'all';
$q = trim($_GET['q'] ?? $_GET['query'] ?? '');
$priceMin = isset($_GET['priceMin']) && is_numeric($_GET['priceMin']) && (float)$_GET['priceMin'] > 0 ? (float)$_GET['priceMin'] : '';
$priceMax = isset($_GET['priceMax']) && is_numeric($_GET['priceMax']) && (float)$_GET['priceMax'] > 0 ? (float)$_GET['priceMax'] : '';
$sort = $_GET['sort'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 8;
$where = ['p.status = "selling"'];
$types = '';
$params = [];

if ($q !== '') {
    $like = $q . '%';
    $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)';
    $types .= 'sss';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($categorySlug !== 'all') {
    $where[] = 'c.slug = ?';
    $types .= 's';
    $params[] = $categorySlug;
}
$where[] = 'c.status = "active"';
if ($priceMin > 0) {
    $where[] = "p.sale_price >= $priceMin";
}
if ($priceMax > 0) {
    $where[] = "p.sale_price <= $priceMax";
}

$orderBy = 'p.created_at DESC';
switch ($sort) {
    case 'price-asc':
        $orderBy = 'p.sale_price ASC';
        break;
    case 'price-desc':
        $orderBy = 'p.sale_price DESC';
        break;
    case 'name-asc':
        $orderBy = 'p.name ASC';
        break;
    case 'name-desc':
        $orderBy = 'p.name DESC';
        break;
}

$whereSql = implode(' AND ', $where);
$countRow = db_fetch_one_prepared("SELECT COUNT(*) AS total FROM products p JOIN categories c ON p.category_id = c.id WHERE $whereSql", $types, $params);
$total = $countRow ? (int)$countRow['total'] : 0;
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) {
    $page = $pages;
}
$offset = ($page - 1) * $perPage;
$productsSql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p JOIN categories c ON p.category_id = c.id WHERE $whereSql ORDER BY $orderBy LIMIT ?, ?";
$products = db_fetch_all_prepared($productsSql, $types . 'ii', array_merge($params, [$offset, $perPage]));
$categories = db_fetch_all('SELECT * FROM categories WHERE status = "active" ORDER BY name');
$user = current_user();

function buildQuery(array $fields) {
    $params = [];
    foreach ($fields as $key) {
        if (isset($_GET[$key]) && $_GET[$key] !== '') {
            $params[] = urlencode($key) . '=' . urlencode($_GET[$key]);
        }
    }
    return $params ? '&' . implode('&', $params) : '';
}

function getStock(array $product) {
    return (int)($product['stock'] ?? $product['qty'] ?? $product['quantity'] ?? $product['inventory'] ?? 0);
}

function isOut(array $product) {
    $stock = getStock($product);
    if ($stock <= 0) {
        return true;
    }
    $status = strtolower(trim($product['status'] ?? ''));
    if ($status === 'hidden' || $status === 'inactive') {
        return true;
    }
    $badge = strtolower(trim($product['badge'] ?? ''));
    if ($badge === 'out_of_stock' || $badge === 'oos') {
        return true;
    }
    return false;
}

function badgeLabel(array $product) {
    $badge = strtolower(trim($product['badge'] ?? ''));
    if ($badge === 'sale') {
        return 'Sale';
    }
    if ($badge === 'new') {
        return 'New';
    }
    if ($badge === 'out_of_stock' || $badge === 'oos') {
        return 'Hết hàng';
    }
    return '';
}

function getRootUrl() {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    return rtrim(dirname(dirname($script)), '/\\');
}

// Trả về ../assets/... cho trang sản phẩm
function assetUrl($path) {
    if (!$path) {
        return '';
    }
    if (preg_match('#^(?:https?:)?//#', $path)) {
        return $path;
    }
    if (preg_match('#^(?:/|(?:\.\./|\./))*assets/#', $path)) {
        return preg_replace('#^(?:/|(?:\.\./|\./))*assets/#', '../assets/', $path);
    }
    return '../assets/images/product/' . ltrim($path, '/');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Sản phẩm – Nobility 1800s</title>
    <meta name="description" content="Danh mục sản phẩm – tìm kiếm, lọc theo phân loại/giá, sắp xếp và phân trang. Hỗ trợ cập nhật trực tiếp khi Admin thay đổi." />

    <!-- CSS chung -->
    <link rel="stylesheet" href="<?php echo h(asset_versioned_url('../assets/css/style.css')); ?>" />
    <link rel="stylesheet" href="../assets/css/base.css" />

    <!-- Vendor CSS -->
    <link rel="stylesheet" href="../bootstrap-4.6.2-dist/css/bootstrap.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="../fontawesome-free-6.7.2-web/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../assets/css/normalize.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>

         :root {
            --bg: #fff;
            --text: #111;
            --muted: #6b7280;
            --line: #eceff3;
            --radius: 14px;
            --shadow-sm: 0 6px 16px rgba(17, 24, 39, 0.08);
            --shadow-md: 0 12px 28px rgba(17, 24, 39, 0.12);
            --ease: cubic-bezier(0.22, 1, 0.36, 1);
        }
        .topbar { background: #fff; border-bottom: 1px solid #eee; padding: 10px 0; }
        .topbar>.container { display: flex; align-items: center; justify-content: flex-end; }
        .topbar-right { margin-left: auto; display: flex; gap: 12px; }
        .topbar-right a { margin-left: 10px; padding: 5px 12px; font-size: 13px; border-radius: 4px; transition: 0.3s; text-decoration: none; }
        .btn-outline { border: 1px solid #333; color: #333; background: #fff; }
        .btn-outline:hover { background: #333; color: #fff; }
        .btn-primary, .btn-logout { background: #333 !important; color: #fff !important; border: 1px solid #333 !important; border-radius: 4px !important; padding: 5px 12px !important; font-size: 13px !important; transition: 0.3s !important; display: inline-block !important; }
        .btn-primary:hover, .btn-logout:hover { background: #555 !important; }
        .page-main { padding-bottom: 40px; }
        .page-main .container { max-width: 1200px; margin: 0 auto; padding: 0 16px; }
        .alert { margin: 20px 0 34px; padding: 16px 18px; border-radius: 14px; background: #fff3cd; color: #513f1f; border: 1px solid #ffecb5; box-shadow: 0 10px 20px rgba(18, 38, 63, 0.05); }
        .bread-crumb { padding: 10px 0; border-bottom: 1px solid #eee; }
        .breadcrumb { list-style: none; margin: 0; padding: 0; display: flex; gap: 8px; font-size: 14px; background: transparent !important; }
        .breadcrumb li { color: #666; display: flex; align-items: center; }
        .breadcrumb li.home a { font-weight: 600; color: #333; }
        .breadcrumb .sep { color: #999; }
        .breadcrumb li strong span { color: #000; }
        .txt_content_child, .sdt { font-size: 10px; }
        .sv-search { display: grid; grid-template-columns: 1.6fr 1fr 0.9fr 0.9fr 1fr auto; gap: 12px; padding: 14px; border-radius: 12px; align-items: end; }
        .sv-field { display: flex; flex-direction: column; gap: 6px; }
        .sv-label { font-size: 12px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.03em; min-height: 16px; line-height: 16px; }
        .sv-field input, .sv-field select { height: 44px; border: 1px solid #e5e7eb; border-radius: 10px; padding: 0 12px; font-size: 14px; background: #fff; transition: border-color 0.2s, box-shadow 0.2s; }
        .sv-field input:focus, .sv-field select:focus { outline: none; border-color: #cbd5e1; box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.08); }
        .sv-icon { position: relative; }
        .sv-icon i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 14px; }
        .sv-icon input { padding-left: 34px; }
        .sv-actions { display: flex; gap: 8px; justify-content: flex-end; }
        .sv-btn { height: 44px; padding: 0 16px; border-radius: 10px; border: 1px solid transparent; display: inline-flex; align-items: center; gap: 8px; font-weight: 800; font-size: 14px; cursor: pointer; transition: transform 0.08s, box-shadow 0.2s, background 0.2s, color 0.2s, border-color 0.2s; }
        .sv-btn:active { transform: translateY(1px); }
        .sv-btn-primary { background: #111; color: #fff; border-color: #111; }
        .sv-btn-primary:hover { box-shadow: var(--shadow-sm); }
        .sv-btn-ghost { background: #fff; color: #111; border-color: #e5e7eb; }
        .sv-btn-ghost:hover { background: #f3f4f6; border-color: #d1d5db; }
        @media (max-width: 992px) { .sv-search { grid-template-columns: 1fr 1fr 1fr 1fr; } .sv-search #q { grid-column: 1 / -1; } .sv-actions { grid-column: 1 / -1; justify-content: stretch; } }
        @media (max-width: 576px) { .sv-search { grid-template-columns: 1fr; } }
        .sv-divider { border: 0; border-top: 1px solid #e9eef5; margin: 8px 0 16px; }
        .row.equalize-cards { display: flex; flex-wrap: wrap; margin: -20px; }
        .col { padding: 20px; }
        .col-3 { width: 25%; }
        @media (max-width: 991.98px) { .col-3 { width: 50%; } }
        @media (max-width: 575.98px) { .col-3 { width: 100%; } }
        .product-box { background: var(--bg); border: 1px solid var(--line); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow-sm); transition: 0.35s var(--ease); position: relative; display: flex; flex-direction: column; height: 100%; }
        .product-box:hover { box-shadow: var(--shadow-md); border-color: #e6ebf0; }
        .product-box.is-out { opacity: 0.9; }
        .product-thumbnail { position: relative; overflow: hidden; background: linear-gradient(180deg, #fafbfc 0%, #f7fafc 100%); aspect-ratio: 1/1; display: flex; align-items: center; justify-content: center; }
        .image-link { position: absolute; inset: 0; display: flex; }
        .image-link img { width: 100%; height: 100%; object-fit: contain; transition: transform 0.5s var(--ease); }
        .product-box:hover .image-link img { transform: scale(1.03); }
        .product-action-grid { position: absolute; left: 0; right: 0; bottom: 0; z-index: 5; opacity: 1; transform: none; pointer-events: auto; transition: none; }
        .product-thumbnail>.product-action-grid .btn-cart { display: block; width: 100%; padding: 12px 16px; border: 0; border-radius: 0; background: #111; color: #fff; font-weight: 800; }
        .product-label { position: absolute; left: 12px; top: 12px; display: flex; gap: 8px; flex-wrap: wrap; }
        .product-label .label { font-size: 11px; font-weight: 700; letter-spacing: 0.04em; padding: 5px 9px; background: #fff; color: #f00; border: 1px #f00 solid; box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06); text-transform: uppercase; }
        .product-label .label-oos { color: #6b7280; border-color: #9ca3af; }
        .product-info { padding: 12px 14px 14px; display: flex; flex-direction: column; flex: 1 1 auto; }
        .product-name { margin: 2px 0 8px; font-size: 16px; line-height: 1.35; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .product-name a { color: var(--text); background: linear-gradient(currentColor, currentColor) 0 100%/0 2px no-repeat; transition: background-size 0.35s var(--ease), color 0.35s var(--ease); }
        .product-name a:hover { color: #000; background-size: 100% 2px; }
        .price-box { margin-top: auto; display: flex; align-items: center; gap: 10px; justify-content: center; }
        .product-price { color: #ff0000; font-weight: 500; font-size: 17px; }
        .product-price-old { color: var(--muted); font-size: 14px; text-decoration: line-through; }
        .product-stock { margin-top: 4px; font-size: 12px; text-align: center; }
        .product-stock span { display: inline-flex; align-items: center; gap: 4px; }
        .stock-in { color: #16a34a; font-weight: 500; }
        .stock-low { color: #ea580c; font-weight: 600; }
        .stock-out { color: #6b7280; font-weight: 500; }
        .stock-in i, .stock-low i, .stock-out i { font-size: 11px; }
        .btn-add-to-cart { width: 100%; margin-top: 10px; padding: 10px 12px; border: 1px solid #111; border-radius: 8px; background: #111; color: #fff; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; cursor: pointer; transition: all 0.2s ease; font-family: inherit; }
        .btn-add-to-cart:hover:not(:disabled) { background: #333; border-color: #333; box-shadow: 0 2px 8px rgba(17, 17, 17, 0.15); }
        .btn-add-to-cart:active:not(:disabled) { transform: translateY(1px); }
        .btn-add-to-cart:disabled { opacity: 0.5; cursor: not-allowed; background: #999; border-color: #999; }
        .pagination-nav { display: flex; justify-content: center; padding: 24px 0 16px; margin-top: 8px; }
        .pagination-list { display: flex; list-style: none; margin: 0; padding: 0; gap: 12px; }
        .page-item .page-link { display: block; padding: 8px 12px; min-width: 40px; text-align: center; color: #333; font-size: 15px; border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; transition: all 0.2s ease; }
        .page-item.active .page-link { color: #fff; font-weight: 700; background: #222; border-color: #222; }
        .page-item.disabled .page-link { opacity: 0.4; pointer-events: none; }
        .page-item:not(.active) .page-link:hover { text-decoration: none; border-color: #cbd5e1; background: #f3f4f6; }
        .page-item .page-link[aria-label="Trang trước"],
        .page-item .page-link[aria-label="Trang sau"] { width: 38px; }
        .main-nav a { text-decoration: none; }


        @media (max-width: 992px) {
            .sv-search { grid-template-columns: 1fr 1fr 1fr 1fr; position: static; }
            .sv-search #q { grid-column: 1 / -1; }
            .sv-actions { grid-column: 1 / -1; justify-content: stretch; }
            .col-3 { width: 50%; }
        }

        @media (max-width: 576px) {
            .sv-search { grid-template-columns: 1fr; border-radius: 14px; }
            .col-3 { width: 100%; }
            .product-name { font-size: 16px; }
            .product-price { font-size: 20px; }
        }

        @media (max-width: 767.98px) {
            .header-left, .search-bar { display: none !important; }
            .header-main { display: flex !important; align-items: center !important; justify-content: space-between !important; padding: 8px 10px !important; gap: 8px !important; }
            .header-center { flex: 0 0 auto; max-width: 40% !important; overflow: hidden; }
            .header-center .logo img { max-width: 120px !important; height: auto !important; display: block; }
            .header-right { flex: 0 0 auto; }
            .header-right .cart-link { display: inline-flex !important; align-items: center; gap: 4px; padding: 4px 6px !important; margin: 0 !important; font-size: 12px !important; white-space: nowrap !important; }
            .header-right .cart-link i { font-size: 15px !important; }
        }
    </style>
</head>

<body>
    <?php render_topbar(); ?>

    <!-- Mid header -->
    <header class="mid-header">
        <div class="container">
            <div class="header-main">
                <div class="header-center">
                    <a href="../index.php" class="logo"><img src="../assets/images/logo.jpg" alt="GENTLEMAN" /></a>
                </div>
                <div class="header-right">
                    <a href="../cart.php" class="cart-link">
                        <i class="fa-solid fa-cart-shopping"></i> GIỎ HÀNG (<span class="cart-count"><?php echo cart_count(); ?></span>)
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main nav -->
    <nav class="main-nav">
        <ul>
            <li><a href="../index.php">TRANG CHỦ</a></li>
            <li><a href="../about.php">GIỚI THIỆU</a></li>
            <li>
                <a href="./product.php" class="js-products-url">SẢN PHẨM</a>
            </li>
            <li><a href="../news.php">TIN TỨC</a></li>
            <li><a href="../contact.php">LIÊN HỆ</a></li>
        </ul>
    </nav>

    <!-- Breadcrumb -->
    <section class="bread-crumb">
        <div class="container">
            <ul class="breadcrumb">
                <li class="home">
                    <a href="../index.php"><span>Trang chủ</span></a>
                </li>
                <li class="sep"><i class="fa-solid fa-angle-right"></i></li>
                <li>
                    <strong><span>Sản phẩm</span></strong>
                </li>
            </ul>
        </div>
    </section>

    <!-- Page content -->
    <div class="page-main">
        <div class="container">
            <form id="searchForm" class="sv-search" method="get" autocomplete="off">
                <div class="sv-field sv-icon">
                    <label class="sv-label">Tìm kiếm</label>
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input id="q" name="q" type="text" placeholder="Gõ từ khoá (vd: gold, gôm, wax)" aria-label="Từ khoá tìm kiếm" value="<?php echo h($q); ?>" />
                </div>
                <div class="sv-field">
                    <label class="sv-label">Phân loại</label>
                    <select id="category" name="category" aria-label="Phân loại">
              <option value="all">Tất cả</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo h($cat['slug']); ?>" <?php if ($categorySlug === $cat['slug']) echo 'selected'; ?>><?php echo h($cat['name']); ?></option>
            <?php endforeach; ?>
            </select>
                </div>
                <div class="sv-field">
                    <label class="sv-label">Giá từ</label>
                    <input id="priceMin" name="priceMin" type="text" inputmode="numeric" placeholder="" aria-label="Giá tối thiểu" value="<?php echo ($priceMin !== '' ? h($priceMin) : ''); ?>" />
                </div>
                <div class="sv-field">
                    <label class="sv-label">Đến</label>
                    <input id="priceMax" name="priceMax" type="text" inputmode="numeric" placeholder="" aria-label="Giá tối đa" value="<?php echo ($priceMax !== '' ? h($priceMax) : ''); ?>" />
                </div>
                <div class="sv-field">
                    <label class="sv-label">Sắp xếp</label>
                    <select id="sort" name="sort" aria-label="Sắp xếp">
              <option value="">Mặc định</option>
              <option value="price-asc" <?php if ($sort === 'price-asc') echo 'selected'; ?>>Giá ↑</option>
              <option value="price-desc" <?php if ($sort === 'price-desc') echo 'selected'; ?>>Giá ↓</option>
              <option value="name-asc" <?php if ($sort === 'name-asc') echo 'selected'; ?>>Tên A→Z</option>
              <option value="name-desc" <?php if ($sort === 'name-desc') echo 'selected'; ?>>Tên Z→A</option>
            </select>
                </div>
                <div class="sv-actions">
                    <button type="submit" class="sv-btn sv-btn-primary">
                            <i class="fa-solid fa-sliders"></i> Tìm
            </button>
                    <button type="button" id="svReset" class="sv-btn sv-btn-ghost">
                            Xóa
            </button>
                </div>
            </form>

            <?php if (empty($products)): ?>
              <div class="alert alert-warning">Không tìm thấy sản phẩm phù hợp.</div>
            <?php endif; ?>

            <div class="row equalize-cards" id="product-grid">
                <?php foreach ($products as $product): ?>
                    <?php
                        $out = isOut($product);
                        $badge = badgeLabel($product);
                        $stock = getStock($product);
                        $hasOld = isset($product['original_price']) && $product['original_price'] > $product['sale_price'];
                        $rootUrl = getRootUrl();
                        $image = assetUrl($product['image'] ?: '../assets/images/product/sample1.jpg');
                    ?>
                    <div class="col col-3">
                        <div class="product-box <?php echo $out ? 'is-out' : ''; ?>">
                            <div class="product-thumbnail">
                                <a class="image-link" href="pages/product_detail.php?id=<?php echo h($product['id']); ?>">
                                    <img src="<?php echo h($image); ?>" alt="<?php echo h($product['name']); ?>" />
                                </a>
                                <?php if ($badge || $out): ?>
                                    <div class="product-label">
                                        <?php if ($badge): ?><span class="label"><?php echo h($badge); ?></span><?php endif; ?>
                                        <?php if ($out): ?><span class="label label-oos">Hết hàng</span><?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h3 class="product-name">
                                    <a href="<?php echo h($rootUrl); ?>/Product/pages/product_detail.php?id=<?php echo h($product['id']); ?>"><?php echo h($product['name']); ?></a>
                                </h3>
                                <div class="price-box">
                                    <span class="price product-price"><?php echo number_format($product['sale_price'], 0, ',', '.'); ?>₫</span>
                                    <?php if ($hasOld): ?><span class="price product-price-old"><?php echo number_format($product['original_price'], 0, ',', '.'); ?>₫</span><?php endif; ?>
                                </div>
                                <div class="product-stock">
                                    <span class="<?php echo $out ? 'stock-out' : ($stock <= 5 ? 'stock-low' : 'stock-in'); ?>">
                                        <i class="fa-solid <?php echo $out ? 'fa-circle-xmark' : ($stock <= 5 ? 'fa-triangle-exclamation' : 'fa-circle-check'); ?>"></i>
                                        <?php echo $out ? 'Hết hàng' : 'Còn ' . $stock . ' sp' . ($stock <= 5 ? ' · Sắp hết' : ''); ?>
                                    </span>
                                </div>
                                <button class="btn-add-to-cart" type="button" data-product-id="<?php echo h($product['id']); ?>" data-product-name="<?php echo h($product['name']); ?>" data-product-stock="<?php echo h($stock); ?>" <?php echo $out ? 'disabled' : ''; ?>>
                                    <i class="fa-solid fa-shopping-cart"></i>
                                    <?php echo $out ? 'Hết hàng' : 'Thêm vào giỏ'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($total > 0): ?>
                <nav class="pagination-nav" aria-label="Phân trang">
                    <ul class="pagination-list">
                        <li class="page-item <?php echo $page === 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?><?php echo buildQuery(['q','category','priceMin','priceMax','sort']); ?>" aria-label="Trang trước">&lsaquo;</a>
                        </li>
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo buildQuery(['q','category','priceMin','priceMax','sort']); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page === $pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo min($pages, $page + 1); ?><?php echo buildQuery(['q','category','priceMin','priceMax','sort']); ?>" aria-label="Trang sau">&rsaquo;</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="site-footer">
            <div class="top-footer">
                <div class="container">
                    <div class="row">
                        <section class="widget-ft">
                            <h4 class="title-menu">Thông tin</h4>
                            <ul class="list-menu">
                                <li class="li_menu"><a href="../index.php">Trang chủ</a></li>
                                <li class="li_menu"><a href="../about.php">Giới thiệu</a></li>
                                <li class="li_menu"><a href="./product.php">Sản phẩm</a></li>
                                <li class="li_menu"><a href="../news.php">Tin tức</a></li>
                                <li class="li_menu"><a href="../contact.php">Liên hệ</a></li>
                            </ul>
                        </section>
                        <section class="widget-ft">
                            <h4 class="title-menu">Hỗ trợ</h4>
                            <ul class="list-menu">
                                <li class="li_menu"><a href="../index.php">Trang chủ</a></li>
                                <li class="li_menu"><a href="../about.php">Giới thiệu</a></li>
                                <li class="li_menu"><a href="./product.php">Sản phẩm</a></li>
                                <li class="li_menu"><a href="../news.php">Tin tức</a></li>
                                <li class="li_menu"><a href="../contact.php">Liên hệ</a></li>
                            </ul>
                        </section>
                        <section class="widget-ft">
                            <h4 class="title-menu">Hướng dẫn</h4>
                            <ul class="list-menu">
                                <li class="li_menu"><a href="../index.php">Trang chủ</a></li>
                                <li class="li_menu"><a href="../about.php">Giới thiệu</a></li>
                                <li class="li_menu"><a href="./product.php">Sản phẩm</a></li>
                                <li class="li_menu"><a href="../news.php">Tin tức</a></li>
                                <li class="li_menu"><a href="../contact.php">Liên hệ</a></li>
                            </ul>
                        </section>
                        <section class="widget-ft">
                            <h4 class="title-menu">Chính sách</h4>
                            <ul class="list-menu">
                                <li class="li_menu"><a href="../index.php">Trang chủ</a></li>
                                <li class="li_menu"><a href="../about.php">Giới thiệu</a></li>
                                <li class="li_menu"><a href="./product.php">Sản phẩm</a></li>
                                <li class="li_menu"><a href="../news.php">Tin tức</a></li>
                                <li class="li_menu"><a href="../contact.php">Liên hệ</a></li>
                            </ul>
                        </section>
                        <section class="wg-logo">
                            <h4 class="title-menu">Liên hệ</h4>
                            <ul class="contact">
                                <li><span class="txt_content_child"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> 140B-Tổ 3, Ấp Xóm Chùa,Tỉnh Tây Ninh</span></li>
                                <li class="sdt"><span><i class="fa-solid fa-phone" aria-hidden="true"></i></span><a href="tel:0338286525">0338286525</a></li>
                                <li class="sdt"><span><i class="fa-solid fa-envelope"></i></span><a href="mailto:thanhloc29052006@gmail.com">thanhloc29052006@gmail.com</a></li>
                            </ul>
                        </section>
                    </div>
                </div>
            </div>

            <div class="mid-footer">
                <div class="container">
                    <div class="row">
                        <div class="fot_copyright"><span class="wsp"><span>Cung cấp bởi <a href="javascript:;">Nhóm 7</a></span></span></div>
                        <nav class="fot_menu_copyright">
                            <ul class="ul_menu_fot">
                                <li><a href="../index.php" title="Trang chủ">Trang chủ</a></li>
                                <li><a href="../about.php" title="Giới thiệu">Giới thiệu</a></li>
                                <li><a href="./product.php" title="Sản phẩm">Sản phẩm</a></li>
                                <li><a href="../news.php" title="Tin tức">Tin tức</a></li>
                                <li><a href="../contact.php" title="Liên hệ">Liên hệ</a></li>
                            </ul>
                        </nav>
                        <div class="pay_footer">
                            <ul class="follow_option">
                                <li><a href="#"><img src="../assets/images/pay_1.webp" alt="Payment" /></a></li>
                                <li><a href="#"><img src="../assets/images/pay_2.webp" alt="Payment" /></a></li>
                                <li><a href="#"><img src="../assets/images/pay_3.webp" alt="Payment" /></a></li>
                                <li><a href="#"><img src="../assets/images/pay_4.webp" alt="Payment" /></a></li>
                                <li><a href="#"><img src="../assets/images/pay_5.webp" alt="Payment" /></a></li>
                                <li><a href="#"><img src="../assets/images/pay_6.webp" alt="Payment" /></a></li>
                                <li><a href="#"><img src="../assets/images/pay_7.webp" alt="Payment" /></a></li>
                            </ul>
                        </div>
                        <a href="#" id="back-to-top" class="backtop" title="Lên đầu trang"><i class="fa-solid fa-angle-up" aria-hidden="true"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <?php if ($user): ?>
      <script>
        window.SERVER_AUTH_STATE = {
          loggedIn: true,
          user: {
            name: <?php echo json_encode($user['full_name'] ?? $user['name'] ?? ''); ?>,
            email: <?php echo json_encode($user['email'] ?? ''); ?>
          }
        };
      </script>
    <?php endif; ?>
    <script src="<?php echo h(asset_versioned_url('../assets/js/auth.js')); ?>"></script>
    <script src="<?php echo h(asset_versioned_url('../assets/js/auth.security.enforcer.js')); ?>"></script>
    <script src="<?php echo h(asset_versioned_url('../assets/js/auth.modal.bridge.js')); ?>"></script>
    <script src="<?php echo h(asset_versioned_url('../assets/js/products.seed.js')); ?>"></script>
    <script src="<?php echo h(asset_versioned_url('../assets/js/store.js')); ?>"></script>
    <script src="<?php echo h(asset_versioned_url('../assets/js/ui.js')); ?>"></script>
    <script src="<?php echo h(asset_versioned_url('../assets/js/jquery-3.6.0.min.js')); ?>" crossorigin="anonymous"></script>
    <script src="<?php echo h(asset_versioned_url('../assets/js/bootstrap.bundle.min.js')); ?>" crossorigin="anonymous"></script>
    <script src="<?php echo h(asset_versioned_url('../assets/js/auth.kick.guard.js')); ?>"></script>
    
    <script>
        document.getElementById('svReset')?.addEventListener('click', function() {
            const form = document.getElementById('searchForm');
            if (!form) return;
            form.querySelector('[name="q"]').value = '';
            form.querySelector('[name="category"]').value = 'all';
            form.querySelector('[name="priceMin"]').value = '';
            form.querySelector('[name="priceMax"]').value = '';
            form.querySelector('[name="sort"]').value = '';
            form.submit();
        });

        function updateHeaderCartCount(nextCount) {
            const normalized = Number(nextCount);
            const value = Number.isFinite(normalized) ? Math.max(0, normalized) : 0;

            window.SERVER_CART_COUNT = value;
            window.SERVER_CART_COUNT_SOURCE = 'server';

            document.querySelectorAll('.cart-count, #cartCount').forEach(el => {
                el.textContent = String(value);
            });

            try {
                window.dispatchEvent(new CustomEvent('cart:changed'));
            } catch (_) {}
        }

        async function addToServerCart(productId, qty) {
            const endpoint = <?php echo json_encode(app_rel_url('cart.php')); ?> + '?action=add';
            const body = new URLSearchParams({
                id: String(productId),
                qty: String(qty),
                redirect: 'none'
            });

            const response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body.toString()
            });

            if (!response.ok) {
                throw new Error('Máy chủ không phản hồi khi thêm giỏ hàng.');
            }

            return response.json();
        }

        // Handle "Add to Cart" button clicks
        document.querySelectorAll('.btn-add-to-cart').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Check if button is disabled (out of stock)
                if (this.disabled) {
                    alert('Sản phẩm này hiện đã hết hàng.');
                    return;
                }

                const productId = this.dataset.productId;
                const productName = this.dataset.productName;
                const productStock = parseInt(this.dataset.productStock, 10) || 0;

                if (!productId) {
                    alert('Lỗi: Không tìm thấy ID sản phẩm');
                    return;
                }

                // Check if user is logged in
                if (!window.SERVER_AUTH_STATE?.loggedIn) {
                    const loginUrl = <?php echo json_encode(app_rel_url('account/login.php')); ?>;
                    alert('Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng.');
                    window.location.href = loginUrl;
                    return;
                }

                // Verify stock is available
                if (productStock <= 0) {
                    alert('Sản phẩm này hiện không có sẵn.');
                    return;
                }

                this.disabled = true;
                addToServerCart(productId, 1)
                    .then((data) => {
                        if (data && data.success) {
                            updateHeaderCartCount(data.cart_count);
                            alert('Đã thêm "' + productName + '" vào giỏ hàng!');
                            return;
                        }

                        if (data && data.message) {
                            alert(data.message);
                        } else {
                            alert('Không thể thêm sản phẩm vào giỏ hàng lúc này.');
                        }
                    })
                    .catch((err) => {
                        console.error('Error adding to cart:', err);
                        alert('Có lỗi xảy ra khi thêm vào giỏ. Vui lòng thử lại.');
                    })
                    .finally(() => {
                        if (!this.dataset.productStock || Number(this.dataset.productStock) > 0) {
                            this.disabled = false;
                        }
                    });
            });
        });
    </script>
</body>
</html>
