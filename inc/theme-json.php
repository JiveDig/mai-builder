<?php

namespace Mai\Builder;

defined( 'ABSPATH' ) || exit;

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
 * @since 0.1.0
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