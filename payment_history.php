<?php
require_once __DIR__ . '/includes/common.php';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Lịch sử Mua hàng</title>

    <!-- Vendor CSS -->
    <link
      rel="stylesheet"
      href="./fontawesome-free-6.7.2-web/css/all.min.css"
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />
    <link
      rel="stylesheet"
      href="./bootstrap-4.6.2-dist/css/bootstrap.css"
      crossorigin="anonymous"
    />

    <!-- Site CSS -->
    <link rel="stylesheet" href="<?php echo h(asset_versioned_url('./assets/css/style.css')); ?>" />
    <link rel="stylesheet" href="./assets/css/base.css" />

    <style>
      .title {
        margin-top: 30px;
        font-weight: 700;
        text-align: center;
      }
    </style>
  </head>

  <body>
    <?php render_topbar(); ?>

    <!-- MID HEADER -->
    <header class="mid-header">
      <div class="container">
        <div class="header-main">
          <div class="header-left">
            <!-- Search: dùng JS đẩy sang trang Sản phẩm -->
            <form
              action="./Product/product.php"
              method="get"
              class="search-bar"
              autocomplete="off"
            >
              <input
                type="text"
                name="query"
                placeholder="Tìm kiếm"
                autocomplete="off"
                autocapitalize="off"
                autocorrect="off"
                spellcheck="false"
                inputmode="search"
              />
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
              <i class="fa-solid fa-cart-shopping"></i>
              GIỎ HÀNG (<span class="cart-count">0</span>)
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
        <li>
          <a href="./Product/product.php" class="js-products-url">SẢN PHẨM</a>
        </li>
        <li><a href="./news.php">TIN TỨC</a></li>
        <li><a href="./contact.php">LIÊN HỆ</a></li>
      </ul>
    </nav>

    <!-- JS: search header → sanpham?q=... -->
    <script>
      (function (w, d) {
        function getProductsURL() {
          var a =
            d.querySelector("nav.main-nav a.js-products-url") ||
            d.querySelector('nav.main-nav a[href*="product"]');
          return a && a.getAttribute("href")
            ? a.getAttribute("href")
            : "./Product/product.php";
        }

        var form = d.querySelector(".search-bar");
        if (!form) return;

        form.addEventListener(
          "submit",
          function (e) {
            e.preventDefault();
            var input = form.querySelector(
              'input[name="query"], input[name="q"]'
            );
            var q = ((input && input.value) || "").trim().replace(/\s+/g, " ");
            var raw = getProductsURL();
            var url = new URL(raw, w.location.href);
            if (q) url.searchParams.set("q", q);
            else url.searchParams.delete("q");
            w.location.href = url.pathname + (url.search ? url.search : "");
          },
          { passive: false }
        );
      })(window, document);
    </script>

    <!-- JS: welcome name → profile -->
    <script>
      (function (w, d) {
        function makeWelcomeClickable() {
          var el = d.querySelector(".welcome-user");
          if (el) {
            el.setAttribute("href", "./account/profile.php");
            return;
          }
          var target = d.querySelector(
            "#welcomeName, [data-welcome-name], .js-welcome-name"
          );
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

    <!-- PAGE CONTENT -->
    <div class="container mt-5">
      <div class="title">
        <i class="fa-solid fa-clock-rotate-left"></i> Lịch sử Mua hàng của bạn
      </div>

      <table
        class="table table-bordered table-striped"
        id="order-history-table"
      >
        <thead class="thead-dark">
          <tr>
            <th>Mã Đơn hàng</th>
            <th>Ngày đặt</th>
            <th>Tổng tiền</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td colspan="5" class="text-center">Đang tải lịch sử...</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- JS: LIB + CORE + CART (đặt TRONG <body>) -->
    <script src="./assets/js/jquery-3.6.0.min.js"></script>

    <script src="<?php echo h(asset_versioned_url('./assets/js/auth.js')); ?>"></script>
    <script src="./assets/js/auth.security.enforcer.js"></script>
    <script src="./assets/js/auth.localstorage.bridge.js"></script>
    <script src="./assets/js/auth.reactive.guard.v2.js"></script>

    <script src="<?php echo h(asset_versioned_url('./assets/js/store.js')); ?>"></script>
    <script src="<?php echo h(asset_versioned_url('./assets/js/ui.js')); ?>"></script>
    <script src="./assets/js/products.seed.js"></script>
    <script src="<?php echo h(asset_versioned_url('./assets/js/products.app.js')); ?>"></script>

    <script src="./assets/js/cart.js"></script>
  </body>
</html>


