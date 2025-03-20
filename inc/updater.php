<?php

namespace Mai\Builder;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

add_action( 'plugins_loaded', __NAMESPACE__ . '\init' );
/**
 * Initialize the updater.
 *
 * @since 0.1.0
 *
 * @return void
 */
function init() {
	$updater = PucFactory::buildUpdateChecker( 'https://github.com/maithemewp/mai-builder/', plugin_dir_path(__DIR__) . 'mai-builder.php', 'mai-builder' );
	$updater->setBranch( 'main' );

	// Maybe set github api token.
	if ( defined( 'MAI_GITHUB_API_TOKEN' ) ) {
		$updater->setAuthentication( MAI_GITHUB_API_TOKEN );
	}
}
