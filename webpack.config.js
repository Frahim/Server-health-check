const path = require('path');

module.exports = {
    entry: './src/block.js', // or './src/block.js' if inside /src
    output: {
        path: path.resolve(__dirname, 'build'),
        filename: 'block.js'
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: 'babel-loader'
            }
        ]
    },
    mode: 'development',
    watch: true
};
