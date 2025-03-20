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
		'reset'  => [ 'frontend', 'editor' ],
		'global' => [ 'frontend' ],
	],
	// Add or override via {TBD}
	'blocks' => [
		'css' => [
			'navigation' => [ 'core/navigation' ],
		],
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
			'button-primary' => [
				'label'   => __( 'Primary', 'mai-builder' ),
				'blocks'  => [ 'core/button' ],
				'name'    => 'primary',
				'default' => true,
			],
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
		],
	],
];