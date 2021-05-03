const path = require('path');
const webpack = require('webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const UglifyJsPlugin = require('uglifyjs-webpack-plugin');
const ImageminPlugin = require('imagemin-webpack-plugin').default;
//const WebpackRTLPlugin = require( 'webpack-rtl-plugin' );
const ProgressBarPlugin = require('progress-bar-webpack-plugin');
const {exec} = require('child_process');

const inProduction = ('production' === process.env.NODE_ENV);

const config = {
	// https://github.com/webpack-contrib/css-loader/issues/447
	node: {
		fs: 'empty',
	},
	// https://webpack.js.org/migrate/4/#mode
	mode: process.env.NODE_ENV,
	// Ensure modules like magnific know jQuery is external (loaded via WP).
	externals: {
		$: 'jQuery',
		jquery: 'jQuery',
		lodash: 'lodash',
		react: 'React',
		localStorage: 'localStorage',
	},
	devtool: 'source-map',
	module: {
		rules: [

			// Use Babel to compile JS.
			{
				test: /\.js$/,
				exclude: /node_modules/,
				loaders: [
					'babel-loader',
				]
			},

			// Create RTL styles.
			/*
            {
                test: /\.css$/,
                use: [
                  {
                    loader: MiniCssExtractPlugin.loader,
                    options: {
                      // you can specify a publicPath here
                      // by default it uses publicPath in webpackOptions.output
                      publicPath: '../',
                      hmr: process.env.NODE_ENV === 'development',
                    },
                  },
                  'css-loader',
                ],
            },
            */

			// SASS to CSS.
			{
				test: /\.scss$/,

				use: [
					{
						loader: MiniCssExtractPlugin.loader,
						options: {
							hmr: process.env.NODE_ENV === 'development',
						},
					},
					'css-loader',
					'postcss-loader',
					'sass-loader',
				],
			},

			// Image files.
			{
				test: /\.(png|jpe?g|gif)$/,
				use: [
					{
						loader: 'file-loader',
						options: {
							name: 'images/[name].[ext]',
							publicPath: '../',
						},
					},
				],
			},

			// SVG files.
			{
				test: /.svg$/,
				use: [
					{
						loader: 'svg-react-loader',
					},
				],
			},
		],
	},

	// Plugins. Gotta have em'.
	plugins: [

		new ProgressBarPlugin({clear: false}),

		new MiniCssExtractPlugin({filename: 'css/gutenberg-menu.css'}),

		// Copy CSS-files
		new CopyWebpackPlugin([{from: '*.css', to: 'css', 'context': 'assets/src/css/'}]),

		// Copy JS-files
		new CopyWebpackPlugin([{from: '*.js', to: 'js', 'context': 'assets/src/js/'}]),

		// Copy images and SVGs
		new CopyWebpackPlugin([{from: 'assets/src/images', to: 'images'}]),

		// Copy index.php to all dist directories.
		new CopyWebpackPlugin([{from: 'index.php', to: '.'}]),
		new CopyWebpackPlugin([{from: 'index.php', to: './images'}]),
		new CopyWebpackPlugin([{from: 'index.php', to: './js'}]),
		new CopyWebpackPlugin([{from: 'index.php', to: './css'}]),

		// Minify images.
		// Must go after CopyWebpackPlugin above: https://github.com/Klathmon/imagemin-webpack-plugin#example-usage
		new ImageminPlugin({test: /\.(jpe?g|png|gif|svg)$/i}),

	],
};

module.exports = [

	/*
    Object.assign( {
        entry: {
            blocks: './packages/blocks/index.js',
        },

        // Tell webpack where to output.
        output: {
            path: path.resolve( __dirname, './assets/dist/' ),
            filename: 'js/[name].js',
        },
    }, config ),
    */

	Object.assign({
		entry: {
			plugin: ['./assets/src/js/gutenberg/gutenberg-cache-purge-menu.js'],
		},
		output: {
			path: path.resolve(__dirname, './assets/dist/'),
			filename: 'js/gutenberg-cache-purge-menu.js',
		},
	}, config),

];

// inProd?
if (inProduction) {

	exec('wp i18n make-pot . languages/servebolt-optimizer.pot --domain="servebolt-wp" --include="assets/src/,src/Servebolt/"');

	// Uglify JS.
	config.optimization = {
		minimizer: [
			new UglifyJsPlugin({sourceMap: true})
		]
	};

	//config.plugins.push(  );

	// Minify CSS.
	config.plugins.push(new webpack.LoaderOptionsPlugin({minimize: true}));

}
