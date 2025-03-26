<?php

defined( 'ABSPATH' ) || exit;

/**
 * Config.
 *
 * @since 0.1.0
 *
 * @return array
 */
return [
	// Add or override via {TBD}
	'css' => [
		'reset'    => [ 'frontend', 'editor' ],
		'frontend' => [ 'frontend' ],
		'editor'   => [ 'editor' ],
	],
	// Add or override via {TBD}
	'blocks' => [
		/**
		 * Add block CSS.
		 */
		'css' => [
			'navigation' => [ 'core/navigation' ],
		],
		/**
		 * Add block styles.
		 */
		'styles' => [
			'heading' => [
				'label'   => __( 'Heading', 'mai-builder' ),
				'blocks'  => [ 'core/paragraph' ],
				'name'    => 'heading',
				'default' => false,
			],
			'subheading' => [
				'label'   => __( 'Subheading', 'mai-builder' ),
				'blocks'  => [ 'core/heading', 'core/paragraph' ],
				'name'    => 'subheading',
				'default' => false,
			],
			// 'button-primary' => [
			// 	'label'   => __( 'Primary', 'mai-builder' ),
			// 	'blocks'  => [ 'core/button' ],
			// 	'name'    => 'primary',
			// 	'default' => true,
			// ],
			'button-secondary' => [
				'label'   => __( 'Secondary', 'mai-builder' ),
				'blocks'  => [ 'core/button' ],
				'name'    => 'secondary',
				'default' => false,
			],
			'button-link' => [
				'label'   => __( 'Link', 'mai-builder' ),
				'blocks'  => [ 'core/link' ],
				'name'    => 'link',
				'default' => false,
			],
			'icon-divider' => [
				'label'   => __( 'Divider', 'mai-builder' ),
				'blocks'  => [ 'outermost/icon-block' ],
				'name'    => 'divider',
				'default' => false,
			],
		],
		/**
		 * Add block variations.
		 *
		 * @link https://developer.wordpress.org/block-editor/reference-guides/block-api/block-variations/#defining-a-block-variation
		 */
		'variations' => [
			// 'icon-divider' => [
			// 	'title'       => __( 'Divider', 'mai-builder' ),
			// 	'blocks'      => [ 'outermost/icon-block' ],
			// 	'name'        => 'divider',
			// 	'description' => __( 'An SVG divider for the icon block.', 'mai-builder' ),
			// 	'scope'       => [ 'block','inserter', 'transform' ],
			// 	'isDefault'   => false,
			// 	'isActive'    => [
			// 		'width',
			// 	],
			// 	'attributes'  => array(
			// 		'width'  => '100%',
			// 		'height' => '200px',
			// 	),
			// ],
		],
	],
	'icons' => [
		plugin_dir_path(__FILE__) . 'assets/icons',
		get_template_directory() . '/mai/icons',
	],
	// TODO: Patterns.
	'patterns' => [],
];