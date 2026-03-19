
        (function() {
            try {
                const fromAdmin = JSON.parse(
                    localStorage.getItem("sv_products_v1") || "[]"
                );
                if (Array.isArray(fromAdmin) && fromAdmin.length) {
                    window.SV_PRODUCT_SEED = fromAdmin;
                }
            } catch (e) {}
        })();
    