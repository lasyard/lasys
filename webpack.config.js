const path = require('path');
const {
    CleanWebpackPlugin
} = require('clean-webpack-plugin');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CopyPlugin = require("copy-webpack-plugin");

const SRC_DIR = './pub_src/';

module.exports = {
    mode: 'production',
    entry: {
        'js/main.js': {
            import: SRC_DIR + 'ts/index.ts',
            library: {
                type: 'window',
            },
        },
        'js/login.js': {
            import: SRC_DIR + 'ts/login.ts',
            dependOn: 'js/main.js',
        },
        'js/file.js': {
            import: SRC_DIR + 'ts/file.ts',
            dependOn: 'js/main.js',
        },
        'js/db.js': {
            import: SRC_DIR + 'ts/db.ts',
            dependOn: 'js/main.js',
        },
        'js/gallery.js': {
            import: SRC_DIR + 'ts/gallery.ts',
            dependOn: 'js/main.js',
        },
        'js/katex-on.js': {
            import: SRC_DIR + 'ts/katex-on.ts',
            dependOn: 'js/main.js',
        },
        // MiniCssExtractPlugin will add `.css`.
        'css/main': SRC_DIR + 'scss/index.scss',
    },
    output: {
        path: path.resolve(__dirname, 'pub'),
        filename: '[name]',
    },
    resolve: {
        extensions: ['.tsx', '.ts', '.jsx', '.js', 'scss'],
    },
    module: {
        rules: [{
            test: /\.tsx?$/,
            use: 'ts-loader',
            exclude: /node_modules/,
        }, {
            test: /\.scss$/,
            use: [
                MiniCssExtractPlugin.loader,
                'css-loader',
                'sass-loader',
            ],
        }],
    },
    plugins: [
        new CleanWebpackPlugin(),
        new MiniCssExtractPlugin({
            filename: '[name].css',
        }),
        new CopyPlugin({
            patterns: [{
                from: 'node_modules/bootstrap-icons/font/bootstrap-icons.css',
                to: 'lib/',
            }, {
                from: 'node_modules/bootstrap-icons/font/fonts/*.woff2',
                to: 'lib/fonts/[name][ext]',
            }, {
                from: 'node_modules/bootstrap-icons/font/fonts/*.woff',
                to: 'lib/fonts/[name][ext]',
            }, {
                from: 'node_modules/katex/dist/katex.min.css',
                to: 'lib/',
            }, {
                from: 'node_modules/katex/dist/fonts/*.woff2',
                to: 'lib/fonts/[name][ext]',
            }, {
                from: 'node_modules/katex/dist/fonts/*.woff',
                to: 'lib/fonts/[name][ext]',
            }, {
                from: 'node_modules/katex/dist/fonts/*.ttf',
                to: 'lib/fonts/[name][ext]',
            }],
        }),
    ],
};
