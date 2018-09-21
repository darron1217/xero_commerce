var path = require('path');
const VueLoader = require('vue-loader/lib/plugin')

module.exports = {
  mode: 'development',
  entry: './src/app.js',
  output: {
    path: path.resolve(__dirname,'js'),
    filename: 'index.js'
  },
  module: {
    rules: [
      {
        test: /\.vue$/,
        loader: 'vue-loader'
      }
    ]
  },
  resolve: {
    alias: {
      'vue': 'vue/dist/vue.js'
    },
    extensions: ['*', '.js', '.vue', '.json']
  },
  plugins: [
      new VueLoader()
  ]
}