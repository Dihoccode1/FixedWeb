// /assets/js/store.js - GIỎ HÀNG THEO TÀI KHOẢN
(function (w) {
  // ===== Helpers =====
  const fmtVND = n =>
    (n || n === 0) ? Number(n).toLocaleString('vi-VN') + '₫' : '';

  const toNumber = v =>
    typeof v === 'number' ? v : Number(String(v).replace(/[^\d]/g, '')) || 0;

  const clampNonNegative = n => {
    const x = toNumber(n);
    return x < 0 ? 0 : x;
  };

  const stripVN = (str = '') =>
    String(str).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

  const normalizeForSearch = (str = '') =>
    stripVN(str).replace(/[^a-z0-9 ]/g, ' ').replace(/\s+/g, ' ').trim();

  function normalizeBadge(p) {
    if (!p || !p.badge) return '';
    const b = String(p.badge).toLowerCase().trim();
    const map = {
      sale: 'sale',
      new: 'new',
      oos: 'out_of_stock',
      out_of_stock: 'out_of_stock'
    };
    return map[b] || '';
  }

  function getAllProducts() {
    try {
      const saved = localStorage.getItem('SV_PRODUCTS');
      if (saved) return JSON.parse(saved);
    } catch (_) {}
    return w.SV_PRODUCT_SEED || [];
  }

  function getProductStockById(id) {
    const list = getAllProducts();
    const product = list.find((p) => String(p.id) === String(id));
    if (!product) return Infinity;
    const raw = Number(product.quantity ?? product.stock ?? product.qty);
    if (!Number.isFinite(raw)) return Infinity;
    return Math.max(0, Math.floor(raw));
  }

  function reconcileLocalAddWithServer(id, requestedQty, data) {
    const asked = Math.max(0, Number(requestedQty) || 0);
    const addedFromServer = Math.max(0, Number(data?.added_qty) || 0);
    const accepted = Math.min(asked, addedFromServer);
    const rejected = Math.max(0, asked - accepted);

    console.log('[SVStore] reconcile: id=' + id + ', requested=' + asked + ', added=' + addedFromServer + ', rejected=' + rejected, 'server_response=', data);

    if (rejected > 0) {
      const cart = getCart();
      const i = cart.findIndex((x) => String(x.id) === String(id));
      if (i > -1) {
        const nextQty = Math.max(0, (Number(cart[i].qty) || 0) - rejected);
        if (nextQty <= 0) cart.splice(i, 1);
        else cart[i].qty = nextQty;
        saveCart(cart);

        lastCartOp = {
          ...lastCartOp,
          addedQty: accepted,
          finalQty: nextQty,
          limitReached: true,
          message: (data && data.message) ? String(data.message) : 'Số lượng đã được giới hạn theo tồn kho.'
        };
        console.log('[SVStore] rejected portion removed, nextQty=' + nextQty);
      }
    } else if (data && typeof data.message === 'string' && data.message.trim()) {
      lastCartOp = {
        ...lastCartOp,
        message: data.message.trim()
      };
    }

    // Chốt cứng theo tổng số lượng do server trả về để tránh lệch +/-1.
    if (data && typeof data.cart_count === 'number') {
      const serverTotal = Math.max(0, Number(data.cart_count) || 0);
      const cart = getCart();
      const localTotal = cart.reduce((s, x) => s + (Number(x.qty) || 0), 0);
      const overflow = Math.max(0, localTotal - serverTotal);

      console.log('[SVStore] total_sync: local=' + localTotal + ', server=' + serverTotal + ', overflow=' + overflow);

      if (overflow > 0) {
        const i = cart.findIndex((x) => String(x.id) === String(id));
        if (i > -1) {
          const nextQty = Math.max(0, (Number(cart[i].qty) || 0) - overflow);
          if (nextQty <= 0) cart.splice(i, 1);
          else cart[i].qty = nextQty;
          saveCart(cart);

          lastCartOp = {
            ...lastCartOp,
            finalQty: nextQty,
            limitReached: true,
            message: (data && data.message) ? String(data.message) : 'Số lượng đã được đồng bộ theo tồn kho thực tế.'
          };
          console.log('[SVStore] overflow removed, nextQty=' + nextQty);
        }
      } else {
        console.log('[SVStore] no overflow, local and server match');
      }
    }
  }

  function filterProducts(list, { q, category, minPrice, maxPrice }) {
    let rs = list.slice();
    if (q && String(q).trim()) {
      const kw = normalizeForSearch(q);
      rs = rs.filter((p) => {
        const nameNorm = normalizeForSearch(p.name || '');
        if (!nameNorm) return false;
        return nameNorm.split(' ').some((word) => word && word.startsWith(kw));
      });
    }
    if (category && category !== 'all') {
      const c = String(category).toLowerCase();
      rs = rs.filter(p => (p.category || '').toLowerCase() === c);
    }
    if (minPrice != null && String(minPrice).trim() !== '') {
      const min = clampNonNegative(minPrice);
      rs = rs.filter(p => toNumber(p.price) >= min);
    }
    if (maxPrice != null && String(maxPrice).trim() !== '') {
      const max = clampNonNegative(maxPrice);
      rs = rs.filter(p => toNumber(p.price) <= max);
    }
    return rs;
  }

  function sortProducts(list, sort) {
    if (!sort) return list;
    const [key, dir] = String(sort).split('-');
    const dirN = dir === 'desc' ? -1 : 1;
    return list.slice().sort((a, b) => {
      if (key === 'price') return (toNumber(a.price) - toNumber(b.price)) * dirN;
      if (key === 'name')  return String(a.name).localeCompare(String(b.name), 'vi') * dirN;
      return 0;
    });
  }

  function paginate(list, page = 1, perPage = 12) {
    const total = list.length;
    const pages = Math.max(1, Math.ceil(total / perPage));
    const cur = Math.min(Math.max(1, Number(page) || 1), pages);
    const start = (cur - 1) * perPage;
    const end = start + perPage;
    return { items: list.slice(start, end), total, pages, page: cur, perPage };
  }

  // ===== Cart (localStorage THEO EMAIL) =====
  const CART_KEY_PREFIX = 'sv_cart_user_'; // prefix + email

  // Lấy email user hiện tại từ AUTH (tương thích nhiều phiên bản)
  function getCurrentUserEmail() {
    try {
      if (w.AUTH?.getCurrentUser) {
        const u = w.AUTH.getCurrentUser();
        if (u && u.email) return String(u.email).toLowerCase();
      }
      if (w.AUTH?.currentUser && w.AUTH.currentUser.email) {
        return String(w.AUTH.currentUser.email).toLowerCase();
      }
      // fallback cho bản cũ nếu có AUTH.user
      if (w.AUTH?.user && w.AUTH.user.email) {
        return String(w.AUTH.user.email).toLowerCase();
      }
    } catch (e) {
      console.warn('[SVStore] Lỗi lấy email user:', e);
    }
    return null;
  }

  function getCartKey() {
    const email = getCurrentUserEmail();
    if (!email) return null;
    return CART_KEY_PREFIX + email;
  }

  const getCart = () => {
    const key = getCartKey();
    if (!key) return []; // chưa đăng nhập = giỏ rỗng
    try {
      return JSON.parse(localStorage.getItem(key) || '[]');
    } catch {
      return [];
    }
  };

  const saveCart = (cart) => {
    const key = getCartKey();
    if (!key) return; // không lưu nếu chưa đăng nhập
    localStorage.setItem(key, JSON.stringify(cart));
    window.SERVER_CART_COUNT_SOURCE = 'local';
  };

  // phát sự kiện toàn cục mỗi khi giỏ đổi
  function emitCartChanged() {
    try {
      window.dispatchEvent(new CustomEvent('cart:changed'));
    } catch (_) {}
  }

  function resolveCartEndpoint() {
    // Tính toán app root dựa trên script src
    var script = document.currentScript || document.querySelector('script[src*="store.js"]');
    if (!script || !script.src) {
      // Fallback: kiểm tra pathname
      const pathname = (location.pathname || '').replace(/\\/g, '/').toLowerCase();
      if (pathname.indexOf('/product/pages/') !== -1) return '../../cart.php';
      if (pathname.indexOf('/product/') !== -1) return '../cart.php';
      return './cart.php';
    }
    
    var scriptUrl = new URL(script.src, location.origin);
    var pathParts = scriptUrl.pathname.split('/').filter(p => p && p !== 'assets' && p !== 'js' && p !== 'store.js');
    var joined = pathParts.join('/');
    var appRoot = joined ? ('/' + joined + '/') : '/';
    return appRoot + 'cart.php';
  }

  function addToServerCart(id, qty = 1) {
    if (!id || qty <= 0) return Promise.resolve(false);
    const endpoint = resolveCartEndpoint() + '?action=add';
    const body = new URLSearchParams({ id: String(id), qty: String(qty), redirect: 'none' });
    return fetch(endpoint, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: body.toString(),
    })
      .then((res) => {
        if (!res.ok) throw new Error('Lỗi máy chủ khi thêm giỏ hàng');
        return res.json();
      })
      .then((data) => {
        reconcileLocalAddWithServer(id, qty, data);

        if (data && typeof data.cart_count === 'number') {
          window.SERVER_CART_COUNT = data.cart_count;
          window.SERVER_CART_COUNT_SOURCE = 'server';
        } else {
          window.SERVER_CART_COUNT = count();
          window.SERVER_CART_COUNT_SOURCE = 'local';
        }
        emitCartChanged();
        return !!(data && data.success);
      })
      .catch((e) => {
        console.warn('[SVStore] addToServerCart failed', e);
        return false;
      });
  }

  let lastCartOp = {
    requestedQty: 0,
    addedQty: 0,
    maxAllowed: Infinity,
    finalQty: 0,
    limitReached: false,
    message: ''
  };

  function addToCart(id, qty = 1) {
    if (!w.AUTH?.loggedIn) {
      console.warn('[SVStore] Chưa đăng nhập - không thể thêm vào giỏ');
      lastCartOp = {
        requestedQty: qty,
        addedQty: 0,
        maxAllowed: 0,
        finalQty: 0,
        limitReached: true,
        message: 'Bạn cần đăng nhập để thêm vào giỏ.'
      };
      return [];
    }
    qty = clampNonNegative(qty) || 1;
    const cart = getCart();
    const i = cart.findIndex(x => String(x.id) === String(id));
    const currentQty = i > -1 ? clampNonNegative(cart[i].qty || 0) : 0;
    const stockMax = getProductStockById(id);
    const limitedByStock = Number.isFinite(stockMax);
    const addable = limitedByStock ? Math.max(0, stockMax - currentQty) : qty;
    const actualAdd = Math.min(qty, addable);

    console.log('[SVStore] addToCart: id=' + id + ', qty_requested=' + qty + ', current_in_cart=' + currentQty + ', stock_max=' + stockMax + ', can_add=' + addable + ', will_add=' + actualAdd);

    if (actualAdd <= 0) {
      lastCartOp = {
        requestedQty: qty,
        addedQty: 0,
        maxAllowed: stockMax,
        finalQty: currentQty,
        limitReached: true,
        message: Number.isFinite(stockMax)
          ? 'Bạn đã thêm tối đa theo tồn kho. Tồn kho còn ' + stockMax + ' cái, bạn đã có ' + currentQty + ' cái trong giỏ.'
          : 'Không thể thêm vào giỏ lúc này.'
      };
      console.log('[SVStore] blocked: ' + lastCartOp.message);
      emitCartChanged();
      return cart;
    }

    if (i > -1) cart[i].qty = currentQty + actualAdd;
    else cart.push({ id, qty: actualAdd });

    const finalQty = i > -1 ? cart[i].qty : actualAdd;
    const isAtMaxStock = limitedByStock && finalQty >= stockMax;
    
    let message = '';
    if (actualAdd > 0) {
      if (actualAdd < qty) {
        // Partial add due to stock limit
        message = 'Số lượng tồn kho còn ' + Math.max(0, stockMax - currentQty) + ' cái. Đã thêm ' + actualAdd + ' sản phẩm vào giỏ (tối đa ' + stockMax + ').';
      } else if (isAtMaxStock) {
        // Fully added and at max stock
        message = 'Bạn đã thêm ' + actualAdd + ' sản phẩm vào giỏ hàng (tối đa tồn kho là ' + stockMax + ' cái).';
      } else {
        // Fully added but not at max yet
        message = 'Bạn đã thêm ' + actualAdd + ' sản phẩm vào giỏ hàng.';
      }
    }
    
    lastCartOp = {
      requestedQty: qty,
      addedQty: actualAdd,
      maxAllowed: stockMax,
      finalQty,
      limitReached: isAtMaxStock,
      message: message
    };

    saveCart(cart);
    emitCartChanged();
    addToServerCart(id, actualAdd);
    return cart;
  }

  function setQty(id, qty) {
    if (!w.AUTH?.loggedIn) return [];
    qty = clampNonNegative(qty) || 1;
    const cart = getCart().map(x => x.id === id ? { ...x, qty } : x);
    saveCart(cart);
    emitCartChanged();
    return cart;
  }

  function removeFromCart(id) {
    if (!w.AUTH?.loggedIn) return [];
    const cart = getCart().filter(x => x.id !== id);
    saveCart(cart);
    emitCartChanged();
    return cart;
  }

  function clearCart() {
    if (!w.AUTH?.loggedIn) return;
    saveCart([]);
    window.SERVER_CART_COUNT = 0;
    window.SERVER_CART_COUNT_SOURCE = 'server';
    emitCartChanged();
  }

  const count = () => {
    if (!w.AUTH?.loggedIn) {
      return typeof window.SERVER_CART_COUNT === 'number' ? window.SERVER_CART_COUNT : 0;
    }
    const c = getCart().reduce((s, x) => s + (x.qty || 0), 0);
    if (typeof window.SERVER_CART_COUNT === 'number') {
      const serverCount = Math.max(0, Number(window.SERVER_CART_COUNT) || 0);

      // Nếu server đã reset giỏ về 0 (xóa giỏ server-side), dọn local để badge luôn đúng.
      if (serverCount === 0 && c > 0) {
        saveCart([]);
        return 0;
      }

      if (window.SERVER_CART_COUNT_SOURCE === 'server') {
        return serverCount;
      }

      if (c === 0) {
        return serverCount;
      }
    }
    return c;
  };

  function total(products = null) {
    if (!w.AUTH?.loggedIn) return 0;
    const list = products || getAllProducts();
    const map = new Map(list.map(p => [p.id, p]));
    return getCart().reduce((s, x) => {
      const p = map.get(x.id);
      return s + (p ? toNumber(p.price) * (x.qty || 0) : 0);
    }, 0);
  }

  // tiện cho trang khác: lấy số lượng 1 sản phẩm trong giỏ
  function getCartItemQty(id) {
    const cart = getCart();
    const found = cart.find(x => String(x.id) === String(id));
    return found ? (found.qty || 0) : 0;
  }

  function getLastCartOp() {
    return { ...lastCartOp };
  }

  w.SVStore = {
    fmtVND,
    toNumber,
    normalizeBadge,
    getAllProducts,
    query(opts = {}) {
      const {
        q = '',
        category = 'all',
        minPrice = '',
        maxPrice = '',
        sort = '',
        page = 1,
        perPage = 12,
        featured = null
      } = opts;

      const base = getAllProducts();
      const baseByFeatured = base.filter(p =>
        featured === null ? true : (!!p.featured === !!featured)
      );
      const filtered = filterProducts(baseByFeatured, {
        q,
        category,
        minPrice,
        maxPrice
      });
      const sorted = sortProducts(filtered, sort);
      return paginate(sorted, page, perPage);
    },

    // Cart API
    getCart,
    addToCart,
    setQty,
    removeFromCart,
    clearCart,
    count,
    total,
    getCartItemQty,
    getProductStockById,
    getLastCartOp
  };

  // Alias cho backward compat
  window.SVCart = {
    add: (id, qty = 1) => {
      if (!w.AUTH?.loggedIn) {
        console.warn('[SVCart] Chưa đăng nhập');
        return;
      }
      w.SVStore?.addToCart(id, qty);
      window.dispatchEvent(new CustomEvent('cart:changed'));
    },
    count: () => w.SVStore?.count?.() ?? 0
  };

  // Khi auth thay đổi (login/logout) → refresh badge
  document.addEventListener('auth:changed', () => {
    emitCartChanged();
  });

})(window);
