const fs = require('fs');
const jsdom = require("jsdom");
const { JSDOM } = jsdom;

const html = fs.readFileSync('Product/product.html', 'utf8');

const virtualConsole = new jsdom.VirtualConsole();
virtualConsole.on("log", (m) => console.log("LOG:", m));
virtualConsole.on("info", (m) => console.info("INFO:", m));
virtualConsole.on("warn", (m) => console.warn("WARN:", m));
virtualConsole.on("error", (m) => console.error("ERROR:", m));
virtualConsole.on("jsdomError", (e) => console.error("JSDOM ER:", e.message, e.detail));

const dom = new JSDOM(html, {
    url: "http://localhost/Product/product.html",
    runScripts: "dangerously",
    resources: "usable",
    virtualConsole
});

dom.window.addEventListener('load', () => {
    console.log("LOADED!");
    const grid = dom.window.document.getElementById('product-grid');
    console.log("PRODUCT GRID HTML LENGTH:", grid ? grid.innerHTML.length : 'grid not found');
    setTimeout(() => {
        console.log("WAIT 1S...");
        console.log("PRODUCT GRID HTML LENGTH:", grid ? grid.innerHTML.length : 'grid not found');
    }, 1000);
});
