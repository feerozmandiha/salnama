/**
 * Custom Webpack config for Salnama plugin (based on @wordpress/scripts defaults)
 *
 * Scans src/blocks/<block> for:
 *  - index.js + editor.scss  -> entry: blocks/<block>/index  (editor script + editor css)
 *  - style.scss              -> entry: blocks/<block>/style-index (frontend CSS)
 *  - frontend.js             -> entry: blocks/<block>/frontend (frontend JS, registered as "script" in block.json)
 *
 * Outputs into: build/blocks/<block>/...
 */

const defaults = require('@wordpress/scripts/config/webpack.config');
const path = require('path');
const fs = require('fs');

const blocksDir = path.resolve(process.cwd(), 'src', 'blocks');

function getBlockEntries() {
    const entries = {};

    if (!fs.existsSync(blocksDir)) return entries;

    const blockFolders = fs.readdirSync(blocksDir).filter((name) => {
        const blockPath = path.resolve(blocksDir, name);
        return fs.statSync(blockPath).isDirectory();
    });

    blockFolders.forEach((folderName) => {
        const blockPath = path.resolve(blocksDir, folderName);

        // 1) Editor entry: index.js + editor.scss
        const editorScript = path.resolve(blockPath, 'index.js');
        const editorStyle = path.resolve(blockPath, 'editor.scss');

        const editorEntryFiles = [];
        if (fs.existsSync(editorScript)) editorEntryFiles.push(editorScript);
        if (fs.existsSync(editorStyle)) editorEntryFiles.push(editorStyle);

        if (editorEntryFiles.length > 0) {
            // results: build/blocks/<block>/index.js and build/blocks/<block>/index.css (extracted)
            entries[`blocks/${folderName}/index`] = editorEntryFiles;
        }

        // 2) Frontend stylesheet: style.scss -> style-index.css
        const styleFile = path.resolve(blockPath, 'style.scss');
        if (fs.existsSync(styleFile)) {
            entries[`blocks/${folderName}/style-index`] = styleFile;
        }

        // 3) Optional frontend JS (e.g. frontend.js) for `script` in block.json (hero-slider)
        const frontendScript = path.resolve(blockPath, 'frontend.js');
        if (fs.existsSync(frontendScript)) {
            entries[`blocks/${folderName}/frontend`] = frontendScript;
        }
    });

    return entries;
}

module.exports = {
    ...defaults,

    // override entry with block-based entries
    entry: getBlockEntries(),

    output: {
        ...defaults.output,
        path: path.resolve(process.cwd(), 'build'),
        // keep name structure so outputs are like build/blocks/<block>/index.js
        filename: '[name].js',
    },

    module: {
        ...defaults.module,
        rules: [
            // keep default rules (babel, css loaders etc.)
            ...defaults.module.rules,
            // you may add custom loaders here if needed (images, svgs, other)
        ],
    },

    // Optionally tweak optimization: keep chunks separate to avoid merging block files
    optimization: {
        ...defaults.optimization,
        splitChunks: {
            // disable automatic vendor splitting to keep block outputs self-contained
            cacheGroups: {
                default: false,
                vendors: false,
            },
        },
    },

    // (اختیاری) اگر می‌خواهید نام فایل‌های CSS حتماً با همان الگو تولید شوند،
    // می‌توانید پلاگین MiniCssExtractPlugin را اینجا بازنویسی/پیکربندی کنید.
    // به صورت پیش‌فرض @wordpress/scripts یک MiniCssExtractPlugin اضافه می‌کند که
    // خروجی CSS را با همان نام ورودی تولید می‌کند (مثلاً blocks/<block>/index.css).
};
