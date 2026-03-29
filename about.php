<?php
require_once __DIR__ . '/includes/common.php';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Giới thiệu - Gentleman</title>

    <!-- Vendor CSS -->
    <link rel="stylesheet" href="./bootstrap-4.6.2-dist/css/bootstrap.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous" />
    <link rel="stylesheet" href="./fontawesome-free-6.7.2-web/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="./assets/css/normalize.min.css" integrity="sha512-NhSC1YmyruXifcj/KFRWoC561YpHpc5Jtzgvbuzx5VozKpWvQ+4nXhPdFgmx8xqexRcpAglTj9sIBWINXa8x5w==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Site CSS -->
    <link rel="stylesheet" href="./assets/css/base.css" />
    <link rel="stylesheet" href="<?php echo h(asset_versioned_url('./assets/css/style.css')); ?>" />

    <style>
        /* =========================
       BASE
       ========================= */
        
        img {
            max-width: 100%;
            display: block;
        }
        
        a {
            text-decoration: none;
        }
        /* =========================
       CONTAINER + GRID NHẸ
       ========================= */
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 16px;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
        }
        
        .col-12 {
            flex: 0 0 100%;
            max-width: 100%;
        }
        /* =========================
       MID-HEADER
       ========================= */
        
        .mid-header {
            background: #fff;
            padding: 10px 0;
        }
        
        .mid-header .header-main {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .header-left {
            flex: 1 1 auto;
        }
        
        .header-center {
            text-align: center;
        }
        
        .header-center .logo img {
            max-height: 50px;
        }
        
        .header-right {
            flex: 1 1 auto;
            display: flex;
            justify-content: flex-end;
        }
        /* Search */
        
        .search-bar {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 25px;
            overflow: hidden;
            max-width: 260px;
        }
        
        .search-bar input {
            border: 0;
            outline: 0;
            padding: 8px 15px;
            font-size: 14px;
            flex: 1;
        }
        
        .search-bar button {
            background: #333;
            color: #fff;
            border: 0;
            padding: 8px 15px;
            cursor: pointer;
        }
        
        .search-bar button:hover {
            background: #555;
        }
        /* Cart */
        
        .cart-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            color: #333;
        }
        
        .cart-link i {
            font-size: 18px;
        }
        /* =========================
       MENU NGANG
       ========================= */
        
        .main-nav {
            background: #fff;
            margin-top: 15px;
        }
        
        .main-nav ul {
            list-style: none;
            display: flex;
            justify-content: center;
            margin: 0;
            padding: 0;
        }
        
        .main-nav li {
            position: relative;
        }
        
        .main-nav a {
            display: block;
            padding: 14px 20px;
            color: #111;
            font-weight: 600;
            transition: .2s;
        }
        
        .main-nav a:hover {
            color: #007bff;
        }
        /* =========================
       BREADCRUMB
       ========================= */
        
        .bread-crumb {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            background: transparent;
        }
        
        .breadcrumb {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 8px;
            font-size: 14px;
        }
        
        .breadcrumb li {
            display: flex;
            align-items: center;
            color: #666;
        }
        
        .breadcrumb li.home a {
            font-weight: 600;
            color: #333;
        }
        
        .breadcrumb .mr_lr i {
            color: #999;
        }
        /* =========================
       PAGE TITLE + NỘI DUNG GIỚI THIỆU
       ========================= */
        
        .page {
            padding: 22px 0 36px;
        }
        
        .page-title .title-head,
        .page-title h1 {
            font-size: 35px;
            margin: 0 0 12px;
        }
        
        .content-page.rte {
            border: 2px solid #333;
            padding: 24px;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.05);
            margin-top: 20px;
        }
        
        .content-page.rte p {
            margin: 10px 0;
            text-align: justify;
            font-size: 15px;
            color: #333;
        }
        
        .content-page.rte p strong {
            display: block;
            font-size: 15.5px;
            text-transform: uppercase;
            color: #111;
        }
        
        /* =========================
       RESPONSIVE
       ========================= */
        
        @media (max-width: 767.98px) {
            .search-bar {
                display: none !important;
            }
            .mid-header .header-main {
                display: grid;
                grid-template-columns: auto 1fr auto;
                gap: 8px;
                align-items: center;
            }
            .header-center .logo img {
                max-height: 40px;
            }
            .cart-link {
                font-size: 13px;
            }
            .btn-logout {
                padding: 5px 8px !important;
                font-size: 11px !important;
                max-width: 70px !important;
            }
        }
    </style>
    <link rel="stylesheet" href="./assets/css/clean-styles.css" />
</head>

<body>
    <!-- TOPBAR -->
    <?php render_topbar(); ?>

    <!-- MID HEADER -->
    <header class="mid-header">
        <div class="container">
            <div class="header-main">
                <div class="header-left">
                    <!-- Search: chặn autofill & gõ dấu ok, đẩy sang trang sản phẩm -->
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

    <!-- MAIN NAV -->
    <nav class="main-nav">
        <ul>
            <li><a href="./index.php">TRANG CHỦ</a></li>
            <li><a href="./about.php">GIỚI THIỆU</a></li>
            <li><a href="./Product/product.php" class="js-products-url">SẢN PHẨM</a></li>
            <li><a href="./news.php">TIN TỨC</a></li>
            <li><a href="./contact.php">LIÊN HỆ</a></li>
        </ul>
    </nav>

    <!-- JS: search header → sanpham?q=... -->
    <script>
        (function(w, d) {
            function getProductsURL() {
                var a = d.querySelector("nav.main-nav a.js-products-url") ||
                    d.querySelector('nav.main-nav a[href*="/Product"]');
                return (a && a.getAttribute("href")) ?
                    a.getAttribute("href") :
                    "./Product/product.php";
            }

            var form = d.querySelector(".search-bar");
            if (!form) return;

            form.addEventListener(
                "submit",
                function(e) {
                    e.preventDefault();
                    var input = form.querySelector('input[name="query"], input[name="q"]');
                    var q = ((input && input.value) || "").trim().replace(/\s+/g, " ");
                    var raw = getProductsURL();
                    var url = new URL(raw, w.location.href);
                    if (q) url.searchParams.set("q", q);
                    else url.searchParams.delete("q");
                    w.location.href = url.pathname + (url.search ? url.search : "");
                }, {
                    passive: false
                }
            );
        })(window, document);
    </script>

    <!-- JS: welcome name → profile -->
    <script>
        (function(w, d) {
            function makeWelcomeClickable() {
                var el = d.querySelector(".welcome-user");
                if (el) {
                    el.setAttribute("href", "./account/profile.php");
                    return;
                }
                var target = d.querySelector("#welcomeName, [data-welcome-name], .js-welcome-name");
                if (!target) return;
                if (target.tagName !== "A") {
                    var a = d.createElement("a");
                    a.href = "./account/profile.php";
                    a.className = "welcome-link";
                    while (target.firstChild) a.appendChild(target.firstChild);
                    target.appendChild(a);
                } else {
                    target.setAttribute("href", "./account/profile.php");
                }
            }
            d.addEventListener("auth:ready", makeWelcomeClickable);
            if (w.AUTH && w.AUTH.ready) makeWelcomeClickable();
        })(window, document);
    </script>

    <!-- ===== BREADCRUMB ===== -->
    <section class="bread-crumb">
        <span class="crumb-border"></span>
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <ul class="breadcrumb">
                        <li class="home">
                            <a href="./index.php"><span>Trang chủ</span></a>
                            <span class="mr_lr">&nbsp;<i class="fa fa-angle-right"></i>&nbsp;</span>
                        </li>
                        <li><strong><span>Giới thiệu</span></strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== PAGE ===== -->
    <section class="page">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="page-title category-title">
                        <h1 class="inline-clean-46">Giới thiệu</h1>
                    </div>

                    <div class="content-page rte intro-box">
                        <p><strong>THE MAN - WEBSITE BÁN SÁP VÀ SẢN PHẨM TẠO KIỂU TÓC CHO NAM HÀNG ĐẦU VIỆT NAM</strong></p>

                        <p>BẠN ĐANG TÌM KIẾM NHỮNG LOẠI SÁP VÀ SẢN PHẨM TẠO KIỂU TÓC NAM GIỮ NẾP LÂU, HƯƠNG THƠM NAM TÍNH VÀ CHÍNH HÃNG? HÃY MUA NGAY HÔM NAY TẠI THE MAN!</p>

                        <p>THE MAN mang lại cho khách hàng trải nghiệm mua sắm hiện đại và tiện lợi với các dòng sáp vuốt tóc nam cao cấp, pomade, gôm xịt tóc, dưỡng tóc và các sản phẩm chăm sóc tóc khác. Chúng tôi cam kết sản phẩm chính hãng, chất lượng
                            cao, giúp bạn tự tin trong mọi phong cách: từ lịch lãm công sở, cá tính đường phố cho tới phong trần, mạnh mẽ. Tất cả đều có tại THE MAN.</p>

                        <p><strong>THE MAN - MÓN QUÀ Ý NGHĨA CHO NAM GIỚI!</strong></p>

                        <p>Nếu bạn muốn dành tặng người thân, bạn bè một món quà thật thiết thực và ý nghĩa, các sản phẩm sáp và pomade tại THE MAN chính là lựa chọn hoàn hảo. Chúng tôi đảm bảo mức giá tốt cùng dịch vụ chăm sóc khách hàng tận tâm.</p>

                        <p><strong>SẢN PHẨM CHÍNH HÃNG - GIÁ HỢP LÝ</strong></p>

                        <p>THE MAN luôn cập nhật những thương hiệu sáp nổi tiếng và được ưa chuộng hàng đầu hiện nay: By Vilain, Blumaan, Hanz de Fuko, Apestomen, Kevin Murphy, và nhiều thương hiệu khác. Ngoài ra, chúng tôi còn có các dòng sản phẩm tầm trung
                            chất lượng cao, phù hợp với nhiều nhu cầu và túi tiền. Đặc biệt, THE MAN thường xuyên có các chương trình khuyến mãi và ưu đãi hấp dẫn.</p>

                        <p><strong>MUA SẮM DỄ DÀNG - THANH TOÁN AN TOÀN</strong></p>

                        <p>Bạn có thể đặt hàng tại THE MAN nhanh chóng và an toàn, chính sách đổi trả linh hoạt trong vòng 7 ngày nếu sản phẩm không như mô tả. Nếu cần tư vấn chọn loại sáp phù hợp, hãy liên hệ ngay với đội ngũ chăm sóc khách hàng của chúng
                            tôi để được hỗ trợ.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
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

    <!-- JS CORE (TRONG body, KHÔNG để ngoài </html>) -->
    <script src="<?php echo h(asset_versioned_url('./assets/js/auth.js')); ?>"></script>
    <script src="./assets/js/auth.security.enforcer.js"></script>
    <script src="<?php echo h(asset_versioned_url('./assets/js/auth.modal.bridge.js')); ?>"></script>

    <script src="./assets/js/products.seed.js"></script>
    <script src="<?php echo h(asset_versioned_url('./assets/js/store.js')); ?>"></script>
    <script src="<?php echo h(asset_versioned_url('./assets/js/ui.js')); ?>"></script>
    <script src="<?php echo h(asset_versioned_url('./assets/js/products.app.js')); ?>"></script>

    <!-- Footer accordion (mobile) -->
    <script>
        (function() {
            function isMobile() {
                return window.matchMedia("(max-width: 767.98px)").matches;
            }

            function setInitialState() {
                document
                    .querySelectorAll(".site-footer .widget-ft")
                    .forEach((box) => {
                        if (isMobile()) {
                            if (
                                box
                                .querySelector(".title-menu") ?
                                .textContent.trim()
                                .toLowerCase()
                                .includes("liên hệ")
                            ) {
                                box.classList.add("is-open");
                            } else {
                                box.classList.remove("is-open");
                            }
                        } else {
                            box.classList.add("is-open");
                        }
                    });
            }

            function bindClick() {
                document
                    .querySelectorAll(".site-footer .widget-ft .title-menu")
                    .forEach((title) => {
                        title.addEventListener("click", () => {
                            if (!isMobile()) return;
                            const box = title.closest(".widget-ft");
                            if (!box) return;
                            box.classList.toggle("is-open");
                        });
                    });
            }

            document.addEventListener("DOMContentLoaded", () => {
                setInitialState();
                bindClick();
            });
            window.addEventListener("resize", setInitialState);
        })(window, document);
    </script>

    <!-- Kick guard (nếu cần ép login/đá session lạ) -->
    <script src="./assets/js/auth.kick.guard.js"></script>
</body>

</html>

