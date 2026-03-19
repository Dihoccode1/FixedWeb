const fs = require('fs');
const path = require('path');

const basePath = path.resolve('.');
const files = [
    "Product\\product.html",
    "Product\\pages\\product_detail.html",
    "payment_history.html",
    "order_success.html",
    "news.html",
    "index.html",
    "News_Section\\maintenance.html",
    "News_Section\\hint.html",
    "News_Section\\tutorial.html",
    "News_Section\\gold_digger.html",
    "News_Section\\distinguish.html",
    "contact.html",
    "cart.html",
    "checkout.html",
    "about.html",
    "account\\profile.html",
    "account\\orders.html"
];

// Mapping of replacements
const replacements = [
    { from: /sanpham\/sanpham\.html/g, to: 'Product/product.html' },
    { from: /giohang\.html/g, to: 'cart.html' },
    { from: /gioithieu\.html/g, to: 'about.html' },
    { from: /tintuc\.html/g, to: 'news.html' },
    { from: /lienhe\.html/g, to: 'contact.html' },
    { from: /chitietsanpham\.html/g, to: 'product_detail.html' },
    { from: /Product\/pages\/chitietsanpham\.html/g, to: 'Product/pages/product_detail.html' },
    { from: /huong_dan\.html/g, to: 'tutorial.html' },
    { from: /goi_y\.html/g, to: 'hint.html' },
    { from: /bao_quan\.html/g, to: 'maintenance.html' },
    { from: /phan_biet\.html/g, to: 'distinguish.html' },
    { from: /dao_vang\.html/g, to: 'gold_digger.html' },
    { from: /href\*\="\/sanpham"/g, to: 'href*="/Product"' }
];

let changedCount = 0;

for (const relPath of files) {
    const fullPath = path.join(basePath, relPath);
    if (!fs.existsSync(fullPath)) {
        console.log("Not found:", fullPath);
        continue;
    }
    
    let content = fs.readFileSync(fullPath, 'utf8');
    let original = content;
    
    for (const r of replacements) {
        content = content.replace(r.from, r.to);
    }
    
    if (content !== original) {
        fs.writeFileSync(fullPath, content, 'utf8');
        console.log(`Updated ${relPath}`);
        changedCount++;
    }
}

console.log(`Total files updated: ${changedCount}`);
