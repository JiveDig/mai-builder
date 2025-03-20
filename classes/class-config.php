<?php

namespace Mai\Builder;

defined( 'ABSPATH' ) || exit;

/**
 * Config class.
 *
 * @since 0.1.0
 */
class Config {

	/**
	 * Plugin config.
	 *
	 * @var array
	 */
	private array $plugin_config;

	/**
	 * Theme config.
	 *
	 * @var array
	 */
	private array $theme_config;

	/**
	 * Merged config.
	 *
	 * @var array
	 */
	private array $config;

	/**
	 * Instance of the class.
	 *
	 * @var Config|null
	 */
	private static ?Config $instance = null;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private function __construct() {
		$this->plugin_config = $this->get_plugin_config();
		$this->theme_config  = $this->get_theme_config();
		$this->config        = $this->get_merged_config();
	}

	/**
	 * Get instance of the class.
	 *
	 * @since 0.1.0
	 *
	 * @return Config
	 */
	public static function get_instance(): Config {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get the merged config or a specific value using dot notation.
	 *
	 * @since 0.1.0
	 *
	 * @param string|null $key Optional. Dot notation key (e.g., 'parent.child.key').
	 *
	 * @return mixed Returns entire config array if no key provided, specific value if key exists, null if key doesn't exist.
	 */
	public static function get( ?string $key = null ): mixed {
		return self::get_from_array(self::get_instance()->config, $key);
	}

	/**
	 * Get the plugin config or a specific value using dot notation.
	 *
	 * @since 0.1.0
	 *
	 * @param string|null $key Optional. Dot notation key (e.g., 'parent.child.key').
	 *
	 * @return mixed Returns entire config array if no key provided, specific value if key exists, null if key doesn't exist.
	 */
	public static function get_plugin( ?string $key = null ): mixed {
		return self::get_from_array(self::get_instance()->plugin_config, $key);
	}

	/**
	 * Get the theme config or a specific value using dot notation.
	 *
	 * @since 0.1.0
	 *
	 * @param string|null $key Optional. Dot notation key (e.g., 'parent.child.key').
	 *
	 * @return mixed Returns entire config array if no key provided, specific value if key exists, null if key doesn't exist.
	 */
	public static function get_theme( ?string $key = null ): mixed {
		return self::get_from_array(self::get_instance()->theme_config, $key);
	}

	/**
	 * Get a value from an array using dot notation.
	 *
	 * @since 0.1.0
	 *
	 * @param array       $array The array to search in.
	 * @param string|null $key   Optional. Dot notation key (e.g., 'parent.child.key').
	 *
	 * @return mixed Returns entire array if no key provided, specific value if key exists, null if key doesn't exist.
	 */
	private static function get_from_array( array $array, ?string $key = null ): mixed {
		if ( null === $key ) {
			return $array;
		}

		$keys  = explode( '.', $key );
		$value = $array;

		foreach ( $keys as $current ) {
			if ( ! is_array( $value ) || ! isset( $value[ $current ] ) ) {
				return null;
			}

			$value = $value[ $current ];
		}

		return $value;
	}

	/**
	 * Returns the merged config.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	private function get_merged_config(): array {
		$config = array_replace_recursive( $this->plugin_config, $this->theme_config );

		return apply_filters( 'mai_config', $config );
	}

	/**
	 * Returns the plugin's full config.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	private function get_plugin_config(): array {
		return (array) include_once plugin_dir_path(__DIR__) . '/config.php';
	}

	/**
	 * Returns the active theme's full config.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	private function get_theme_config(): array {
		$path = get_template_directory() . '/config.php';

		return is_readable( $path ) ? (array) include_once $path : [];
	}
}