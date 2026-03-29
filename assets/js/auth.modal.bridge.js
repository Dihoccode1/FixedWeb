// ===== CẦU NỐI AUTH <-> UI (KHÔNG MODAL) =====
(function (w, d) {
  "use strict";

  function accountHref(page) {
    var cleanPage = String(page || '').replace(/^\/+/, '');
    var pathname = (location.pathname || "").replace(/\\/g, "/").toLowerCase();
    if (pathname.indexOf("/account/") !== -1) return cleanPage;
    if (pathname.indexOf("/product/pages/") !== -1) return "../../account/" + cleanPage;
    if (pathname.indexOf("/product/") !== -1 || pathname.indexOf("/news_section/") !== -1) return "../account/" + cleanPage;
    return "./account/" + cleanPage;
  }

  function getTopbarAuthUrl(attrName) {
    try {
      var topbarRight = d.querySelector(".topbar-right");
      if (!topbarRight) return "";
      var value = (topbarRight.getAttribute(attrName) || "").trim();
      return normalizeAuthHref(value);
    } catch (_) {
      return "";
    }
  }

  function normalizeAuthHref(value) {
    var v = String(value || '').trim();
    if (!v) return '';
    v = v.replace(/\\/g, '/');
    v = v.replace(/\/acount\//ig, '/account/');
    if (v.indexOf('//') === 0) {
      v = '/' + v.replace(/^\/\/+/, '');
    }
    if (/^\.{1,2}\//.test(v)) {
      return v;
    }
    if (/^[a-z][a-z0-9+.-]*:/i.test(v)) {
      return v;
    }
    v = v.replace(/^\/+/, '/');
    v = v.replace(/([^:])\/\/{2,}/g, '$1/');
    return v;
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (match) {
      return {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
      }[match];
    });
  }

  function renderGuestAuthUI(topbarRight, loginHref, registerHref) {
    if (!topbarRight) return;
    topbarRight.innerHTML =
      '<a href="' + normalizeAuthHref(registerHref) + '" class="btn btn-outline">Đăng ký</a>' +
      '<a href="' + normalizeAuthHref(loginHref) + '" class="btn btn-primary">Đăng nhập</a>';
  }

  function renderLoggedAuthUI(topbarRight, profileHref, logoutHref, userName) {
    if (!topbarRight) return;
    topbarRight.innerHTML =
      '<a href="' + normalizeAuthHref(profileHref) + '" class="welcome-user" style="text-decoration:none;">' +
        '<span class="welcome-message">Xin chào</span>' +
        '<span class="user-name">' + escapeHtml(userName || 'Khách') + '</span>' +
      '</a>' +
      '<a href="' + normalizeAuthHref(logoutHref) + '" class="btn btn-primary">Đăng xuất</a>';
  }

  function updateAuthUI() {
    var topbarRight = d.querySelector('.topbar-right');
    if (!topbarRight) return;

    var loginHref = normalizeAuthHref(getTopbarAuthUrl('data-auth-login-url') || (w.AUTH && w.AUTH.LOGIN_URL) || accountHref('login.php'));
    var registerHref = normalizeAuthHref(getTopbarAuthUrl('data-auth-register-url') || (w.AUTH && w.AUTH.REGISTER_URL) || accountHref('register.php'));
    var logoutHref = normalizeAuthHref(getTopbarAuthUrl('data-auth-logout-url') || accountHref('logout.php'));

    if (w.AUTH && w.AUTH.loggedIn && w.AUTH.user) {
      var profileHref = normalizeAuthHref(getTopbarAuthUrl('data-auth-profile-url') || accountHref('profile.php'));
      var profileAnchor = topbarRight.querySelector('.welcome-user[href], .user-name[href], a[href*="profile.php"]');
      var logoutAnchor = topbarRight.querySelector('a[href*="logout.php"]');
      var hasGuestAnchors = !!topbarRight.querySelector('a[href*="login.php"], a[href*="register.php"]');

      if (!profileAnchor || !logoutAnchor || hasGuestAnchors) {
        renderLoggedAuthUI(topbarRight, profileHref, logoutHref, w.AUTH.user.name || w.AUTH.user.email || 'Khách');
      } else {
        profileAnchor.setAttribute('href', profileHref);
        logoutAnchor.setAttribute('href', logoutHref);
      }
    } else {
      var loginAnchor = topbarRight.querySelector('a[href*="login.php"]');
      var registerAnchor = topbarRight.querySelector('a[href*="register.php"]');
      var mixedMarkup = !!topbarRight.querySelector('.welcome-user, a[href*="logout.php"]');
      var tooManyAnchors = topbarRight.querySelectorAll('a[href]').length > 2;

      if (!loginAnchor || !registerAnchor || mixedMarkup || tooManyAnchors) {
        renderGuestAuthUI(topbarRight, loginHref, registerHref);
      } else {
        loginAnchor.setAttribute('href', loginHref);
        registerAnchor.setAttribute('href', registerHref);
      }
    }

    updateCartBadge();
  }

  function updateCartBadge() {
    const badges = d.querySelectorAll(".cart-count, #cartCount");
    const isCartPage = /\/cart\.php(?:$|[?#])/i.test(location.pathname + location.search);
    const cartLooksEmpty = !!d.querySelector('.alert-warning') && d.querySelectorAll('.cart-item-row').length === 0;
    let count = 0;

    if (typeof w.SERVER_CART_COUNT === 'number') {
      count = Math.max(0, Number(w.SERVER_CART_COUNT) || 0);
    } else {
      count = w.SVStore?.count?.() || 0;
    }

    if (isCartPage && cartLooksEmpty) {
      count = 0;
      window.SERVER_CART_COUNT = 0;
    }
    badges.forEach((el) => {
      if (el) el.textContent = count;
    });
  }

  d.addEventListener("click", function (e) {
    const anchor = e.target.closest("a[href*='logout.php']");
    if (anchor) {
      var href = anchor.getAttribute('href') || '';
      if (href.indexOf('logout.php') !== -1) {
        var url = new URL(href, w.location.href);
        if (!url.searchParams.has('redirect')) {
          url.searchParams.set('redirect', w.location.pathname + w.location.search + w.location.hash);
          anchor.setAttribute('href', url.pathname + url.search);
        }
      }
    }
  });

  d.addEventListener("auth:ready", updateAuthUI);
  d.addEventListener("auth:changed", updateAuthUI);
  d.addEventListener("cart:changed", updateCartBadge);
  w.addEventListener("storage", (e) => {
    if (e.key && e.key.startsWith("sv_cart_user_")) {
      updateCartBadge();
    }
  });

  if (d.readyState !== "loading") {
    w.AUTH?.check?.();
  } else {
    d.addEventListener("DOMContentLoaded", () => w.AUTH?.check?.());
  }
})(window, document);

