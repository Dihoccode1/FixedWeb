const puppeteer = require('puppeteer');

(async () => {
  const browser = await puppeteer.launch({
      headless: "new"
  });
  const page = await browser.newPage();
  
  page.on('console', msg => {
      console.log(`[BROWSER CONSOLE] ${msg.type()}: ${msg.text()}`);
  });
  page.on('pageerror', error => {
      console.error(`[PAGE ERROR]: ${error.message}`);
  });
  
  const path = 'file:///' + __dirname.replace(/\\/g, '/') + '/Product/product.html';
  console.log("Navigating to", path);
  
  await page.goto(path, { waitUntil: 'networkidle0' });
  
  const gridHtmlLen = await page.evaluate(() => {
     const g = document.getElementById('product-grid');
     return g ? g.innerHTML.length : -1;
  });
  console.log("PRODUCT GRID HTML LENGTH:", gridHtmlLen);
  
  const qSelect = await page.evaluate(() => {
     return !!document.getElementById('q');
  });
  console.log("Search input element exists:", qSelect);
  
  await browser.close();
})();
