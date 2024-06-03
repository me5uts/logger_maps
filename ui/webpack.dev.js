const { merge } = require('webpack-merge');
const common = require('./webpack.common.js');
const path = require('path');

module.exports = merge(common, {
  mode: 'development',
  devtool: 'inline-source-map',
  devServer: {
    static: path.join(__dirname, 'dist'),
    host: 'ulogger.test',
    proxy: [
      {
        path: '**/*.php',
        changeOrigin: true,
        target: 'http://ulogger.test'
      },
      {
        path: '/api/**',
        changeOrigin: true,
        target: 'http://ulogger.test'
      }
    ]
  },
  module: {
    rules: [
      {
        test: /\.css$/i,
        use: [ 'style-loader', 'css-loader' ],
        generator: {
          filename: 'styles/[hash][ext][query]'
        }
      }
    ]
  }
});
