wp.domReady(() => {
	const { __ } = wp.i18n;
	const { addFilter } = wp.hooks;
	const { apiFetch } = wp;
	const { registerStore, select, dispatch } = wp.data;

	/**
	 * Register a custom store for managing theme icons.
	 * This store handles:
	 * - Loading state management
	 * - Icon data caching during page session
	 * - Race condition prevention
	 * - Centralized state management
	 */
	registerStore('mai/icon-block-icons', {
		/**
		 * Reducer for managing store state.
		 *
		 * @param {Object} state Current state with icons and loading status.
		 * @param {Object} action Action object containing type and payload.
		 * @return {Object} Updated state.
		 */
		reducer: (state = { icons: null, isLoading: false }, action) => {
			switch (action.type) {
				case 'SET_ICONS':
					return { ...state, icons: action.icons };
				case 'SET_LOADING':
					return { ...state, isLoading: action.isLoading };
				default:
					return state;
			}
		},

		/**
		 * Action creators for updating store state.
		 */
		actions: {
			/**
			 * Sets the theme icons in the store.
			 *
			 * @param {Object|null} icons The theme icons object or null if error.
			 * @return {Object} Action object.
			 */
			setIcons: (icons) => ({ type: 'SET_ICONS', icons }),

			/**
			 * Sets the loading state.
			 *
			 * @param {boolean} isLoading Whether icons are being loaded.
			 * @return {Object} Action object.
			 */
			setLoading: (isLoading) => ({ type: 'SET_LOADING', isLoading }),
		},

		/**
		 * Selectors for accessing store state.
		 */
		selectors: {
			/**
			 * Gets the current theme icons.
			 *
			 * @param {Object} state Current store state.
			 * @return {Object|null} Theme icons object or null.
			 */
			getIcons: (state) => state.icons,

			/**
			 * Gets the current loading state.
			 *
			 * @param {Object} state Current store state.
			 * @return {boolean} Whether icons are being loaded.
			 */
			isLoading: (state) => state.isLoading,
		},

		/**
		 * Resolvers handle async data fetching.
		 * They ensure data is only fetched once and cached in the store.
		 */
		resolvers: {
			async getIcons() {
				const store = select('mai/icon-block-icons');

				// Don't fetch if we're loading or already have icons
				if (store.isLoading() || store.getIcons()) {
					console.log('Skipping icon fetch - already loading or cached:', {
						isLoading: store.isLoading(),
						hasIcons: !!store.getIcons()
					});
					return;
				}

				// Start the timer.
				console.time('Load Theme Icons');

				// Set the loading state.
				dispatch('mai/icon-block-icons').setLoading(true);

				try {
					const data = await apiFetch({ path: '/mai/v1/icons' });

					// If the data is invalid, set the icons to null.
					if (!data || !data.icons || !data.categories) {
						console.error('Invalid data structure received from API');
						dispatch('mai/icon-block-icons').setIcons(null);
						return;
					}

					// Create a properly structured icon set object that matches the Icon Block's expectations
					const iconSet = {
						isDefault: false,
						type: 'theme',
						title: __('Theme Icons', 'mai-builder'),
						icons: data.icons,
						categories: data.categories
					};

					dispatch('mai/icon-block-icons').setIcons(iconSet);
				} catch (error) {
					console.error('Error loading theme icons:', error);
					console.error('Error details:', {
						message: error.message,
						stack: error.stack,
						response: error.response
					});
					dispatch('mai/icon-block-icons').setIcons(null);
				} finally {
					dispatch('mai/icon-block-icons').setLoading(false);

					// End the timer.
					console.timeEnd('Load Theme Icons');
				}
			}
		}
	});

	/**
	 * Adds custom theme icons to the Icon Block's icon list.
	 *
	 * @param {Array} icons The original array of icon sets.
	 * @return {Array} The combined array of icon sets.
	 */
	function addCustomIcons(icons) {
		const iconSet = select('mai/icon-block-icons').getIcons();

		// Add our icon set to the array of icon sets.
		return iconSet ? [...icons, iconSet] : icons;
	}

	/**
	 * Inject our custom icons into the Icon Block.
	 *
	 * @param {Array} icons The original array of icon sets.
	 * @return {Array} The combined array of icon sets.
	 */
	addFilter(
		'iconBlock.icons',
		'mai/icon-block-icons',
		addCustomIcons
	);
});