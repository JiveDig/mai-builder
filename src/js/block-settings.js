import { __ } from '@wordpress/i18n';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';
import { InspectorControls } from '@wordpress/block-editor';
import { SelectControl } from '@wordpress/components';

/**
 * Blocks that allow img loading attribute settings.
 *
 * @since 0.2.0
 *
 * @type {Array}
 */
const allowedBlocks = ['core/cover', 'core/image', 'core/site-logo', 'core/post-featured-image' ];

/**
 * Add custom img loading attribute to allowed blocks.
 *
 * @since 0.2.0
 *
 * @param {Object} settings Original block settings.
 * @param {string} name     Block name.
 *
 * @return {Object} Modified block settings.
 */
addFilter(
	'blocks.registerBlockType',
	'mai/add-loading-attribute',
	(settings, name) => {
		if (!allowedBlocks.includes(name)) {
			return settings;
		}

		return {
			...settings,
			attributes: {
				...settings.attributes,
				imgLoading: {
					type: 'string',
					default: '',
				},
			},
		};
	}
);

/**
 * Add img loading attribute control to block inspector settings.
 *
 * Adds a select control to allowed blocks that allows users to choose
 * between default, lazy, and eager loading strategies for images.
 *
 * @since 0.2.0
 */
addFilter(
	'editor.BlockEdit',
	'mai/with-loading-attribute',
	createHigherOrderComponent((BlockEdit) => (props) => {
		if (!allowedBlocks.includes(props.name)) {
			return <BlockEdit {...props} />;
		}

		const { attributes, setAttributes } = props;

		return (
			<>
				<BlockEdit {...props} />
				<InspectorControls>
					<div style={{ padding: '0px 16px 8px' }}>
						<SelectControl
							label={__('Image Loading')}
							value={attributes.imgLoading || ''}
							options={[
								{ label: __('Default'), value: '' },
								{ label: __('Lazy (for offscreen images)'), value: 'lazy' },
								{ label: __('Eager (loads immediately)'), value: 'eager' },
							]}
							onChange={(value) => setAttributes({ imgLoading: value })}
							help={__('Controls how the browser loads this image.')}
						/>
					</div>
				</InspectorControls>
			</>
		);
	}, 'withLoadingAttribute')
);

// /**
//  * Add img loading attribute to saved block HTML.
//  *
//  * Modifies the saved output of allowed blocks to include
//  * the loading attribute and fetchpriority when appropriate.
//  *
//  * @since 0.2.0
//  *
//  * @param {Object} element   The saved element.
//  * @param {Object} blockType Block type settings.
//  * @param {Object} attributes Block attributes.
//  *
//  * @return {Object} Modified element.
//  */
// addFilter(
// 	'blocks.getSaveElement',
// 	'mai/add-loading-attribute',
// 	(element, blockType, attributes) => {
// 		if (!allowedBlocks.includes(blockType.name)) {
// 			return element;
// 		}

// 		// Bail if no img loading attribute is set.
// 		if (!attributes.imgLoading) {
// 			return element;
// 		}

// 		// Bail if it's a dynamic block.
// 		if (['core/post-featured-image', 'core/site-logo'].includes(blockType.name)) {
// 			return element;
// 		}

// 		// Set children variable.
// 		let children = null;

// 		// Handle cover block.
// 		if ('core/cover' === blockType.name) {
// 			// Get children.
// 			children = element?.props?.children;
// 		}

// 		// Handle image block.
// 		if ('core/image' === blockType.name) {
// 			// Get children.
// 			children = element?.props?.children?.props?.children;
// 		}

// 		// Bail if children is not an array.
// 		if (!Array.isArray(children)) {
// 			return element;
// 		}

// 		// Map through children and modify img elements.
// 		const newChildren = children.map(child => {
// 			// Bail if child is not an img element.
// 			if (!child || child.type !== 'img') {
// 				return child;
// 			}

// 			return {
// 				...child,
// 				props: {
// 					...child.props,
// 					loading: attributes.imgLoading,
// 					// Add fetchpriority="high" for eager loading.
// 					...(attributes.imgLoading === 'eager' && { fetchpriority: 'high' })
// 				}
// 			};
// 		});

// 		// Return new element with modified children.
// 		return {
// 			...element,
// 			props: {
// 				...element.props,
// 				children: newChildren
// 			}
// 		};
// 	}
// );