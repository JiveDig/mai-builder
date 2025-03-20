<?php

namespace HeritageAds;
use MatthiasMullie\Minify;

include_once __DIR__ . '/vendor/autoload.php';

add_action( 'after_setup_theme', __NAMESPACE__ . '\setup' );
/**
 * Set up theme defaults and register various WordPress features.
 */
function setup() {
	// Remove core block patterns.
	remove_theme_support( 'core-block-patterns' );
}

add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_style_sheet' );
/**
 * Enqueue styles.
 */
function enqueue_style_sheet() {
	$css = hmg_get_global_styles();

	// Bail if no CSS.
	if ( ! $css ) {
		return;
	}

	// Register and enqueue the global styles.
	wp_register_style( 'hmg-global', false );
	wp_enqueue_style( 'hmg-global' );
	wp_add_inline_style( 'hmg-global', $css );
}

/**
 * Get global styles.
 *
 * @return string
 */
function hmg_get_global_styles() {
	$debug  = defined( 'WP_SCRIPT_DEBUG' ) && WP_SCRIPT_DEBUG;
	$key    = 'hmg_global_styles';
	$styles = get_transient( $key );

	// If this source path hasn't been processed yet.
	if ( false === $styles || $debug ) {
		$src_styles = [];
		$src_paths  = glob( get_template_directory() . '/assets/css/global/*.css' );

		// Loop through each source path.
		foreach ( $src_paths as $src_path ) {
			// Get and minify the CSS.
			$src_css  = file_get_contents( $src_path );

			// If not debugging.
			if ( ! $debug ) {
				// Minify the CSS.
				$minifier = new Minify\CSS( $src_css );
				$file_css = $minifier->minify();
			}
			// If debugging.
			else {
				$file_css = $src_css;
			}

			// Add to styles array and update cache.
			$src_styles[ $src_path ] = $file_css;
		}

		// Remove empty styles and stringify the array.
		$src_styles = array_filter( $src_styles );
		$styles    = implode( '', array_values( $src_styles ) );

		// Update the cache.
		set_transient( $key, $styles, DAY_IN_SECONDS );
	}

	// Return all combined styles.
	return $styles;
}

add_filter( 'wp_theme_json_data_default', __NAMESPACE__ . '\theme_json_data_default' );
/**
 * Filter the default theme.json data.
 *
 * Removes default duotone, gradients and color palette values provided by WordPress core.
 * This allows the theme to define its own color settings without also loading core defaults as CSS variables.
 *
 * wp_theme_json_data_default: hooks into the default data provided by WordPress
 * wp_theme_json_data_blocks: hooks into the data provided by the blocks
 * wp_theme_json_data_theme: hooks into the data provided by the theme
 * wp_theme_json_data_user: hooks into the data provided by the user
 *
 * @param WP_Theme_JSON_Data $theme_json The default theme.json data.
 *
 * @return WP_Theme_JSON_Data The modified theme.json data.
 */
function theme_json_data_default( $theme_json ) {
	// Get JSON data as an array.
	$data = $theme_json->get_data();

	// Values to check.
	$values = [
		'duotone',
		'gradients',
		'palette',
	];

	// Remove duotone, gradients, and palette values.
	foreach ( $values as $value ) {
		if ( isset( $data['settings']['color'][ $value ]['default'] ) ) {
			$data['settings']['color'][ $value ]['default'] = [];
		}
	}

	// Update the theme JSON data.
	$theme_json = $theme_json->update_with( $data );

	// Return the modified theme JSON data.
	return $theme_json;
}

// add_action( 'init', __NAMESPACE__ . '\add_block_styles' );
/**
 * Add block styles.
 *
 * @since 2.0.0
 *
 * @return void
 */
function add_block_styles() {

	/************************
	 * Enqueue block styles.
	 ************************/

	wp_enqueue_block_style(
		'core/button',
		[
			'handle' => 'hmg-button',
			'src'    => get_theme_file_uri( 'assets/css/blocks/core/button.css' ),
			'path'   => get_theme_file_path( 'assets/css/blocks/core/button.css' ),
		]
	);

	// wp_enqueue_block_style(
	// 	'core/media-text',
	// 	[
	// 		'handle' => 'hmg-media-text',
	// 		'src'    => get_theme_file_uri( 'assets/css/blocks/core/media-text.css' ),
	// 		'path'   => get_theme_file_path( 'assets/css/blocks/core/media-text.css' ),
	// 	]
	// );

	wp_enqueue_block_style(
		'core/navigation',
		[
			'handle' => 'hmg-navigation',
			'src'    => get_theme_file_uri( 'assets/css/blocks/core/navigation.css' ),
			'path'   => get_theme_file_path( 'assets/css/blocks/core/navigation.css' ),
		]
	);

	$subheading = [
		'uri'  => get_theme_file_uri( 'assets/css/blocks/core/subheading.css' ),
		'path' => get_theme_file_path( 'assets/css/blocks/core/subheading.css' ),
	];

	wp_enqueue_block_style(
		'core/paragraph',
		[
			'handle' => 'hmg-subheading',
			'src'    => $subheading['uri'],
			'path'   => $subheading['path'],
		]
	);

	wp_enqueue_block_style(
		'core/heading',
		[
			'handle' => 'hmg-subheading',
			'src'    => $subheading['uri'],
			'path'   => $subheading['path'],
		]
	);

	/************************
	 * Register block styles.
	 ************************/

	register_block_style(
		'core/button',
		[
			'name'  => 'secondary',
			'label' => __( 'Secondary', 'heritageads' ),
		]
	);

	register_block_style(
		'core/paragraph',
		[
			'name'         => 'subheading',
			'label'        => __( 'Subheading', 'heritageads' ),
			'style_handle' => 'hmg-subheading',
		]
	);

	register_block_style(
		'core/heading',
		[
			'name'         => 'subheading',
			'label'        => __( 'Subheading', 'heritageads' ),
			'style_handle' => 'hmg-subheading',
		]
	);
}

// add_filter( 'render_block_core/post-content', __NAMESPACE__ . '\use_blog_page_content', 10, 2 );
/**
 * Use the blog page content for the Content block when used in Blog Home template.
 *
 * @since 2.0.0
 *
 * @param string $block_content The block content about to be rendered.
 * @param array  $block         The block attributes.
 *
 * @return string
 */
function use_blog_page_content( $block_content, $block ) {
	// Bail if not the main query on the blog page.
	if ( ! ( is_home() && is_main_query() ) ) {
		return $block_content;
	}

	// Get the static posts page ID.
	$page_id = get_option( 'page_for_posts' );

	// Bail if no page ID.
	if ( ! $page_id ) {
		return $block_content;
	}

	// Get the post content.
	$post = get_post( $page_id );

	// Return the post content or the block content.
	return $post ? do_blocks( $post->post_content ) : $block_content;
}
