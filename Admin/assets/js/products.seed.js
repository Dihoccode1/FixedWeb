/**
 * products.seed.js - Seed admin.* data từ API vào localStorage
 * Chạy TRƯỚC inventory.js
 */

(function () {
  const LS_CATS = "admin.categories";
  const LS_PRODS = "admin.products";
  const LS_TX = "admin.stock";

  console.log("🔵 products.seed.js: Starting data seed...");

  // Kiểm tra localStorage đã có dữ liệu không
  const hasProds = localStorage.getItem(LS_PRODS);
  const hasCats = localStorage.getItem(LS_CATS);
  const hasTx = localStorage.getItem(LS_TX);

  console.log("📦 Current localStorage state:", {
    hasProds: !!hasProds,
    hasCats: !!hasCats,
    hasTx: !!hasTx,
  });

  // Nếu đã có dữ liệu, không cần seed lại
  if (hasProds && hasCats && hasTx) {
    console.log("✅ Data already in localStorage, skipping seed");
    return;
  }

  // Fetch dữ liệu từ API
  console.log("🔄 Fetching from API: ./api/inventory.php?action=all-data");

  fetch("./api/inventory.php?action=all-data")
    .then((res) => {
      console.log("API response status:", res.status);
      return res.json();
    })
    .then((data) => {
      console.log("✅ API data received:", data);

      if (data.success) {
        // Lưu vào localStorage
        if (data.products && data.products.length > 0) {
          localStorage.setItem(LS_PRODS, JSON.stringify(data.products));
          console.log("✅ Saved products to localStorage:", data.products.length + " items");
        }

        if (data.categories && data.categories.length > 0) {
          localStorage.setItem(LS_CATS, JSON.stringify(data.categories));
          console.log("✅ Saved categories to localStorage:", data.categories.length + " items");
        }

        if (data.transactions && data.transactions.length > 0) {
          localStorage.setItem(LS_TX, JSON.stringify(data.transactions));
          console.log("✅ Saved transactions to localStorage:", data.transactions.length + " items");
        }

        console.log(
          "🎉 Seed complete! Products=%d, Categories=%d, Transactions=%d",
          data.products?.length || 0,
          data.categories?.length || 0,
          data.transactions?.length || 0
        );
      } else {
        console.error("❌ API error:", data.message || "unknown");
      }
    })
    .catch((err) => {
      console.error("❌ Error fetching from API:", err);
    });
})();
