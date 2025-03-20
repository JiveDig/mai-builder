/**
 * WordPress dependencies
 */
import { registerBlockStyle, unregisterBlockStyle } from '@wordpress/blocks';
import domReady from '@wordpress/dom-ready';

domReady(() => {
	// // Register custom block styles
	// registerBlockStyle('core/button', {
	//     name: 'secondary',
	//     label: 'Secondary',
	// });

	// Unregister default styles if needed
	unregisterBlockStyle( 'core/button', 'fill' );
});
