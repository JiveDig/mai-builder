import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Check if divider icons exist and register the variation if they do.
 *
 * @since TBD
 *
 * @return {void}
 */
async function init() {
	try {
		// Check for icons in the dividers directory.
		const response = await apiFetch({
			path: '/mai/v1/icons/dividers',
		});

		// Bail if no icons.
		if ( ! response?.icons?.length ) {
			return;
		}

		// Register the divider block variation.
		wp.blocks.registerBlockVariation( 'outermost/icon-block', {
			name: 'divider',
			title: __( 'Divider', 'mai-builder' ),
			attributes: {
				itemsJustification: 'center',
				width: '100vw',
				height: '5vw',
				marginTop: '0',
				marginBottom: '0',
				className: 'is-style-divider',
			},
			isActive: (blockAttributes, variationAttributes) => {
				return blockAttributes?.className?.includes('is-style-divider');
			}
		});

		// Register the divider block style.
		wp.blocks.registerBlockStyle( 'outermost/icon-block', {
			name: 'divider',
			label: __( 'Divider', 'mai-builder' ),
		});

	} catch (error) {
		console.error('Error checking for divider icons:', error);
	}
}

// Initialize when DOM is ready.
wp.domReady(init);
