<?php

namespace Mai\Builder;

defined( 'ABSPATH' ) || exit;

add_filter( 'render_block_core/cover',               __NAMESPACE__ . '\render_block_add_loading_attribute', 10, 2 );
add_filter( 'render_block_core/image',               __NAMESPACE__ . '\render_block_add_loading_attribute', 10, 2 );
add_filter( 'render_block_core/site-logo',           __NAMESPACE__ . '\render_block_add_loading_attribute', 10, 2 );
add_filter( 'render_block_core/post-featured-image', __NAMESPACE__ . '\render_block_add_loading_attribute', 10, 2 );
/**
 * Render the core/site-logo block.
 *
 * @since 0.2.0
 *
 * @param string $block_content The block content.
 * @param array  $block         The block.
 *
 * @return string The block content.
 */
function render_block_add_loading_attribute( $block_content, $block ) {
	// Get the img loading attribute.
	$loading = $block['attrs']['imgLoading'] ?? '';

	// Bail if no loading attribute is set.
	if ( ! $loading ) {
		return $block_content;
	}

	// Set up tag processor.
	$tags = new \WP_HTML_Tag_Processor( $block_content );

	// Set up args.
	$args = [
		'tag_name' => 'img',
	];

	// Add class check for cover block.
	// This insures only the background image is handled,
	// not any inner blocks.
	if ( 'core/cover' === $block['blockName'] ) {
		$args['class'] = 'wp-block-cover__background';
	}

	// Loop through tags.
	while ( $tags->next_tag( [ 'tag_name' => 'img' ] ) ) {
		// Add loading attribute.
		$tags->set_attribute( 'loading', $loading );

		// Add fetchpriority="high" for eager loading.
		if ( 'eager' === $loading ) {
			$tags->set_attribute( 'fetchpriority', 'high' );
		}
	}

	// Get updated block content.
	$block_content = $tags->get_updated_html();

	return $block_content;
}
