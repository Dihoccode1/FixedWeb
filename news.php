<?php
require_once __DIR__ . '/includes/common.php';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tin tức</title>

    <link rel="stylesheet" href="./bootstrap-4.6.2-dist/css/bootstrap.css" crossorigin="anonymous">
    <link rel="stylesheet" href="./fontawesome-free-6.7.2-web/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="./assets/css/normalize.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="<?php echo h(asset_versioned_url('./assets/css/style.css')); ?>">
    <link rel="stylesheet" href="./assets/css/base.css">

    <style>
        img {
            max-width: 100%;
            display: block
        }
        
        a {
            color: #0d6efd;
            text-decoration: none;
            transition: color .2s ease
        }
        
        .container {
            max-width: 1140px;
            margin: 0 auto;
            padding: 0 14px;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -8px
        }
        
        .col {
            padding: 0 8px
        }
        /* Grid sẵn có */
        
        .col-xs-12 {
            flex: 0 0 100%;
            max-width: 100%
        }
        
        .col-md-9 {
            flex: 0 0 75%;
            max-width: 75%
        }
        
        .col-md-3 {
            flex: 0 0 25%;
            max-width: 25%
        }
        
        .col-md-push-3 {
            order: 2
        }
        
        .col-md-pull-9 {
            order: 1
        }
        
        @media (max-width:991px) {
            .col-md-9,
            .col-md-3 {
                flex: 0 0 100%;
                max-width: 100%
            }
            .col-md-push-3,
            .col-md-pull-9 {
                order: initial
            }
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
        /* ===== BREADCRUMB ===== */
        
        .bread-crumb {
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
            background: transparent;
        }
        
        .breadcrumb {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            background: transparent!important;
        }
        
        .breadcrumb li {
            color: #999;
            display: flex;
            align-items: center;
        }
        
        .breadcrumb li.home a {
            font-weight: 600;
            color: #666;
        }
        
        .breadcrumb li.home a:hover {
            color: #111;
        }
        
        .breadcrumb .sep {
            color: #ddd;
        }
        
        .breadcrumb li strong span {
            color: #111;
            font-weight: 600;
        }
        
        .page_title {
            font-size: 28px;
            margin: 20px 0 18px;
            color: #111;
            font-weight: 700;
        }
        /* ===== MAIN CONTENT ===== */
        
        .right-content {
            margin-bottom: 40px;
        }
        
        .list-blogs .row {
            margin-top: 0;
        }
        
        .blog_xxx {
            padding: 0 8px;
            margin-bottom: 20px;
        }
        
        .blog-item {
            display: flex;
            gap: 16px;
            padding: 0;
            border: none;
            border-radius: 14px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: transform .25s ease, box-shadow .25s ease;
        }
        
        .blog-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
        }
        
        .blog-item-thumbnail {
            flex: 0 0 200px;
            max-width: 200px;
            min-height: 200px;
        }
        
        .blog-item-thumbnail .thumb {
            display: block;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        
        .blog-item-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 0;
            transition: transform .3s ease;
        }
        
        .blog-item-thumbnail img:hover {
            transform: scale(1.04);
        }
        
        .blog-item-info {
            flex: 1 1 auto;
            min-width: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        
        .blog-item-name {
            margin: 0 0 10px;
            font-size: 18px;
            line-height: 1.4;
            font-weight: 700;
        }
        
        .blog-item-name a {
            color: #111;
        }
        
        .blog-item-name a:hover {
            color: #0d6efd;
        }
        
        .date {
            display: flex;
            gap: 12px;
            align-items: center;
            color: #999;
            font-size: 13px;
            margin-bottom: 10px;
        }
        
        .post-time,
        .news_home_content_short_time {
            white-space: nowrap;
        }
        
        .blog-item-summary {
            margin: 0 0 14px;
            color: #666;
            line-height: 1.6;
            font-size: 14px;
            flex: 1 1 auto;
        }
        
        .news_post_loop_more .btn {
            display: inline-block;
            padding: 9px 16px;
            border-radius: 8px;
            background: #f5f5f5;
            color: #111;
            font-size: 13px;
            border: 1px solid #e5e7eb;
            transition: background .2s ease, color .2s ease, border-color .2s ease;
            font-weight: 600;
            align-self: flex-start;
        }
        
        .news_post_loop_more .btn:hover {
            background: #111;
            color: #fff;
            border-color: #111;
        }
        
        @media (max-width:767px) {
            .blog-item {
                flex-direction: column;
            }
            .blog-item-thumbnail {
                flex: 0 0 auto;
                max-width: 100%;
                min-height: 180px;
            }
            .blog-item-thumbnail img {
                height: 180px;
            }
        }
        /* ===== SIDEBAR ===== */
        
        .left-content .aside-item {
            margin-bottom: 20px;
        }
        
        .aside-title {
            margin: 0 0 14px;
        }
        
        .aside-title .title-head {
            font-size: 16px;
            margin: 0;
            display: inline-block;
            border: none;
            border-bottom: 2px solid #111;
            padding: 0 0 6px;
            font-weight: 700;
            color: #111;
        }
        
        .aside-title .title-head span a {
            color: #111;
        }
        
        .aside-title .title-head span a:hover {
            color: #0d6efd;
        }
        
        .blog-list .loop-blog {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .blog-list .loop-blog:last-child {
            border-bottom: none;
        }
        
        .blog-list h3 {
            font-size: 13px;
            margin: 0 0 6px;
            font-weight: 700;
            color: #111;
            line-height: 1.5;
        }
        
        .blog-list .date {
            font-size: 12px;
            color: #999;
        }
        
        .blog-aside a {
            text-decoration: none;
            color: #333;
        }
        
        .blog-aside a:hover {
            color: #0d6efd;
        }
        
        .banner_blogs .widget {
            margin-top: 20px;
        }
        
        .banner_blogs .widget img {
            width: 100%;
            height: auto;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }
        
        .margin-bottom-50 {
            margin-bottom: 50px
        }
        
        .text-center {
            text-align: center
        }
        
        .hidden-xs,
        .hidden-sm {
            display: block
        }
        
        @media (max-width:767px) {
            .hidden-xs {
                display: none
            }
        }
        
        @media (min-width:768px) and (max-width:991px) {
            .hidden-sm {
                display: none
            }
        }
        /* ====== 30/70 layout mới ====== */
        
        @media (min-width:992px) {
            .col-30 {
                flex: 0 0 30%;
                max-width: 30%
            }
            .col-70 {
                flex: 0 0 70%;
                max-width: 70%
            }
            .order-lg-1 {
                order: 1
            }
            .order-lg-2 {
                order: 2
            }
        }
        /* Khoảng cách giữa 2 cột */
        
        .sidebar-pad {
            padding-right: 24px
        }
        
        .content-pad {
            padding-left: 24px
        }
        /* Đảm bảo sidebar full trong cột */
        
        .sidebar-pad .col-md-3,
        .sidebar-pad .col-md-pull-9 {
            flex: 0 0 100%!important;
            max-width: 100%!important;
            order: initial!important
        }
    </style>
    <link rel="stylesheet" href="./assets/css/clean-styles.css" />
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
                        <img src="./assets/images/logo.jpg" alt="GENTLEMAN">
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
            <li><a href="./Product/product.php" class="js-products-url">SẢN PHẨM</a></li>
            <li><a href="./news.php">TIN TỨC</a></li>
            <li><a href="./contact.php">LIÊN HỆ</a></li>
        </ul>
    </nav>

    <script>
        (function(w, d) {
            // Lấy URL trang sản phẩm từ menu (ưu tiên .js-products-url)
            function getProductsURL() {
                var a = d.querySelector('nav.main-nav a.js-products-url') ||
                    d.querySelector('nav.main-nav a[href*="/Product"]');
                return (a && a.getAttribute('href')) ? a.getAttribute('href') : './Product/product.php';
            }

            var form = d.querySelector('.search-bar');
            if (!form) return;

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                var input = form.querySelector('input[name="query"], input[name="q"]');
                var q = (input && input.value || '').trim().replace(/\s+/g, ' ');

                var raw = getProductsURL();
                var base = raw.split('?')[0].split('#')[0];

                var href = q ? base + '?q=' + encodeURIComponent(q) : base;

                // GIỮ LINK TƯƠNG ĐỐI
                w.location.href = href;
            }, {
                passive: false
            });

        })(window, document);
    </script>
    <script>
        (function(w, d) {
            // gọi sau khi auth sẵn sàng
            function makeWelcomeClickable() {
                // ưu tiên phần tử của header mới
                var el = d.querySelector('.welcome-user');
                if (el) {
                    // đã là <a>, đảm bảo đúng href
                    el.setAttribute('href', './account/profile.php');
                    return;
                }

                // các trường hợp site cũ: #welcomeName, [data-welcome-name], .js-welcome-name
                var target = d.querySelector('#welcomeName, [data-welcome-name], .js-welcome-name');
                if (!target) return;

                // nếu chưa phải <a>, bọc lại thành <a>
                if (target.tagName !== 'A') {
                    var a = d.createElement('a');
                    a.href = './account/profile.php';
                    a.className = 'welcome-link';
                    // chuyển toàn bộ nội dung vào <a>
                    while (target.firstChild) a.appendChild(target.firstChild);
                    target.appendChild(a);
                } else {
                    // đã là <a> thì gán href đúng
                    target.setAttribute('href', './account/profile.php');
                }
            }

            // chạy khi auth ready, và dự phòng nếu auth đã sẵn sàng
            d.addEventListener('auth:ready', makeWelcomeClickable);
            if (w.AUTH && w.AUTH.ready) makeWelcomeClickable();
        })(window, document);
    </script>



    <!-- BREADCRUMB -->
    <section class="bread-crumb">
        <span class="crumb-border"></span>
        <div class="container">
            <div class="row">
                <div class="col-xs-12">
                    <ul class="breadcrumb">
                        <li class="home">
                            <a href="./index.php"><span>Trang chủ</span></a>
                            <span class="mr_lr">&nbsp;<i class="fa fa-angle-right"></i>&nbsp;</span>
                        </li>
                        <li><strong><span>Tin tức</span></strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- PAGE CONTENT -->
    <div class="container" itemscope itemtype="">
        <meta itemprop="name" content="Tin tức">
        <meta itemprop="description" content="Chào mừng quý khách đến với Gentleman - website mua sắm thời trang hàng đầu Việt Nam. Ở Gentleman, quý khách sẽ có những trải nghiệm mua sắm vô cùng thú vị!">

        <div class="row" id="latest-news">
            <div class="box-heading col-xs-12">
                <h1 class="inline-clean-46">Tin tức</h1>
            </div>

            <!-- SIDEBAR (30%) -->
            <aside class="col col-30 order-lg-1 sidebar-pad" id="sidebar-latest">
                <style>
                    /* Sidebar ăn full chiều rộng cột 50% bên ngoài */
                    
                    .sidebar-box {
                        width: 100%;
                        padding: 0 20px;
                    }
                    /* Item trong sidebar gọn gàng */
                    
                    .sidebar-box .loop-blog {
                        padding: 12px 0;
                        border-bottom: 1px dashed #e9e9e9;
                    }
                    
                    .sidebar-box .loop-blog:last-child {
                        border-bottom: none;
                    }
                    
                    .sidebar-box .name-right h3 {
                        font-size: 16px;
                        line-height: 1.4;
                        margin: 0 0 6px;
                    }
                    
                    .sidebar-box .date {
                        font-size: 13px;
                        color: #999;
                    }
                    /* Banner responsive + khoảng cách */
                    
                    .banner_blogs .widget {
                        margin-top: 16px;
                    }
                    
                    .banner_blogs img {
                        max-width: 100%;
                        height: auto;
                        display: block;
                        border-radius: 6px;
                    }
                    
                    @media (min-width: 992px) {
                        .sidebar-box {
                            padding: 0 24px;
                        }
                        /* nhích “sang 1 xíu” cho thoáng ở desktop */
                    }
                </style>

                <aside class="blog_hai left left-content sidebar-box">
                    <div class="blog-aside aside-item">
                        <div>
                            <div class="aside-title">
                                <h2 class="title-head inline-clean-47">
                                    <span><a href="./news.php" title="Bài viết mới nhất">Bài viết mới nhất</a></span>
                                </h2>
                            </div>

                            <div class="aside-content">
                                <div class="blog-list blog-image-list">

                                    <div class="loop-blog">
                                        <div class="name-right">
                                            <h3>
                                                <a href="./News_Section/gold_digger.php" target="_blank">
                  Sáp Vuốt Tóc By Vilain Gold Digger – Giữ nếp mạnh, không bóng
                </a>
                                            </h3>
                                            <div class="date">29/09/2025</div>
                                        </div>
                                    </div>

                                    <div class="loop-blog">
                                        <div class="name-right">
                                            <h3><a href="./News_Section/tutorial.php">Hướng dẫn sử dụng sáp vuốt tóc đúng cách</a></h3>
                                            <div class="date">29/09/2025</div>
                                        </div>
                                    </div>

                                    <div class="loop-blog">
                                        <div class="name-right">
                                            <h3>
                                                <a href="./News_Section/hint.php">
                  Gợi ý loại sáp vuốt tóc nam tốt nhất nên dùng thử một lần trong đời
                </a>
                                            </h3>
                                            <div class="date">29/09/2025</div>
                                        </div>
                                    </div>

                                    <div class="loop-blog">
                                        <div class="name-right">
                                            <h3>
                                                <a href="./News_Section/maintenance.php">
                  Cách bảo quản sáp vuốt tóc để dùng lâu và hiệu quả
                </a>
                                            </h3>
                                            <div class="date">29/09/2025</div>
                                        </div>
                                    </div>

                                    <div class="loop-blog">
                                        <div class="name-right">
                                            <h3><a href="./News_Section/distinguish.php">Cách phân biệt sáp vuốt tóc thật và giả</a></h3>
                                            <div class="date">29/09/2025</div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Banners -->
                    <div class="banner_blogs d-none d-md-block">
                        <div class="widget banner-sidebar-1">
                            <a href="#" title="Banner blog 1">
                                <img loading="lazy" src="./assets/images/bray.png" alt="THE MAN">
                            </a>
                        </div>
                        <div class="widget banner-sidebar-2">
                            <a href="#" title="Banner blog 2">
                                <img loading="lazy" src="./assets/images/ship.jpg" alt="THE MAN">
                            </a>
                        </div>
                    </div>
                </aside>
            </aside>

            <!-- MAIN (70%) -->
            <section class="right-content margin-bottom-50 col col-70 order-lg-2 content-pad">
                <section class="list-blogs blog-main">
                    <div class="row">

                        <!-- Item 1 -->
                        <div class="col col-xs-12 blog_xxx">
                            <article class="blog-item">
                                <div class="blog-item-thumbnail">
                                    <a class="thumb" href="./News_Section/gold_digger.php" target="_blank" title="Sáp Vuốt Tóc By Vilain Gold Digger">
                                        <img loading="lazy" src="./assets/images/OIP.webp" alt="Sáp Vuốt Tóc By Vilain Gold Digger">
                                    </a>
                                </div>
                                <div class="blog-item-info">
                                    <h3 class="blog-item-name">
                                        <a href="./News_Section/gold_digger.php" target="_blank" title="Sáp Vuốt Tóc By Vilain Gold Digger">
                      Sáp Vuốt Tóc By Vilain Gold Digger – Giữ nếp mạnh, không bóng
                    </a>
                                    </h3>
                                    <div class="date">
                                        <div class="post-time">Đăng ngày&nbsp;29/09/2025</div>
                                        <div class="news_home_content_short_time">bởi <span>Nobility</span></div>
                                    </div>
                                    <p class="blog-item-summary">
                                        By Vilain Gold Digger là dòng sáp cao cấp đến từ Đan Mạch, nổi bật với khả năng giữ nếp siêu mạnh (Strong Hold) và độ bóng 0% cho mái tóc tự nhiên. Sản phẩm phù hợp cho mọi kiểu tóc từ Undercut, Side Part, đến Pompadour, giúp anh em tự tin cả ngày dài.
                                    </p>
                                    <div class="news_post_loop_more">
                                        <a class="btn btn-primary" target="_blank" href="./News_Section/gold_digger.php" title="Xem chi tiết">Xem chi tiết</a>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <!-- Item 2 -->
                        <div class="col col-xs-12 blog_xxx">
                            <article class="blog-item">
                                <div class="blog-item-thumbnail">
                                    <a class="thumb" href="./News_Section/tutorial.php" target="_blank" title="Hướng dẫn sử dụng sáp vuốt tóc">
                                        <img loading="lazy" src="./assets/images/huong_dan.jpg" alt="Hướng dẫn sử dụng sáp vuốt tóc">
                                    </a>
                                </div>
                                <div class="blog-item-info">
                                    <h3 class="blog-item-name">
                                        <a href="./News_Section/tutorial.php" target="_blank" title="Hướng dẫn sử dụng sáp vuốt tóc">
                      Hướng dẫn sử dụng sáp vuốt tóc đúng cách
                    </a>
                                    </h3>
                                    <div class="date">
                                        <div class="post-time">Đăng ngày&nbsp;29/09/2025</div>
                                        <div class="news_home_content_short_time">bởi <span>Nobility</span></div>
                                    </div>
                                    <p class="blog-item-summary">
                                        Để có một kiểu tóc đẹp và giữ nếp lâu, việc sử dụng sáp đúng cách rất quan trọng. Lấy một lượng sáp vừa đủ, xoa đều ra lòng bàn tay, sau đó vuốt từ chân tóc đến ngọn để tạo form mong muốn. Hãy nhớ rằng dùng quá nhiều sáp có thể khiến tóc bết dính. Xem
                                        ngay hướng dẫn chi tiết để tối ưu hiệu quả tạo kiểu!
                                    </p>
                                    <div class="news_post_loop_more">
                                        <a class="btn btn-primary" href="./News_Section/tutorial.php" target="_blank" title="Xem chi tiết">Xem chi tiết</a>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <!-- Item 3 -->
                        <div class="col col-xs-12 blog_xxx">
                            <article class="blog-item">
                                <div class="blog-item-thumbnail">
                                    <a class="thumb" href="./News_Section/hint.php" target="_blank" title="Gợi ý loại sáp vuốt tóc nam nên dùng thử một lần trong đời">
                                        <img loading="lazy" src="./assets/images/dung1lan.jpg" alt="Gợi ý loại sáp vuốt tóc nam nên dùng thử">
                                    </a>
                                </div>
                                <div class="blog-item-info">
                                    <h3 class="blog-item-name">
                                        <a href="./News_Section/hint.php" target="_blank" title="Gợi ý loại sáp vuốt tóc nam nên dùng thử một lần trong đời">
                      Gợi ý loại sáp vuốt tóc nam tốt nhất nên dùng thử một lần trong đời
                    </a>
                                    </h3>
                                    <div class="date">
                                        <div class="post-time">Đăng ngày&nbsp;29/09/2025</div>
                                        <div class="news_home_content_short_time">bởi <span>Nobility</span></div>
                                    </div>
                                    <p class="blog-item-summary">
                                        Trên thị trường có rất nhiều loại sáp với chất lượng và mức giá khác nhau. Tuy nhiên, một số dòng sáp cao cấp như By Vilain Gold Digger, Clay Wax hay Morris Motley luôn được giới trẻ ưa chuộng nhờ khả năng giữ nếp mạnh mẽ, mùi hương dễ chịu và không gây
                                        bết dính. Đây là những lựa chọn mà bạn nên thử ít nhất một lần trong đời.
                                    </p>
                                    <div class="news_post_loop_more">
                                        <a class="btn btn-primary" href="./News_Section/hint.php" target="_blank" title="Xem chi tiết">Xem chi tiết</a>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <!-- Item 4 -->
                        <div class="col col-xs-12 blog_xxx">
                            <article class="blog-item">
                                <div class="blog-item-thumbnail">
                                    <a class="thumb" href="./News_Section/maintenance.php" target="_blank" title="Cách bảo quản sáp vuốt tóc">
                                        <img loading="lazy" src="./assets/images/cach_bao_quan.jpg" alt="Cách bảo quản sáp vuốt tóc">
                                    </a>
                                </div>
                                <div class="blog-item-info">
                                    <h3 class="blog-item-name">
                                        <a href="./News_Section/maintenance.php" target="_blank" title="Cách bảo quản sáp vuốt tóc">
                      Cách bảo quản sáp vuốt tóc để dùng lâu và hiệu quả
                    </a>
                                    </h3>
                                    <div class="date">
                                        <div class="post-time">Đăng ngày&nbsp;29/09/2025</div>
                                        <div class="news_home_content_short_time">bởi <span>Nobility</span></div>
                                    </div>
                                    <p class="blog-item-summary">
                                        Để sáp luôn giữ được chất lượng, anh em nên bảo quản sản phẩm nơi khô ráo, thoáng mát, tránh ánh nắng trực tiếp và nhiệt độ quá cao. Sau khi sử dụng, hãy đóng chặt nắp hộp để tránh sáp bị khô hoặc bay mùi. Với cách bảo quản đúng, một hộp sáp có thể dùng
                                        được rất lâu.
                                    </p>
                                    <div class="news_post_loop_more">
                                        <a class="btn btn-primary" href="./News_Section/maintenance.php" target="_blank" title="Xem chi tiết">Xem chi tiết</a>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <!-- Item 5 -->
                        <div class="col col-xs-12 blog_xxx">
                            <article class="blog-item">
                                <div class="blog-item-thumbnail">
                                    <a class="thumb" href="./News_Section/distinguish.php" target="_blank" title="Cách phân biệt sáp vuốt tóc thật và giả">
                                        <img loading="lazy" src="./assets/images/phan_biet.jpg" alt="Cách phân biệt sáp vuốt tóc thật và giả">
                                    </a>
                                </div>
                                <div class="blog-item-info">
                                    <h3 class="blog-item-name">
                                        <a href="./News_Section/distinguish.php" target="_blank" title="Cách phân biệt sáp vuốt tóc thật và giả">
                      Cách phân biệt sáp vuốt tóc thật và giả
                    </a>
                                    </h3>
                                    <div class="date">
                                        <div class="post-time">Đăng ngày&nbsp;29/09/2025</div>
                                        <div class="news_home_content_short_time">bởi <span>Nobility</span></div>
                                    </div>
                                    <p class="blog-item-summary">
                                        Trên thị trường hiện nay xuất hiện rất nhiều loại sáp vuốt tóc kém chất lượng. Để phân biệt sáp thật và giả, anh em cần chú ý đến bao bì, tem chống giả, mùi hương và chất sáp bên trong. Sáp chính hãng thường có mùi dịu nhẹ, chất sáp mịn và dễ tan khi
                                        xoa đều. Hãy mua tại cửa hàng uy tín để tránh rủi ro.
                                    </p>
                                    <div class="news_post_loop_more">
                                        <a class="btn btn-primary" href="./News_Section/distinguish.php" target="_blank" title="Xem chi tiết">Xem chi tiết</a>
                                    </div>
                                </div>
                            </article>
                        </div>

                    </div>
                </section>

                <div class="row">
                    <div class="col col-xs-12 text-center">
                        <!-- Phân trang nếu cần -->
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="ab-module-article-mostview"></div>
    <footer class="footer">
        <div class="site-footer">
            <div class="top-footer">
                <div class="container">
                    <div class="row">
                        <section class="widget-ft">
                            <h4 class="title-menu">Thông tin</h4>
                            <ul class="list-menu">
                                <li class="li_menu"><a href="./index.php">Trang chủ</a></li>
                                <li class="li_menu"><a href="./about.php">Giới thiệu</a></li>
                                <li class="li_menu"><a href="./Product/product.php">Sản phẩm</a></li>
                                <li class="li_menu"><a href="./news.php">Tin tức</a></li>
                                <li class="li_menu"><a href="./contact.php">Liên hệ</a></li>
                            </ul>
                        </section>

                        <section class="widget-ft">
                            <h4 class="title-menu">Hỗ trợ</h4>
                            <ul class="list-menu">
                                <li class="li_menu"><a href="./index.php">Trang chủ</a></li>
                                <li class="li_menu"><a href="./about.php">Giới thiệu</a></li>
                                <li class="li_menu"><a href="./Product/product.php">Sản phẩm</a></li>
                                <li class="li_menu"><a href="./news.php">Tin tức</a></li>
                                <li class="li_menu"><a href="./contact.php">Liên hệ</a></li>
                            </ul>
                        </section>

                        <section class="widget-ft">
                            <h4 class="title-menu">Hướng dẫn</h4>
                            <ul class="list-menu">
                                <li class="li_menu"><a href="./index.php">Trang chủ</a></li>
                                <li class="li_menu"><a href="./about.php">Giới thiệu</a></li>
                                <li class="li_menu"><a href="./Product/product.php">Sản phẩm</a></li>
                                <li class="li_menu"><a href="./news.php">Tin tức</a></li>
                                <li class="li_menu"><a href="./contact.php">Liên hệ</a></li>
                            </ul>
                        </section>

                        <section class="widget-ft">
                            <h4 class="title-menu">Chính sách</h4>
                            <ul class="list-menu">
                                <li class="li_menu"><a href="./index.php">Trang chủ</a></li>
                                <li class="li_menu"><a href="./about.php">Giới thiệu</a></li>
                                <li class="li_menu"><a href="./Product/product.php">Sản phẩm</a></li>
                                <li class="li_menu"><a href="./news.php">Tin tức</a></li>
                                <li class="li_menu"><a href="./contact.php">Liên hệ</a></li>
                            </ul>
                        </section>

                        <section class="wg-logo">
                            <h4 class="title-menu">Liên hệ</h4>
                            <ul class="contact">
                                <li>
                                    <span class="txt_content_child">
                   <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i></span> 140B-Tổ 3, Ấp Xóm Chùa,Tỉnh Tây Ninh
                                    </span>
                                </li>
                                <li class="sdt">
                                    <span><i class="fa-solid fa-phone" aria-hidden="true"></i></span>
                                    <a href="tel:0338286525">0338286525</a>
                                </li>
                                <li class="sdt">
                                    <span><i class="fa-solid fa-envelope"></i></span>
                                    <a href="mailto:thanhloc29052006@gmail.com">thanhloc29052006@gmail.com</a>
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
                            <span class="wsp"><span>Cung cấp bởi <a href="javascript:;">Nhóm 7</a></span></span>
                        </div>
                        <nav class="fot_menu_copyright">
                            <ul class="ul_menu_fot">
                                <li><a href="./index.php" title="Trang chủ">Trang chủ</a></li>
                                <li><a href="./about.php" title="Giới thiệu">Giới thiệu</a></li>
                                <li><a href="./Product/product.php" title="Sản phẩm">Sản phẩm</a></li>
                                <li><a href="./news.php" title="Tin tức">Tin tức</a></li>
                                <li><a href="./contact.php" title="Liên hệ">Liên hệ</a></li>
                            </ul>
                        </nav>
                        <div class="pay_footer">
                            <ul class="follow_option">
                                <li>
                                    <a href="#"><img src="./assets/images/pay_1.webp" alt="Payment"></a>
                                </li>
                                <li>
                                    <a href="#"><img src="./assets/images/pay_2.webp" alt="Payment"></a>
                                </li>
                                <li>
                                    <a href="#"><img src="./assets/images/pay_3.webp" alt="Payment"></a>
                                </li>
                                <li>
                                    <a href="#"><img src="./assets/images/pay_4.webp" alt="Payment"></a>
                                </li>
                                <li>
                                    <a href="#"><img src="./assets/images/pay_5.webp" alt="Payment"></a>
                                </li>
                                <li>
                                    <a href="#"><img src="./assets/images/pay_6.webp" alt="Payment"></a>
                                </li>
                                <li>
                                    <a href="#"><img src="./assets/images/pay_7.webp" alt="Payment"></a>
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

    <script src="<?php echo h(asset_versioned_url('./assets/js/auth.js')); ?>"></script>
    <script src="./assets/js/auth.security.enforcer.js"></script>
    <script src="<?php echo h(asset_versioned_url('./assets/js/auth.modal.bridge.js')); ?>"></script>

    <script src="./assets/js/products.seed.js"></script>
    <script src="<?php echo h(asset_versioned_url('./assets/js/store.js')); ?>"></script>
    <script src="<?php echo h(asset_versioned_url('./assets/js/ui.js')); ?>"></script>

</body>

</html>

<!-- (Tùy chọn) CSS ngoài cho module reviews -->
<link rel="preload" as="style" href="./assets/css/bpr-products-module.css">
<link rel="stylesheet" href="./assets/css/bpr-products-module.css" />
<div class="bizweb-product-reviews-module"></div>
<script>
    (function() {
        function isMobile() {
            return window.matchMedia("(max-width: 767.98px)").matches;
        }

        function setInitialState() {
            document.querySelectorAll(".site-footer .widget-ft").forEach((box, i) => {
                // Mặc định: đóng hết, riêng cột cuối "Liên hệ" mở sẵn (tuỳ bạn)
                if (isMobile()) {
                    if (box.querySelector(".title-menu")?.textContent.trim().toLowerCase().includes("liên hệ")) {
                        box.classList.add("is-open");
                    } else {
                        box.classList.remove("is-open");
                    }
                } else {
                    box.classList.add("is-open"); // PC luôn mở
                }
            });
        }

        // Toggle khi bấm tiêu đề (chỉ mobile)
        function bindClick() {
            document.querySelectorAll(".site-footer .widget-ft .title-menu").forEach(title => {
                title.addEventListener("click", () => {
                    if (!isMobile()) return; // PC không toggle
                    const box = title.closest(".widget-ft");
                    if (!box) return;
                    box.classList.toggle("is-open");
                });
            });
        }

        // Khởi tạo
        document.addEventListener("DOMContentLoaded", () => {
            setInitialState();
            bindClick();
        });
        // Đổi trạng thái khi resize giữa mobile/PC
        window.addEventListener("resize", setInitialState);
    })(window, document);
</script>
<script src="<?php echo h(asset_versioned_url('./assets/js/auth.js')); ?>"></script>
<script src="<?php echo h(asset_versioned_url('./assets/js/auth.modal.bridge.js')); ?>"></script>

<script src="<?php echo h(asset_versioned_url('./assets/js/products.seed.js')); ?>"></script>
<script src="<?php echo h(asset_versioned_url('./assets/js/store.js')); ?>"></script>
<script src="<?php echo h(asset_versioned_url('./assets/js/ui.js')); ?>"></script>
    <script src="<?php echo h(asset_versioned_url('./assets/js/products.app.js')); ?>"></script>
<!-- cuối body, sau các script khác -->
<script src="<?php echo h(asset_versioned_url('./assets/js/auth.kick.guard.js')); ?>"></script>

</body>

</html>

</body>

</html>
