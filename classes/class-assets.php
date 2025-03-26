<?php

namespace Mai\Builder;

use Mai\Builder\Cache;
use Mai\Builder\Config;
use MatthiasMullie\Minify;

defined( 'ABSPATH' ) || exit;

/**
 * Assets class.
 *
 * @since 0.1.0
 */
class Assets {

	/**
	 * The single instance of the class.
	 *
	 * @var Assets
	 */
	private static $instance = null;

	/**
	 * Cache instance.
	 *
	 * @var Cache
	 */
	protected $cache;

	/**
	 * Cache time.
	 *
	 * @var int
	 */
	protected $expire;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function __construct() {
		$this->cache        = new Cache;
		$this->expire       = DAY_IN_SECONDS;
		$this->cache->debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
		$this->hooks();
	}

	/**
	 * Get the single instance of the class.
	 *
	 * @since 0.1.0
	 *
	 * @return Assets
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
		add_action( 'wp_enqueue_scripts',          [ $this, 'enqueue_frontend_global_css' ], 5 );
		add_action( 'wp_enqueue_scripts',          [ $this, 'enqueue_frontend_global_js' ], 5 );
		add_action( 'after_setup_theme',           [ $this, 'enqueue_blocks_css' ], 5 );
		add_action( 'after_setup_theme',           [ $this, 'register_block_styles' ], 5 );
		add_filter( 'get_block_type_variations',   [ $this, 'register_block_variations' ], 5, 2 );
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function enqueue_editor_assets() {
		$assets = include( plugin_dir_path( __DIR__ ) . 'build/block-settings.asset.php' );

		wp_enqueue_script(
			'mai-block-settings',
			plugin_dir_url( __DIR__ ) . 'build/block-settings.js',
			$assets['dependencies'],
			$assets['version'],
		);

		// Get the global styles.
		$styles = $this->cache->remember( 'global_styles', [ $this, 'get_global_css_data' ], $this->expire );

		// Bail if no styles.
		if ( ! $styles['editor'] ) {
			return;
		}

		// Loop through the editor styles.
		foreach ( $styles['editor'] as $style ) {
			// Enqueue the editor style.
			wp_enqueue_style( $style['handle'], $style['uri'], [], $style['ver'] );
		}
	}

	/**
	 * Enqueue global styles.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function enqueue_frontend_global_css() {
		// Get the global styles.
		$styles = $this->cache->remember( 'global_styles', [ $this, 'get_global_css_data' ], $this->expire );

		// Bail if no styles.
		if ( ! $styles['frontend'] ) {
			return;
		}

		// Register and enqueue the global styles.
		wp_register_style( 'mai-global', false );
		wp_enqueue_style( 'mai-global' );
		wp_add_inline_style( 'mai-global', $styles['frontend'] );
	}

	/**
	 * Enqueue global scripts.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function enqueue_frontend_global_js() {
		// Get the global scripts.
		$data = $this->cache->remember( 'global_scripts', [ $this, 'get_global_scripts_data' ], DAY_IN_SECONDS );

		// Loop through data.
		foreach ( $data as $filename => $file ) {
			wp_enqueue_script( $filename, $file['uri'], [], $file['ver'], true );
		}
	}

	/**
	 * Enqueue blocks CSS.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function enqueue_blocks_css() {
		// Get the block styles.
		$data = $this->cache->remember( 'block_css', [ $this, 'get_block_css_data' ], $this->expire );

		// Loop through data.
		foreach ( (array) $data as $block ) {
			foreach ( (array) $block['blocks'] as $block_name ) {
				// Enqueue the block style.
				wp_enqueue_block_style(
					$block_name,
					[
						'handle' => $block['handle'],
						'src'    => $block['src'],
						'path'   => $block['path'],
						'ver'    => $block['ver'],
					]
				);
			}
		}
	}

	/**
	 * Register block styles.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_block_styles() {
		// Get the block styles.
		$data = $this->cache->remember( 'block_styles', [ $this, 'get_block_styles_data' ], $this->expire );

		// Loop through data.
		foreach ( $data as $block ) {
			// If the block has a src and path, enqueue the block styles.
			if ( $block['src'] && $block['path'] ) {
				// Enqueue the block style.
				foreach ( (array) $block['blocks'] as $block_name ) {
					wp_enqueue_block_style(
						$block_name,
						[
							'handle' => $block['handle'],
							'src'    => $block['src'],
							'path'   => $block['path'],
						]
					);
				}
			}

			// Register the block style.
			register_block_style(
				$block['blocks'],
				[
					'name'         => $block['name'],
					'label'        => $block['label'],
					'style_handle' => $block['handle'],
				]
			);
		}
	}

	/**
	 * Register block variations.
	 * Uses static cache to avoid multiple calls to the cache
	 * for each registered block.
	 *
	 * @since 0.1.0
	 *
	 * @param array         $variations The variations.
	 * @param WP_Block_Type $block_type The block type.
	 *
	 * @return array
	 */
	public function register_block_variations( $variations, $block_type ) {
		// Get the block styles.
		static $data = null;

		// Get the block variations data.
		$data = is_null( $data ) ? $this->cache->remember( 'block_variations', [ $this, 'get_block_variations_data' ], $this->expire ) : $data;

		// Bail if no data.
		if ( ! $data ) {
			return $variations;
		}

		// Bail if no block variation for this block.
		if ( ! isset( $data[ $block_type->name ] ) ) {
			return $variations;
		}

		// Register the block variation.
		$variations[] = $data[ $block_type->name ];

		return $variations;
	}

	/**
	 * Get global scripts data.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_global_scripts_data() {
		$theme_dir   = get_stylesheet_directory() . '/mai/js/';
		$theme_uri   = get_stylesheet_directory_uri() . '/mai/js/';
		$all_scripts = (array) Config::get( 'js' );
		$plugin_dir  = plugin_dir_path(__DIR__) . 'assets/js/';
		$plugin_uri  = plugin_dir_url(__DIR__) . 'assets/js/';
		$data        = [];

		// Loop through all scripts.
		foreach ( $all_scripts as $filename ) {
			// If the theme path is readable.
			if ( is_readable( $theme_dir . $filename . '.js' ) ) {
				$data[ $filename ] = [
				'uri'  => $theme_uri . $filename,
				'path' => $theme_dir . $filename,
					'ver'  => filemtime( $theme_dir . $filename ),
				];
			}
			// If the plugin path is readable.
			elseif ( is_readable( $plugin_dir . $filename . '.js' ) ) {
				$data[ $filename ] = [
					'uri'  => $plugin_uri . $filename,
					'path' => $plugin_dir . $filename,
					'ver'  => filemtime( $plugin_dir . $filename ),
				];
			}
		}

		return $data;
	}

	/**
	 * Get global CSS.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_global_css_data() {
		$theme_dir  = get_stylesheet_directory() . '/mai/css/';
		$theme_uri  = get_stylesheet_directory_uri() . '/mai/css/';
		$plugin_dir = plugin_dir_path(__DIR__) . 'assets/css/';
		$plugin_uri = plugin_dir_url(__DIR__) . 'assets/css/';
		$all_styles = array_filter( (array) Config::get( 'css' ) );
		$data       = [ 'frontend' => [], 'editor' => [] ];
		$styles     = [ 'frontend' => '', 'editor' => [] ];

		// Loop through all styles to build data array.
		foreach ( $all_styles as $filename => $locations ) {
			// If the theme path is readable.
			if ( is_readable( $theme_dir . $filename . '.css' ) ) {
				foreach ( $locations as $location ) {
					$data[ $location ][] = [
						'uri'  => $theme_uri . $filename . '.css',
						'path' => $theme_dir . $filename . '.css',
					];
				}
			}
			// If the plugin path is readable.
			elseif ( is_readable( $plugin_dir . $filename . '.css' ) ) {
				foreach ( $locations as $location ) {
					$data[ $location ][] = [
						'uri'  => $plugin_uri . $filename . '.css',
						'path' => $plugin_dir . $filename . '.css',
					];
				}
			}
		}

		// Loop through data to build styles strings.
		foreach ( $data as $location => $file_paths ) {
			// Add frontend styles.
			if ( 'frontend' === $location ) {
				// Loop through file paths.
				foreach ( $file_paths as $file_path ) {
					// Get the CSS.
					$css = file_get_contents( $file_path['path'] );

					// Skip if empty.
					if ( empty( $css ) ) {
						continue;
					}

					// If not debugging.
					if ( ! $this->cache->debug ) {
						$minifier = new Minify\CSS( $css );
						$css = $minifier->minify();
					}

					// Add to styles.
					$styles[ $location ] .= $css;
				}
			}
			// Add editor styles.
			elseif ( 'editor' === $location ) {
				foreach ( $file_paths as $file_path ) {
					$filename              = pathinfo( $file_path['path'], PATHINFO_FILENAME );
					$styles[ $location ][] = [
						'handle' => "mai-global-{$filename}",
						'uri'    => $file_path['uri'],
						'path'   => $file_path['path'],
						'ver'    => filemtime( $file_path['path'] ),
					];
				}
			}
		}

		return $styles;
	}

	/**
	 * Get block CSS data.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_block_css_data() {
		$block_styles = (array) Config::get( 'blocks.css' );
		$data         = [];

		// Loop through the block styles.
		foreach ( $block_styles as $filename => $block_names ) {
			// Skip if disabled via false or empty array.
			if ( ! $block_names ) {
				continue;
			}

			// Get the theme and plugin data.
			$theme_uri   = get_theme_file_uri( "mai/blocks/css/{$filename}.css" );
			$theme_path  = get_theme_file_path( "mai/blocks/css/{$filename}.css" );
			$plugin_uri  = plugin_dir_url(__DIR__) . "assets/blocks/css/{$filename}.css";
			$plugin_path = plugin_dir_path(__DIR__) . "assets/blocks/css/{$filename}.css";

			// Start block data.
			$block = [
				'blocks' => $block_names,
				'handle' => "mai-block-style-{$filename}",
				'src'    => null,
				'path'   => null,
				'ver'    => null,
			];

			// If the theme path is readable.
			if ( is_readable( $theme_path ) ) {
				$block['src']  = $theme_uri;
				$block['path'] = $theme_path;
				$block['ver']  = filemtime( $theme_path );

				// Add the block data.
				$data[] = $block;
			}
			// If the plugin path is readable.
			elseif ( is_readable( $plugin_path ) ) {
				$block['src']  = $plugin_uri;
				$block['path'] = $plugin_path;
				$block['ver']  = filemtime( $plugin_path );

				// Add the block data.
				$data[] = $block;
			}
		}

		return $data;
	}

	/**
	 * Get block styles data.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_block_styles_data() {
		$block_styles = (array) Config::get( 'blocks.styles' );
		$data         = [];

		// Loop through the block styles.
		foreach ( $block_styles as $filename => $block_data ) {
			// Skip if disabled via false or empty array.
			if ( ! $block_data ) {
				continue;
			}

			// TODO: Allow for registering the style without CSS.
			// TODO: Allow for disabling the style via false in the config.

			// Parse the block data.
			$block = wp_parse_args(
				$block_data,
				[
					'label'   => ucwords( str_replace( '-', ' ', $filename ) ),
					'blocks'  => [],
					'name'    => $filename,
					'handle'  => "mai-block-style-{$filename}",
					'default' => false,
					'src'     => null,
					'path'    => null,
					'ver'     => null,
				]
			);

			// Get the theme and plugin data.
			$theme_uri   = get_theme_file_uri( "mai/blocks/styles/{$filename}.css" );
			$theme_path  = get_theme_file_path( "mai/blocks/styles/{$filename}.css" );
			$plugin_uri  = plugin_dir_url(__DIR__) . "assets/blocks/styles/{$filename}.css";
			$plugin_path = plugin_dir_path(__DIR__) . "assets/blocks/styles/{$filename}.css";

			// If the theme path is readable.
			if ( is_readable( $theme_path ) ) {
				$block['src']  = $theme_uri;
				$block['path'] = $theme_path;
				$block['ver']  = filemtime( $theme_path );
			}
			// If the plugin path is readable.
			elseif ( is_readable( $plugin_path ) ) {
				$block['src']  = $plugin_uri;
				$block['path'] = $plugin_path;
				$block['ver']  = filemtime( $plugin_path );
			}

			// Add the block data.
			$data[] = $block;
		}

		return $data;
	}

	/**
	 * Get block variations data.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_block_variations_data() {
		$variations = (array) Config::get( 'blocks.variations' );
		$data             = [];

		// Loop through the block variations.
		foreach ( $variations as $variation ) {
			// Loop through the blocks.
			foreach ( $variation['blocks'] as $block_name ) {
				// Add a custom variation.
				$data[ $block_name ] = wp_parse_args(
					$variation,
					[
						'name'        => null,
						'title'       => null,
						'description' => null,
						'scope'       => [ 'inserter' ],
						'isDefault'   => false,
						'attributes'  => [],
					],
				);
			}
		}

		return $data;
	}
}
