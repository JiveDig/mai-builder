<?php

namespace Mai\Builder;

use WP_HTML_Tag_Processor;
use WP_REST_Request;
use WP_REST_Response;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

defined( 'ABSPATH' ) || exit;

/**
 * Icons Block class.
 *
 * @since 0.1.0
 *
 * @version 0.1.0
 *
 * @uses Icons Block from Nick Diego.
 * @link https://wordpress.org/plugins/icon-block/
 *
 * @return void
 */
class IconBlock {
	/**
	 * Icons.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected $icons;

	/**
	 * Categories.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected $categories;

	/**
	 * Instance of the class.
	 *
	 * @var IconBlock|null
	 */
	private static ?IconBlock $instance = null;

	/**
	 * Constructor.
	 *
	 * @since TBD
	 */
	function __construct() {
		$this->icons      = [];
		$this->categories = [];
		$this->hooks();
	}

	/**
	 * Get instance of the class.
	 *
	 * @since 0.1.0
	 *
	 * @return IconBlock
	 */
	public static function get_instance(): IconBlock {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function hooks() {
		add_action( 'rest_api_init',                     [ $this, 'register_rest_route' ] );
		add_action( 'enqueue_block_editor_assets',       [ $this, 'enqueue_script' ] );
		add_filter( 'render_block_outermost/icon-block', [ $this, 'render_block' ], 10, 2 );
	}

	/**
	 * Register REST route.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function register_rest_route() {
		register_rest_route( 'mai/v1', '/icons(?:/(?P<path>.+))?', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_icons' ],
			'permission_callback' => '__return_true',
			// 'permission_callback' => function() {
				// return current_user_can( 'edit_posts' );
			// },
			'args' => [
				'path' => [
					'required'    => false,
					'type'        => 'string',
					'description' => __( 'The subpath within the icons directory to filter by.', 'mai-builder' ),
				],
			],
		]);
	}

	/**
	 * Get icons.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request The REST API request.
	 *
	 * @return WP_REST_Response
	 */
	function get_icons( WP_REST_Request $request ) {
		$base_dir   = get_template_directory() . '/mai/icons';
		$path       = $request->get_param( 'path' );
		$target_dir = $path ? realpath( $base_dir . '/' . $path ) : $base_dir;

		// Ensure the target directory is within the base icons directory.
		if ( ! str_contains( $target_dir, $base_dir ) || ! is_dir( $target_dir ) ) {
			return new WP_REST_Response( [ 'error' => __( 'Invalid directory path.', 'mai-builder' ) ], 400 );
		}

		// Start icons and categories.
		$this->icons      = [];
		$this->categories = [];

		// If no path, check for root level SVGs.
		if ( ! $path ) {
			$root_svgs = glob( $base_dir . '/*.svg' );

			// If root level SVGs.
			if ( ! empty( $root_svgs ) ) {
				$cat_slug  = '_theme';
				$cat_title = __( 'Theme', 'mai-builder' );

				// Add category.
				$this->categories[] = [
					'name'  => $cat_slug,
					'title' => $cat_title,
				];

				// Add icons.
				foreach ( $root_svgs as $svg ) {
					$filename      = basename( $svg, '.svg' );
					$this->icons[] = [
						'name'       => $filename,
						'title'      => $this->format_title( $filename ),
						'icon'       => file_get_contents( $svg ),
						'categories' => [ $cat_slug ],
					];
				}
			}
		}

		// Get iterator.
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $base_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::SELF_FIRST
		);

		// Loop through the iterator.
		foreach ( $iterator as $file ) {
			// Skip if not a directory.
			if ( ! $file->isDir() ) {
				continue;
			}

			// Get SVGs in this directory.
			$svg_files = glob( $file->getPathname() . '/*.svg' );

			// Skip if no SVGs.
			if ( empty( $svg_files ) ) {
				continue;
			}

			// Set category name.
			$cat_base  = str_replace( $base_dir . '/', '', $file->getPathname() );
			$cat_slug  = str_replace( '/', '-', $cat_base );
			$cat_title = str_replace( '-', ' ', $cat_base );

			// Add category.
			$this->categories[] = [
				'name'  => $cat_slug,
				'title' => $cat_title,
			];

			// Add icons.
			foreach ( $svg_files as $svg ) {
				$filename      = basename( $svg, '.svg' );
				$this->icons[] = [
					'name'       => sanitize_title( $cat_slug . '-' . $filename ),
					'title'      => $this->format_title( $filename ),
					'icon'       => file_get_contents( $svg ),
					'categories' => [ $cat_slug ],
				];
			}
		}

		// Sort categories.
		usort( $this->categories, function( $a, $b ) {
			return strcmp( $a['title'], $b['title'] );
		} );

		// Return the icons and categories.
		return new WP_REST_Response( [
			'icons'      => $this->icons,
			'categories' => $this->categories,
		], 200 );
	}

	/**
	 * Format title.
	 *
	 * @since 0.1.0
	 *
	 * @param string $title Title to format.
	 *
	 * @return string
	 */
	function format_title( $title ) {
		return ucwords( str_replace( ['-', '_', '/'], ' ', $title ) );
	}

	/**
	 * Enqueue script.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function enqueue_script() {
		$icons_asset   = require( plugin_dir_path( __DIR__ ) . 'build/icon-block-icons.asset.php' );
		$divider_asset = require( plugin_dir_path( __DIR__ ) . 'build/icon-block-divider.asset.php' );

		wp_enqueue_script(
			'mai-icon-block-icons',
			plugins_url( 'build/icon-block-icons.js', dirname( __FILE__ ) ),
			$icons_asset['dependencies'],
			$icons_asset['version'],
			true // Very important, otherwise the filter is called too early.
		);

		wp_enqueue_script(
			'mai-icon-block-divider',
			plugins_url( 'build/icon-block-divider.js', dirname( __FILE__ ) ),
			$divider_asset['dependencies'],
			$divider_asset['version'],
			true // Very important, otherwise the filter is called too early.
		);
	}

	/**
	 * Convert viewport units to pixels in icon block height.
	 * This is for the divider functionality so we don't get partial pixel gaps.
	 *
	 * @since 0.1.0
	 *
	 * @param string $block_content The block content.
	 * @param array  $block         The block data.
	 *
	 * @return string
	 */
	function render_block( $block_content, $block ) {
		// Bail if no height.
		if ( ! isset( $block['attrs']['height'] ) ) {
			return $block_content;
		}

		// Bail if height string does not end with 'vh' or 'vw'.
		if ( ! ( str_ends_with( $block['attrs']['height'], 'vh' ) || str_ends_with( $block['attrs']['height'], 'vw' ) ) ) {
			return $block_content;
		}

		// Use WP_HTML_Tag_Processor to modify the style attribute.
		$processor = new WP_HTML_Tag_Processor( $block_content );

		// Get tag.
		if ( $processor->next_tag( [ 'class_name' => 'icon-container' ] ) ) {
			// Get the style attribute and replace the height value with the rounded value.
			$style = $processor->get_attribute( 'style' );
			$style = str_replace( 'height:' . $block['attrs']['height'], 'height:round(' . $block['attrs']['height'] . ', 1px)', $style );

			// Set the style attribute.
			$processor->set_attribute( 'style', $style );
		}

		// Get updated HTML.
		$block_content = $processor->get_updated_html();

		return $block_content;
	}
}
