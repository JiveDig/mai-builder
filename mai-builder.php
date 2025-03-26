<?php

/**
 * Plugin Name:     Mai Builder
 * Plugin URI:      https://bizbudding.com
 * Description:     Core functionality for Mai Theme FSE.
 * Version:         0.2.0
 * Requires PHP:    8.0
 *
 * Author:          JiveDig
 * Author URI:      https://bizbudding.com
 */

namespace Mai\Builder;

// Prevent direct file access.
defined( 'ABSPATH' ) || exit;

// Check PHP version.
if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
	add_action( 'admin_notices', function() {
		echo '<div class="notice notice-error"><p>' .
			esc_html__( 'Mai Plugin requires PHP 8.0 or higher.', 'mai-builder' ) .
			'</p></div>';
	});
	return;
}

// Autoload Composer dependencies.
require_once __DIR__ . '/vendor/autoload.php';

// Include non-class files only.
// require_once __DIR__ . '/inc/setup.php';
// require_once __DIR__ . '/inc/assets.php';
require_once __DIR__ . '/inc/block-settings.php';
require_once __DIR__ . '/inc/theme-json.php';
require_once __DIR__ . '/inc/updater.php';
// Instantiate classes.
Assets::get_instance();

// Load separate core block assets.
add_filter( 'should_load_separate_core_block_assets', '__return_true', 8 );

add_action( 'init', __NAMESPACE__ . '\icon_block_init' );
/**
 * Initialize the Icon Block.
 *
 * @since 0.1.0
 *
 * @return void
 */
function icon_block_init() {
	// Get all registered blocks.
	$blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();

	// Bail if Icon Block plugin is not active.
	if ( ! isset( $blocks['outermost/icon-block'] ) ) {
		return;
	}

	// Instantiate the class.
	IconBlock::get_instance();
}

add_action( 'after_setup_theme', __NAMESPACE__ . '\setup' );
/**
 * Set up theme.
 *
 * @since 0.1.0
 *
 * @return void
 */
function setup() {
	// Remove core block patterns.
	remove_theme_support( 'core-block-patterns' );
}
