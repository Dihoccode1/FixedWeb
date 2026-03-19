const fs = require('fs');
const jsdom = require("jsdom");
const { JSDOM } = jsdom;

const html = fs.readFileSync('Product/product.html', 'utf8');
const virtualConsole = new jsdom.VirtualConsole();
virtualConsole.on("jsdomError", (e) => {
    console.error("JSDOM ER:", e.message, e.detail ? e.detail.stack : '');
});

const dom = new JSDOM(html, {
    url: "file:///" + __dirname.replace(/\\/g, '/') + "/Product/product.html",
    runScripts: "dangerously",
    resources: "usable",
    virtualConsole
});
