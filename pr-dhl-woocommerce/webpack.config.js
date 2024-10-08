const path = require('path');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const WooCommerceDependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

// Remove SASS rule from the default config so we can define our own.
const defaultRules = defaultConfig.module.rules.filter((rule) => {
	return String(rule.test) !== String(/\.(sc|sa)ss$/);
});

module.exports = {
	...defaultConfig,
	entry: {
		index: path.resolve(process.cwd(), 'includes', 'checkout-blocks', 'js', 'index.js'),
		// Existing blocks
		'pr-dhl-blocks': path.resolve(
			process.cwd(),
			'includes',
			'checkout-blocks',
			'js',
			'dhl-blocks',
			'index.js'
		),
		'pr-dhl-blocks-frontend': path.resolve(
			process.cwd(),
			'includes',
			'checkout-blocks',
			'js',
			'dhl-blocks',
			'frontend.js'
		)
	},
	module: {
		...defaultConfig.module,
		rules: [
			...defaultRules,
			{
				test: /\.(sc|sa)ss$/,
				exclude: /node_modules/,
				use: [
					MiniCssExtractPlugin.loader,
					{ loader: 'css-loader', options: { importLoaders: 1 } },
					{
						loader: 'sass-loader',
						options: {
							sassOptions: {
								includePaths: ['src/css'],
							},
						},
					},
				],
			},
		],
	},
	plugins: [
		...defaultConfig.plugins.filter(
			(plugin) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		// Add WooCommerce Dependency Extraction Plugin
		new WooCommerceDependencyExtractionWebpackPlugin(),
		// MiniCssExtractPlugin for handling the CSS files
		new MiniCssExtractPlugin({
			filename: `[name].css`,
		}),
	],
};
