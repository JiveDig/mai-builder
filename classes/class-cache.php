<?php
/**
 * Cache helper class using WordPress transients.
 *
 * This class provides a simple interface for caching data using WordPress transients,
 * automatically leveraging the object cache when available or falling back to the database.
 * It allows for retrieving, setting, and forgetting cached values with a prefixed key system.
 * Unlike static implementations, this class can be instantiated for each use, though it
 * maintains no instance-specific state beyond configuration.
 *
 * @example Retrieve and cache an expensive operation:
 * ```
 * use Mai\Builder\Cache;
 *
 * $value = (new Cache)->remember(
 *     'expensive_data',
 *     function () {
 *         // Simulate expensive processing.
 *         sleep(2);
 *         return 'Processed: ' . time();
 *     },
 *     3600 // Cache for 1 hour.
 * );
 * echo $value; // Outputs "Processed: 1234567890" (first call takes 2 seconds).
 * ```
 *
 * @example Retrieve and remove cached data:
 * ```
 * $value = (new Cache)->forget('expensive_data');
 * echo $value ?: 'No data found'; // Outputs cached value or "No data found".
 * ```
 *
 * @version    0.1.0
 * @author     JiveDig
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright  Copyright (c) 2025, JiveDig
 * @package    Cache
 */

namespace Mai\Builder;

defined( 'ABSPATH' ) || exit;

/**
 * Cache class.
 *
 * @since 0.1.0
 */
class Cache {
	/**
	 * Prefix for the cache key.
	 *
	 * @var string
	 */
	protected $prefix;

	/**
	 * Whether to use debug mode.
	 *
	 * @var bool
	 */
	public $debug;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function __construct() {
		$this->prefix = 'mai';
		$this->debug  = defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

	/**
	 * Retrieve a value from transients, generating and caching it if not found.
	 *
	 * Attempts to retrieve a value from the cache using the provided key. If the value
	 * is not found or has expired, the callback is executed to generate the value,
	 * which is then cached for future use. The key is automatically prefixed to avoid
	 * collisions with other transients.
	 *
	 * @since 0.1.0
	 *
	 * @param string   $key      The cache key (without prefix).
	 * @param callable $callback The callback function to generate the value if not cached.
	 * @param int      $expire   Expiration time in seconds.
	 *
	 * @return mixed The cached value or the result of the callback.
	 */
	public function remember( $key, $callback, $expire ) {
		$cached = $this->get_transient( $key );

		if ( false !== $cached ) {
			return $cached;
		}

		$value = $callback();

		if ( ! is_wp_error( $value ) ) {
			$this->set_transient( $key, $value, absint( $expire ) );
		}

		return $value;
	}

	/**
	 * Retrieve and delete a value from transients.
	 *
	 * Retrieves a value from the cache using the provided key and, if found, deletes it
	 * from the cache immediately after retrieval. Returns a default value if the key
	 * is not found. The key is automatically prefixed.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key     The cache key (without prefix).
	 * @param mixed  $default Optional. Value to return if the key is not found. Default null.
	 *
	 * @return mixed The cached value if found, otherwise the default value.
	 */
	public function forget( $key, $default = null ) {
		$cached = $this->get_transient( $key );

		if ( false !== $cached ) {
			$this->delete_transient( $key );

			return $cached;
		}

		return $default;
	}

	/**
	 * Get a value from transients.
	 *
	 * Retrieves a value from either regular or site-wide transients based on the provided
	 * key. Returns false if caching is disabled or the key is not found. The key is
	 * prefixed internally to ensure uniqueness.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key  The cache key (without prefix).
	 * @param bool   $site Whether to use site-wide transients (multisite).
	 *
	 * @return mixed The cached value, or false if not found or caching is disabled.
	 */
	protected function get_transient( $key ) {
		if ( ! $this->can_cache() || $this->debug ) {
			return false;
		}

		return get_transient( $this->key( $key ) );
	}

	/**
	 * Set a value in transients.
	 *
	 * Stores a value in either regular or site-wide transients with the provided key and
	 * expiration time. Returns false if caching is disabled. The key is prefixed
	 * internally to ensure uniqueness.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key    The cache key (without prefix).
	 * @param mixed  $value  The value to store in the cache.
	 * @param int    $expire Expiration time in seconds.
	 *
	 * @return bool True if the value was set, false otherwise.
	 */
	protected function set_transient( $key, $value, $expire ) {
		if ( ! $this->can_cache() || $this->debug ) {
			return false;
		}

		return set_transient( $this->key( $key ), $value, absint( $expire ) );
	}

	/**
	 * Delete a value from transients.
	 *
	 * Removes a value from either regular or site-wide transients using the provided key.
	 * The key is prefixed internally to match the stored value.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key  The cache key (without prefix).
	 *
	 * @return bool True if the transient was deleted, false otherwise.
	 */
	protected function delete_transient( $key ) {
		return delete_transient( $this->key( $key ) );
	}

	/**
	 * Generate a prefixed cache key.
	 *
	 * Combines the class prefix with the provided key name to create a unique cache key.
	 * This ensures that cache entries from this class do not conflict with others.
	 *
	 * @since 0.1.0
	 *
	 * @param string $name The base key name.
	 *
	 * @return string The prefixed key (e.g., "mai_cache_$name").
	 */
	protected function key( $name ) {
		return "{$this->prefix}_{$name}";
	}

	/**
	 * Check if caching is enabled.
	 *
	 * Determines whether caching is allowed, based on a filterable setting. This can be
	 * used to disable caching globally for debugging or testing purposes.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if caching is enabled, false otherwise.
	 */
	protected function can_cache() {
		/**
		 * Filter to enable or disable caching.
		 *
		 * @param bool $enabled Whether caching is enabled.
		 */
		return apply_filters( "{$this->prefix}_can_cache", true );
	}
}