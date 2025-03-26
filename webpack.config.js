const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		// 'block-filters': './src/js/block-filters.js',
		// 'block-styles': './src/js/block-styles.js',
		'block-settings': './src/js/block-settings.js',
		'icon-block-icons': './src/js/icon-block-icons.js',
		'icon-block-divider': './src/js/icon-block-divider.js',
	},
	output: {
		path: path.resolve(__dirname, 'build'),
		filename: '[name].js',
	},
};
