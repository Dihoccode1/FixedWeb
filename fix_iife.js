const fs = require('fs');
const path = require('path');

function walkDir(dir, callback) {
    fs.readdirSync(dir).forEach(f => {
        let dirPath = path.join(dir, f);
        let isDirectory = fs.statSync(dirPath).isDirectory();
        if (isDirectory) {
            walkDir(dirPath, callback);
        } else {
            callback(dirPath);
        }
    });
}

const basePath = path.resolve('.');
let changedCount = 0;

walkDir(basePath, function(filePath) {
    if (!filePath.endsWith('.html')) return;
    
    let original = fs.readFileSync(filePath, 'utf8');
    
    // Replace IIFE missing arguments
    // Pattern: (function (w, d) { ... })();
    // We use a regex to find everything from `(function (w, d) {` to `})();` 
    // Wait, regex with [\s\S]*? might be slow or risky if there are nested IFFEs.
    // Instead, just replace any `})();` that comes after a `function(w, d)` in the same script.
    // Actually, safer:
    let content = original.replace(/(\(function\s*\(\s*w\s*,\s*d\s*\)\s*\{[\s\S]*?\})\)\(\);/g, '$1})(window, document);');

    // Also handle `function (w,d) { ... })();`
    content = content.replace(/(\(function\s*\(\s*w\s*,\s*d\s*\)\s*\{[\s\S]*?\})\)\(\s*\);/g, '$1})(window, document);');

    if (content !== original) {
        fs.writeFileSync(filePath, content, 'utf8');
        console.log("Fixed IIFE in:", filePath);
        changedCount++;
    }
});

console.log(`Total HTML files with IIFE fixed: ${changedCount}`);
