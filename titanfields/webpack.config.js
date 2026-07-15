const defaultConfig = require('@wordpress/scripts/config/webpack.config');

module.exports = {
    ...defaultConfig,
    entry: {
        'admin-groups': './src/Admin/react/App.js',
    },
    output: {
        ...defaultConfig.output,
        filename: '[name].build.js',
        path: __dirname + '/build',
    },
};
