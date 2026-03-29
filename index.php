<?php
require_once __DIR__ . '/includes/common.php';

$user = current_user();
$newProducts = db_fetch_all(
    'SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p JOIN categories c ON p.category_id = c.id WHERE p.status = "selling" AND c.status = "active" ORDER BY p.created_at DESC LIMIT 8'
);


// Trả về ../assets/... cho admin và trang sản phẩm
function assetUrl($path) {
    if (!$path) {
        return '';
    }
    if (preg_match('#^(?:https?:)?//#', $path)) {
        return $path;
    }
    // Nếu đã là ../assets hoặc ./assets thì giữ nguyên
    if (preg_match('#^(\.\./|\./)?assets/#', $path)) {
        return $path;
    }
    // Nếu chỉ là tên file, tự động thêm ../assets/images/product/
    return '../assets/images/product/' . ltrim($path, '/');
}

function badgeLabel($value) {
    $badge = strtolower(trim((string)$value));
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

function formatPrice($value) {
    return number_format((int)$value, 0, ',', '.') . '₫';
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Trang bán sản phẩm sáp</title>
    <meta name="description" content="Cửa hàng sáp vuốt tóc – sản phẩm mới, ưu đãi, giao nhanh, đổi trả dễ dàng." />

    <!-- Vendor CSS -->
    <link rel="stylesheet" href="./bootstrap-4.6.2-dist/css/bootstrap.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="./fontawesome-free-6.7.2-web/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="./assets/css/normalize.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Site CSS -->
    <link rel="stylesheet" href="./assets/css/base.css" />
    <link rel="stylesheet" href="<?php echo h(asset_versioned_url('./assets/css/style.css')); ?>" />

    <style>
        /* ===== Variables & base ===== */
        
         :root {
            --bg: #fff;
            --text: #111;
            --muted: #6b7280;
            --line: #eceff3;
            --brand: #111;
            --radius: 14px;
            --shadow-sm: 0 6px 16px rgba(17, 24, 39, 0.08);
            --shadow-md: 0 12px 28px rgba(17, 24, 39, 0.12);
            --ease: cubic-bezier(0.22, 1, 0.36, 1);
            --container: 1140px;
            --gap: 24px;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: Roboto, system-ui, -apple-system, Segoe UI, Arial, sans-serif;
            color: var(--text);
            margin: 0;
        }
        
        a {
            color: inherit;
            text-decoration: none;
        }
        
        img {
            max-width: 100%;
            height: auto;
            display: block;
        }
        /* ===== Scoped layout for homepage only ===== */
        
        .page-main .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 16px;
            position: relative;
        }
        
        .page-main .container-pag {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 16px;
            position: relative;
        }
        /* Policy strip */
        
        .policy-box {
            margin: 10px 0;
        }
        /* link trong topbar */
        
        .topbar-right a {
            margin-left: 10px;
            padding: 5px 12px;
            font-size: 13px;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        /* viền đen, chữ đen */
        
        .btn-outline {
            border: 1px solid #333;
            color: #333;
        }
        
        .btn-outline:hover {
            background: #333;
            color: #fff;
        }
        /* nút đen, chữ trắng */
        
        .btn-primary {
            background: #333;
            color: #fff;
            border: 1px solid #333;
            border-radius: 4px;
            padding: 5px 12px;
            /* giống topbar-right a */
            font-size: 13px;
            text-decoration: none;
            /* để không bị gạch */
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: #555;
        }
        
        .policy-box .container {
            display: flex;
            gap: 20px;
            justify-content: space-between;
        }
        
        .policy-item {
            flex: 1;
            background: #202020;
            border: 1px solid #333;
            text-align: center;
            padding: 5px;
            border-radius: 4px;
        }
        
        .policy-item span {
            display: block;
            border: 1px solid #545454;
            line-height: 40px;
            font-size: 14px;
            color: #b8d0e0;
            text-transform: uppercase;
        }
        /* Hero slider (single image for now) */
        
        .awe-section-2 {
            margin: 30px 0;
        }
        
        .awe-section-2 .swiper-slide img {
            width: 100%;
            height: auto;
            object-fit: cover;
        }
        /* Three banners */
        
        .awe-section-3 {
            padding: 20px 0;
            background: #fff;
        }
        
        .adv-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        
        .adv_bottom_inner {
            border: 1px solid #e6e6e6;
        }
        
        .adv_bottom_inner img {
            border-radius: 6px;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        
        .adv_bottom_inner a:hover img {
            transform: translateY(-2px);
        }
        /* Brand slider (simple) */
        
        .section-brand {
            background: #fff;
        }
        
        .brand-slider {
            position: relative;
            display: flex;
            align-items: center;
            overflow: hidden;
        }
        
        .slider-track {
            display: flex;
            transition: transform 0.4s ease;
        }
        
        .slide {
            min-width: 200px;
            margin: 0 10px;
        }
        
        .btn-1 {
            background: #f3f4f6;
            color: #000;
            border: 1px solid #e5e7eb;
            font-size: 24px;
            padding: 8px 12px;
            cursor: pointer;
            z-index: 5;
        }
        
        .prev {
            position: absolute;
            left: 0;
        }
        
        .next {
            position: absolute;
            right: 0;
        }
        /* Product grid */
        
        .row.equalize-cards {
            display: flex;
            flex-wrap: wrap;
            margin: -20px;
        }
        
        .col {
            padding: 20px;
        }
        
        .col-3 {
            width: 25%;
        }
        
        @media (max-width: 991.98px) {
            .col-3 {
                width: 50%;
            }
        }
        
        @media (max-width: 575.98px) {
            .col-3 {
                width: 100%;
            }
        }
        
        .product-box {
            background: var(--bg);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: transform 0.35s var(--ease), box-shadow 0.35s var(--ease), border-color 0.35s;
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .product-box:hover {
            box-shadow: var(--shadow-md);
            border-color: #e6ebf0;
        }
        
        .product-thumbnail {
            position: relative;
            overflow: hidden;
            background: linear-gradient(180deg, #fafbfc 0%, #f7fafc 100%);
            aspect-ratio: 1/1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .image_link {
            position: absolute;
            inset: 0;
            display: flex;
        }
        
        .image_link img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.5s var(--ease), filter 0.4s var(--ease);
        }
        
        .product-box:hover .image_link img {
            transform: scale(1.03);
        }
        /* On-image CTA */
        
        .product-action-grid {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 5;
            opacity: 1;
            transform: none;
            pointer-events: auto;
            transition: none;
        }

        .product-thumbnail>.product-action-grid .btn-cart {
            display: block;
            width: 100%;
            padding: 12px 16px;
            border: 0;
            border-radius: 0;
            background: #111;
            color: #fff;
            font-weight: 800;
        }
        /* Labels */
        
        .product-label {
            position: absolute;
            left: 12px;
            top: 12px;
            display: flex;
            gap: 8px;
        }
        
        .product-label .label {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            padding: 6px 10px;
            background: #fff;
            color: #f00;
            border: 1px #f00 solid;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
        }
        /* Info */
        
        .product-info {
            padding: 12px 14px 14px;
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
        }
        
        .product-name {
            margin: 2px 0 8px;
            font-size: 16px;
            line-height: 1.35;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .product-name a {
            color: var(--text);
            background: linear-gradient(currentColor, currentColor) 0 100%/0 2px no-repeat;
            transition: background-size 0.35s var(--ease), color 0.35s var(--ease);
        }
        
        .product-name a:hover {
            color: #000;
            background-size: 100% 2px;
        }
        
        .price-box {
            margin-top: auto;
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
        }
        
        .product-price {
            color: #ff0000;
            font-weight: 500;
            font-size: 17px;
        }
        
        .product-price-old {
            color: var(--muted);
            font-size: 14px;
            text-decoration: line-through;
        }
        /* Section heading */
        
        .heading_spbc {
            display: flex;
            justify-content: center;
            margin: 10px 0 30px;
        }
        
        .heading_spbc .title-head {
            margin: 0;
            font-size: 22px;
        }
        /* Pagination */
        
        .pagination-nav {
            display: flex;
            justify-content: center;
            padding: 24px 0 16px;
        }
        
        .pagination-list {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 12px;
        }
        
        .page-item .page-link {
            display: block;
            padding: 8px 12px;
            color: #333;
            font-size: 15px;
            border: 1px solid transparent;
        }
        
        .page-item.active .page-link {
            color: #000;
            font-weight: 700;
            border-bottom: 2px solid #000;
        }
        
        .page-item:not(.active) .page-link:hover {
            text-decoration: underline;
        }
        /* Two banners (bottom) */
        
        .awe-section-6 {
            padding: 32px 0 48px;
        }
        
        .content_banner {
            position: relative;
            margin-bottom: var(--gap);
        }
        
        .content_banner .des {
            position: absolute;
            left: 16px;
            bottom: 16px;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            padding: 14px 16px;
            max-width: 80%;
        }
        
        .content_banner .des h4 {
            margin: 0 0 4px;
            font-size: 20px;
            line-height: 1.2;
        }
        
        .txt_content_child {
            font-size: 10px;
        }
        
        .content_banner .des h4 span {
            border-bottom: 2px solid #fff;
            padding-bottom: 2px;
            color: #fff;
            font-size: 15px;
        }
        
        .content_banner .des p {
            margin: 6px 0 10px;
            color: #fff;
            font-size: 12px;
        }
        
        .content_banner .des a {
            font-weight: 600;
            font-size: 20px;
            color: #fff;
        }
        /* ===== Load More button ===== */
        
        .loadmore-wrap {
            display: flex;
            justify-content: center;
            padding: 18px 0 8px;
            margin-top: 40px;
        }
        
        .btn-loadmore {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            min-width: 220px;
            padding: 12px 18px;
            background: #111;
            color: #fff;
            border: 1px solid #111;
            box-shadow: 0 8px 20px rgba(17, 24, 39, 0.12);
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease, color 0.18s ease;
        }
        
        .sdt {
            font-size: 12px;
        }
        
        .btn-loadmore .lm-text {
            font-weight: 800;
            font-size: 15px;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }
        
        .btn-loadmore .lm-sub {
            font-size: 12px;
            opacity: 0.75;
        }
        
        .btn-loadmore:hover {
            box-shadow: 0 12px 28px rgba(17, 24, 39, 0.16);
            background: #fff;
            color: #000;
            border: 1px solid #111;
        }
        
        .btn-loadmore:active {
            transform: translateY(0);
            box-shadow: 0 6px 16px rgba(17, 24, 39, 0.12);
        }
        /* Trạng thái hết dữ liệu */
        
        .btn-loadmore.is-done {
            background: #f3f4f6;
            color: #6b7280;
            border-color: #e5e7eb;
            box-shadow: none;
            cursor: default;
            pointer-events: none;
        }
        
        .btn-loadmore.is-done .lm-sub {
            opacity: 0.9;
        }
        
        .btn-loadmore {
            text-decoration: none;
            text-align: center;
        }
        /* Mobile tinh gọn */
        
        @media (max-width: 575.98px) {
            .btn-loadmore {
                min-width: 180px;
                padding: 10px 16px;
            }
            .btn-loadmore .lm-text {
                font-size: 14px;
            }
        }
        
        .content_banner a img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 14px;
            transition: transform 0.4s ease;
        }
        
        @media (min-width: 576px) {
            .content_banner a img {
                height: 320px;
            }
        }
        /* Mobile hoverless */
        
        @media (hover: none) and (pointer: coarse) {
            .product-thumbnail>.product-action-grid {
                opacity: 1;
                transform: none;
                pointer-events: auto;
            }
        }
    </style>
</head>

<body>
    <?php render_topbar(); ?>

    <header class="mid-header">
        <div class="container">
            <div class="header-main">
                <div class="header-left">
                    <!-- Chặn autofill & gõ dấu ok -->
                    <form action="./Product/product.php" method="get" class="search-bar" autocomplete="off">
                        <input type="text" name="query" placeholder="Tìm kiếm" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" inputmode="search" />
                        <button type="submit" aria-label="Tìm kiếm">
                <i class="fa-solid fa-magnifying-glass"></i>
              </button>
                    </form>
                </div>

                <div class="header-center">
                    <a href="./index.php" class="logo">
                        <img src="./assets/images/logo.jpg" alt="GENTLEMAN" />
                    </a>
                </div>

                <div class="header-right">
                    <a href="./cart.php" class="cart-link">
                        <i class="fa-solid fa-cart-shopping"></i> GIỎ HÀNG (<span class="cart-count">0</span>)
                    </a>
                </div>
            </div>
        </div>
    </header>

    <nav class="main-nav">
        <ul>
            <li><a href="./index.php">TRANG CHỦ</a></li>
            <li><a href="./about.php">GIỚI THIỆU</a></li>
            <li>
                <a href="./Product/product.php" class="js-products-url">SẢN PHẨM</a>
            </li>
            <li><a href="./news.php">TIN TỨC</a></li>
            <li><a href="./contact.php">LIÊN HỆ</a></li>
        </ul>
    </nav>

    <script>
        (function(w, d) {
            // Lấy URL trang sản phẩm từ menu (ưu tiên .js-products-url)
            function getProductsURL() {
                var a =
                    d.querySelector("nav.main-nav a.js-products-url") ||
                    d.querySelector('nav.main-nav a[href*="/Product"]');
                return a && a.getAttribute("href") ?
                    a.getAttribute("href") :
                    "./Product/product.php";
            }

            var form = d.querySelector(".search-bar");
            if (!form) return;

            form.addEventListener(
                "submit",
                function(e) {
                    e.preventDefault();

                    var input = form.querySelector(
                        'input[name="query"], input[name="q"]'
                    );
                    var q = ((input && input.value) || "").trim().replace(/\s+/g, " ");
                    var raw = getProductsURL();

                    // Dùng URL để không bị nhân đôi ?q= và giữ các param khác nếu có
                    var url = new URL(raw, w.location.href);
                    if (q) url.searchParams.set("q", q);
                    else url.searchParams.delete("q");

                    // Điều hướng sang trang sản phẩm với ?q=
                    w.location.href = url.pathname + (url.search ? url.search : "");
                }, {
                    passive: false
                }
            );
        })(window, document);
    </script>
    <script>
        (function(w, d) {
            // gọi sau khi auth sẵn sàng
            function makeWelcomeClickable() {
                // ưu tiên phần tử của header mới
                var el = d.querySelector(".welcome-user");
                if (el) {
                    // đã là <a>, đảm bảo đúng href
                    el.setAttribute("href", "./account/profile.php");
                    return;
                }

                // các trường hợp site cũ: #welcomeName, [data-welcome-name], .js-welcome-name
                var target = d.querySelector(
                    "#welcomeName, [data-welcome-name], .js-welcome-name"
                );
                if (!target) return;

                // nếu chưa phải <a>, bọc lại thành <a>
                if (target.tagName !== "A") {
                    var a = d.createElement("a");
                    a.href = "./account/profile.php";
                    a.className = "welcome-link";
                    // chuyển toàn bộ nội dung vào <a>
                    while (target.firstChild) a.appendChild(target.firstChild);
                    target.appendChild(a);
                } else {
                    // đã là <a> thì gán href đúng
                    target.setAttribute("href", "./account/profile.php");
                }
            }

            // chạy khi auth ready, và dự phòng nếu auth đã sẵn sàng
            d.addEventListener("auth:ready", makeWelcomeClickable);
            if (w.AUTH && w.AUTH.ready) makeWelcomeClickable();
        })(window, document);
    </script>

    <div class="page-main">
        <!-- Policy strip -->
        <div class="policy-box">
            <div class="container">
                <div class="policy-item"><span>Miễn phí vận chuyển</span></div>
                <div class="policy-item"><span>Miễn phí đổi trả</span></div>
                <div class="policy-item"><span>Thanh toán trực tuyến</span></div>
            </div>
        </div>

        <!-- Hero slider (single) -->
        <section class="awe-section-2">
            <div class="container">
                <div class="section_slider swiper-container">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <a href="#" title="Slider 1">
                                <picture>
                                    <source media="(min-width:1200px)" srcset="./assets/images/home1.jpg" />
                                    <source media="(min-width:992px)" srcset="./assets/images/home1.jpg" />
                                    <source media="(min-width:569px)" srcset="./assets/images/home1.jpg" />
                                    <img src="./assets/images/home1.jpg" alt="Ưu đãi sáp vuốt tóc" class="img-responsive" />
                                </picture>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3 banners -->
        <section class="awe-section-3">
            <div class="container">
                <div class="adv-row">
                    <div class="adv_bottom_inner">
                        <figure>
                            <a href="#" title="Dưỡng tóc"><img src="./assets/images/duong_toc.webp" alt="Dưỡng tóc" loading="lazy" /></a>
                        </figure>
                    </div>
                    <div class="adv_bottom_inner">
                        <figure>
                            <a href="#" title="Gôm xịt tóc"><img src="./assets/images/gom_xit_toc.webp" alt="Gôm xịt tóc" loading="lazy" /></a>
                        </figure>
                    </div>
                    <div class="adv_bottom_inner">
                        <figure>
                            <a href="#" title="Sáp vuốt tóc"><img src="./assets/images/sap2.webp" alt="Sáp vuốt tóc" loading="lazy" /></a>
                        </figure>
                    </div>
                </div>
            </div>
        </section>

        <!-- Brand slider -->
        <div class="section-brand">
            <div class="container">
                <div class="brand-slider">
                    <button class="btn-1 prev" aria-label="Prev">‹</button>
                    <div class="slider-track">
                        <div class="slide">
                            <img src="./assets/images/brand1.webp" alt="Brand 1" />
                        </div>
                        <div class="slide">
                            <img src="./assets/images/brand_2.webp" alt="Brand 2" />
                        </div>
                        <div class="slide">
                            <img src="./assets/images/brand_3.webp" alt="Brand 3" />
                        </div>
                        <div class="slide">
                            <img src="./assets/images/brand_4.webp" alt="Brand 4" />
                        </div>
                        <div class="slide">
                            <img src="./assets/images/brand_5.webp" alt="Brand 5" />
                        </div>
                        <div class="slide">
                            <img src="./assets/images/brand_6.webp" alt="Brand 6" />
                        </div>
                        <div class="slide">
                            <img src="./assets/images/brand_7.webp" alt="Brand 7" />
                        </div>
                        <div class="slide">
                            <img src="./assets/images/brand_8.webp" alt="Brand 8" />
                        </div>
                    </div>
                    <button class="btn-1 next" aria-label="Next">›</button>
                </div>
                <hr />
            </div>
        </div>

        <!-- Products -->
        <section class="awe-section-5 section_new_product">
            <div class="container">
                <div class="heading_spbc">
                    <h2 class="title-head">
                        <a href="./Product/product.php" title="Sản phẩm mới">Sản phẩm mới</a>
                    </h2>
                </div>
                <div class="row equalize-cards">
                    <?php if (!empty($newProducts)): ?>
                        <?php foreach ($newProducts as $product): ?>
                            <div class="col col-3">
                                <div class="product-box<?php echo strtolower(trim($product['status'] ?? '')) !== 'selling' ? ' is-out' : ''; ?>">
                                    <div class="product-thumbnail">
                                        <a class="image_link" href="./Product/pages/product_detail.php?id=<?php echo urlencode($product['id']); ?>">
                                            <img src="<?php echo h(assetUrl($product['image'] ?? '')); ?>" alt="<?php echo h($product['name']); ?>" />
                                        </a>
                                        <?php $badge = badgeLabel($product['badge'] ?? ''); ?>
                                        <?php if ($badge): ?>
                                            <div class="product-label"><strong class="label"><?php echo h($badge); ?></strong></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="product-info a-left">
                                        <h3 class="product-name">
                                            <a href="./Product/pages/product_detail.php?id=<?php echo urlencode($product['id']); ?>"><?php echo h($product['name']); ?></a>
                                        </h3>
                                        <div class="price-box">
                                            <span class="price product-price"><?php echo formatPrice($product['sale_price'] ?? $product['price'] ?? 0); ?></span>
                                            <?php if (!empty($product['original_price']) && $product['original_price'] > ($product['sale_price'] ?? $product['price'] ?? 0)): ?>
                                                <span class="price product-price-old"><?php echo formatPrice($product['original_price']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col col-12">
                            <p>Hiện chưa có sản phẩm mới. Vui lòng quay lại sau.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="loadmore-wrap">
                    <a href="./Product/product.php" class="btn-loadmore" aria-label="Xem thêm">
                        <span class="lm-text">Xem thêm</span>
                    </a>
                </div>
            </div>
        </section>
    </div>
    <!-- ================== END PAGE MAIN ================== -->

    <!-- Two banners + About -->
    <section class="awe-section-6">
        <div class="home-two-banner">
            <div class="container">
                <div class="row">
                    <div class="col col-6 content_banner">
                        <a href="#" title="Handmade"><img class="img-responsive lazyload" src="./assets/images/sap_handmade.jpg" alt="Handmade" /></a>
                        <div class="des">
                            <h4><span>Handmade</span></h4>
                            <p>Sáp thủ công hương thơm đặc trưng, giữ nếp cao.</p>
                            <a href="#" class="hidden-xs" title="Xem thêm">Xem thêm</a>
                        </div>
                    </div>
                    <div class="col col-6 content_banner">
                        <a href="#" title="Bộ sưu tập mới"><img class="img-responsive lazyload" src="./assets/images/suu_tap.jpg" alt="Bộ sưu tập mới" /></a>
                        <div class="des">
                            <h4><span>Bộ sưu tập mới</span></h4>
                            <p>Cập nhật liên tục By Vilain, Hanz de Fuko, Apestomen…</p>
                            <a href="#" class="hidden-xs" title="Xem thêm">Xem thêm</a>
                        </div>
                    </div>
                </div>
                <div class="about-home">
                    <div class="inner">
                        <h5>Về chúng tôi</h5>
                        <p>
                            Nobility 1800s mang đến trải nghiệm mua sắm hàng hiệu trực tuyến đẳng cấp – từ quần áo, giày dép, phụ kiện đến mỹ phẩm cho nam & nữ – bắt kịp xu hướng mới nhất.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="site-footer">
            <div class="top-footer">
                <div class="container">
                    <div class="row">
                        <section class="widget-ft">
                            <h4 class="title-menu">Thông tin</h4>
                            <ul class="list-menu">
                                <li class="li_menu">
                                    <a href="./index.php">Trang chủ</a>
                                </li>
                                <li class="li_menu">
                                    <a href="./about.php">Giới thiệu</a>
                                </li>
                                <li class="li_menu">
                                    <a href="./Product/product.php">Sản phẩm</a>
                                </li>
                                <li class="li_menu"><a href="./news.php">Tin tức</a></li>
                                <li class="li_menu"><a href="./contact.php">Liên hệ</a></li>
                            </ul>
                        </section>

                        <section class="widget-ft">
                            <h4 class="title-menu">Hỗ trợ</h4>
                            <ul class="list-menu">
                                <li class="li_menu">
                                    <a href="./index.php">Trang chủ</a>
                                </li>
                                <li class="li_menu">
                                    <a href="./about.php">Giới thiệu</a>
                                </li>
                                <li class="li_menu">
                                    <a href="./Product/product.php">Sản phẩm</a>
                                </li>
                                <li class="li_menu"><a href="./news.php">Tin tức</a></li>
                                <li class="li_menu"><a href="./contact.php">Liên hệ</a></li>
                            </ul>
                        </section>

                        <section class="widget-ft">
                            <h4 class="title-menu">Hướng dẫn</h4>
                            <ul class="list-menu">
                                <li class="li_menu">
                                    <a href="./index.php">Trang chủ</a>
                                </li>
                                <li class="li_menu">
                                    <a href="./about.php">Giới thiệu</a>
                                </li>
                                <li class="li_menu">
                                    <a href="./Product/product.php">Sản phẩm</a>
                                </li>
                                <li class="li_menu"><a href="./news.php">Tin tức</a></li>
                                <li class="li_menu"><a href="./contact.php">Liên hệ</a></li>
                            </ul>
                        </section>

                        <section class="widget-ft">
                            <h4 class="title-menu">Chính sách</h4>
                            <ul class="list-menu">
                                <li class="li_menu">
                                    <a href="./index.php">Trang chủ</a>
                                </li>
                                <li class="li_menu">
                                    <a href="./about.php">Giới thiệu</a>
                                </li>
                                <li class="li_menu">
                                    <a href="./Product/product.php">Sản phẩm</a>
                                </li>
                                <li class="li_menu"><a href="./news.php">Tin tức</a></li>
                                <li class="li_menu"><a href="./contact.php">Liên hệ</a></li>
                            </ul>
                        </section>

                        <section class="wg-logo">
                            <h4 class="title-menu">Liên hệ</h4>
                            <ul class="contact">
                                <li>
                                    <span class="txt_content_child">
                      <span
                        ><i
                          class="fa-solid fa-location-dot"
                          aria-hidden="true"
                        ></i
                      ></span> 140B-Tổ 3, Ấp Xóm Chùa,Tỉnh Tây Ninh
                                    </span>
                                </li>
                                <li class="sdt">
                                    <span><i class="fa-solid fa-phone" aria-hidden="true"></i
                    ></span>
                                    <a href="tel:0338286525">0338286525</a>
                                </li>
                                <li class="sdt">
                                    <span><i class="fa-solid fa-envelope"></i></span>
                                    <a href="mailto:thanhloc29052006@gmail.com">thanhloc29052006@gmail.com</a
                    >
                  </li>
                </ul>
              </section>
            </div>
          </div>
        </div>

        <div class="mid-footer">
          <div class="container">
            <div class="row">
              <div class="fot_copyright">
                <span class="wsp"
                  ><span
                    >Cung cấp bởi <a href="javascript:;">Nhóm 7</a></span>
                                    </span>
                    </div>
                    <nav class="fot_menu_copyright">
                        <ul class="ul_menu_fot">
                            <li>
                                <a href="./index.php" title="Trang chủ">Trang chủ</a>
                            </li>
                            <li>
                                <a href="./about.php" title="Giới thiệu">Giới thiệu</a>
                            </li>
                            <li>
                                <a href="./Product/product.php" title="Sản phẩm">Sản phẩm</a
                    >
                  </li>
                  <li><a href="./news.php" title="Tin tức">Tin tức</a></li>
                            <li><a href="./contact.php" title="Liên hệ">Liên hệ</a></li>
                        </ul>
                    </nav>
                    <div class="pay_footer">
                        <ul class="follow_option">
                            <li>
                                <a href="#"><img src="./assets/images/pay_1.webp" alt="Payment" /></a>
                            </li>
                            <li>
                                <a href="#"><img src="./assets/images/pay_2.webp" alt="Payment" /></a>
                            </li>
                            <li>
                                <a href="#"><img src="./assets/images/pay_3.webp" alt="Payment" /></a>
                            </li>
                            <li>
                                <a href="#"><img src="./assets/images/pay_4.webp" alt="Payment" /></a>
                            </li>
                            <li>
                                <a href="#"><img src="./assets/images/pay_5.webp" alt="Payment" /></a>
                            </li>
                            <li>
                                <a href="#"><img src="./assets/images/pay_6.webp" alt="Payment" /></a>
                            </li>
                            <li>
                                <a href="#"><img src="./assets/images/pay_7.webp" alt="Payment" /></a>
                            </li>
                        </ul>
                    </div>
                    <a href="#" id="back-to-top" class="backtop" title="Lên đầu trang">
                        <i class="fa-solid fa-angle-up" aria-hidden="true"></i>
                    </a>
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
    <script src="<?php echo h(asset_versioned_url('./assets/js/auth.js')); ?>"></script>
    <script src="./assets/js/auth.security.enforcer.js"></script>
    <script src="<?php echo h(asset_versioned_url('./assets/js/auth.modal.bridge.js')); ?>"></script>

    <script src="<?php echo h(asset_versioned_url('./assets/js/store.js')); ?>"></script>
    <script src="<?php echo h(asset_versioned_url('./assets/js/ui.js')); ?>"></script>
    <!-- ===== JS ===== -->
    <script>
        /* ===== CHỐT CHẶN: Hover overlay đã có bằng CSS; click "Giỏ hàng" => bắt buộc đăng nhập ===== */
        (function() {
            const LOGIN_URL = <?php echo json_encode(app_rel_url('account/login.php')); ?>;

            function redirectToLogin() {
                const back = location.pathname + location.search + location.hash;
                location.href = LOGIN_URL + "?redirect=" + encodeURIComponent(back);
            }

            // 1) Vá mọi anchor "quick-add" cũ trong overlay (nếu có) thành button
            document.addEventListener("DOMContentLoaded", () => {
                document.querySelectorAll(".product-action-grid a").forEach((a) => {
                    a.setAttribute("href", "#");
                    a.setAttribute("role", "button");
                });
            });

            // 2) Bắt mọi click vào các nút thêm giỏ trong overlay/card (trang chủ & toàn site)
            document.addEventListener(
                "click",
                function(e) {
                    const btn = e.target.closest(
                        ".btn-add-cart, .btn-cart, [data-add-to-cart], .js-add-to-cart, a.quick-add"
                    );
                    if (!btn) return;

                    e.preventDefault();
                    if (typeof e.stopImmediatePropagation === "function")
                        e.stopImmediatePropagation();

                    // Chưa đăng nhập -> ép đăng nhập
                    if (!window.AUTH?.loggedIn) {
                        alert("Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng!");
                        redirectToLogin();
                        return;
                    }

                    // Đã đăng nhập -> thêm vào giỏ
                    const id =
                        btn.dataset.id ||
                        btn.getAttribute("data-id") ||
                        ((btn.href || "").includes("id=") ?
                            new URL(btn.href, location.origin).searchParams.get("id") :
                            "");

                    if (!id) return;

                    window.SVStore?.addToCart?.(id, 1);
                    window.dispatchEvent(new CustomEvent("cart:changed"));
                    window.SVUI?.updateCartCount?.();

                    // Phản hồi nhanh trên nút
                    const prev = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa fa-check"></i> Đã thêm';
                    setTimeout(() => {
                        btn.disabled = false;
                        btn.innerHTML = prev;
                    }, 900);
                },
                true
            );

            // 3) Chặn form mua nhanh (nếu có) khi chưa đăng nhập
            document.addEventListener(
                "submit",
                function(e) {
                    const form = e.target.closest("#buyForm, .js-buy-form");
                    if (!form) return;
                    if (!window.AUTH?.loggedIn) {
                        e.preventDefault();
                        if (typeof e.stopImmediatePropagation === "function")
                            e.stopImmediatePropagation();
                        redirectToLogin();
                    }
                },
                true
            );

            // 4) Đồng bộ badge giỏ khi tab khác thay đổi
            function updateBadge() {
                window.SVUI?.updateCartCount?.();
            }
            window.addEventListener("storage", (e) => {
                if (e.key && e.key.startsWith("sv_cart_user_")) updateBadge();
            });
            window.addEventListener("cart:changed", updateBadge);
        })(window, document);
    </script>
    <script>
        (function() {
            let tm = null;

            function scheduleRefresh() {
                if (tm) return; // chống spam
                tm = setTimeout(() => {
                    tm = null;
                    try {
                        // 1) Trang listing dùng SVUI.renderProducts
                        if (window.SVUI && typeof SVUI.renderProducts === "function") {
                            SVUI.renderProducts();
                            return;
                        }
                        // 2) Một số trang của bạn dùng window.render()
                        if (typeof window.render === "function") {
                            window.render();
                            return;
                        }
                        // 3) Trang chi tiết nếu có module riêng
                        if (
                            window.SVProductDetail &&
                            typeof SVProductDetail.render === "function"
                        ) {
                            SVProductDetail.render();
                            return;
                        }
                    } catch (e) {
                        console.warn("refresh error:", e);
                    }
                    // 4) Fallback an toàn nếu không có hàm render nào
                    try {
                        location.reload();
                    } catch {}
                }, 100);
            }

            window.addEventListener("storage", (e) => {
                if (!e || !e.key) return;
                if (
                    e.key === "SV_PRODUCTS" ||
                    e.key === "sv_products_v1" ||
                    e.key === "SV_PRODUCTS_UPDATED_AT"
                ) {
                    scheduleRefresh();
                }
            });
        })(window, document);
    </script>
    <!-- cuối body, sau các script khác -->
    <script src="./assets/js/auth.kick.guard.js"></script>
</body>

</html>

