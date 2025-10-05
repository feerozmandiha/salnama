/**
 * Custom Webpack config for Salnama plugin (based on @wordpress/scripts defaults)
 *
 * Scans src/blocks/<block> for:
 *  - index.js + editor.scss  -> entry: blocks/<block>/index  (editor script + editor css)
 *  - style.scss              -> entry: blocks/<block>/style-index (frontend CSS)
 *  - view.js                 -> entry: blocks/<block>/view (frontend JS, registered as "script" in block.json)
 *
 * Outputs into: build/blocks/<block>/...
 */

const defaults = require('@wordpress/scripts/config/webpack.config');
const path = require('path');
const fs = require('fs');
const blocksDir = path.resolve(process.cwd(), 'src', 'blocks');

function getBlockEntries() {
    const entries = {};

    if (!fs.existsSync(blocksDir)) {
        console.log('Blocks directory not found:', blocksDir);
        return entries;
    }

    const blockFolders = fs.readdirSync(blocksDir).filter((name) => {
        const blockPath = path.resolve(blocksDir, name);
        return fs.statSync(blockPath).isDirectory();
    });

    console.log('Found block folders:', blockFolders);

    blockFolders.forEach((folderName) => {
        const blockPath = path.resolve(blocksDir, folderName);
        console.log(`Processing block: ${folderName}`);

        // 1) Editor entry: index.js + editor.scss
        const editorScript = path.resolve(blockPath, 'index.js');
        const editorStyle = path.resolve(blockPath, 'editor.scss');

        const editorEntryFiles = [];
        if (fs.existsSync(editorScript)) {
            editorEntryFiles.push(editorScript);
            console.log(`  - Added editor script: ${editorScript}`);
        }
        if (fs.existsSync(editorStyle)) {
            editorEntryFiles.push(editorStyle);
            console.log(`  - Added editor style: ${editorStyle}`);
        }

        if (editorEntryFiles.length > 0) {
            entries[`blocks/${folderName}/index`] = editorEntryFiles;
            console.log(`  - Created editor entry: blocks/${folderName}/index`);
        }

        // 2) Frontend stylesheet: style.scss -> style-index.css
        const styleFile = path.resolve(blockPath, 'style.scss');
        if (fs.existsSync(styleFile)) {
            entries[`blocks/${folderName}/style-index`] = styleFile;
            console.log(`  - Created style entry: blocks/${folderName}/style-index`);
        }

        // 3) Frontend JS (view.js) for `script` in block.json
        const viewScript = path.resolve(blockPath, 'view.js');
        if (fs.existsSync(viewScript)) {
            entries[`blocks/${folderName}/view`] = viewScript;
            console.log(`  - Created view script entry: blocks/${folderName}/view`);
        } else {
            console.log(`  - view.js not found: ${viewScript}`);
        }

        // 4) Also check for frontend.js (legacy)
        const frontendScript = path.resolve(blockPath, 'frontend.js');
        if (fs.existsSync(frontendScript)) {
            entries[`blocks/${folderName}/frontend`] = frontendScript;
            console.log(`  - Created frontend script entry: blocks/${folderName}/frontend`);
        }
    });

    console.log('Final entries:', Object.keys(entries));
    return entries;
}

// Filter out default MiniCssExtractPlugin to avoid conflicts
const filteredPlugins = defaults.plugins.filter(plugin => {
    return plugin.constructor.name !== 'MiniCssExtractPlugin';
});

module.exports = {
    ...defaults,

    // override entry with block-based entries
    entry: getBlockEntries(),

    output: {
        ...defaults.output,
        path: path.resolve(process.cwd(), 'build'),
        filename: '[name].js',
    },

    module: {
        ...defaults.module,
        rules: [
            ...defaults.module.rules,
        ],
    },

    plugins: [
        ...filteredPlugins,
        new (require('mini-css-extract-plugin'))({
            filename: '[name].css',
        }),
    ],

    optimization: {
        ...defaults.optimization,
        splitChunks: {
            cacheGroups: {
                default: false,
                vendors: false,
            },
        },
    },
};