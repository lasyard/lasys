const path = require('path');
const {
    CleanWebpackPlugin
} = require('clean-webpack-plugin');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CopyPlugin = require("copy-webpack-plugin");

module.exports = {
    mode: 'production',
    entry: {
        'js/main.js': {
            import: './pub_src/index.ts',
            library: {
                type: 'window',
            },
        },
        'js/login.js': './pub_src/login.ts',
        // MiniCssExtractPlugin will add `.css`.
        'css/main': './pub_src/index.scss',
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
        }, {
            test: /\.woff2?$/,
            type: 'asset/resource',
            generator: {
                filename: 'fonts/[name][ext]',
            },
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
                to: 'lib/'
            }, {
                from: 'node_modules/bootstrap-icons/font/fonts/bootstrap-icons.woff2',
                to: 'lib/fonts/'
            }, {
                from: 'node_modules/bootstrap-icons/font/fonts/bootstrap-icons.woff',
                to: 'lib/fonts/'
            }],
        }),
    ],
};
