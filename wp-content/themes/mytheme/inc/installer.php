<?php
// Only intercept onboarding redirects on the wizard or its authenticated requests.
add_filter( 'wp_redirect', function ( $url ) {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) { return $url; }
	if ( wp_doing_ajax() && isset( $_POST['installerNonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['installerNonce'] ) ), 'mytheme_installer' )
		&& in_array( $_POST['action'] ?? '', array( 'mytheme_dependency', 'install-plugin' ), true ) ) {
		return false;
	}
	if ( 'themes.php' === $GLOBALS['pagenow'] && 'mytheme-setup' === ( $_GET['page'] ?? '' ) && str_starts_with( $url, admin_url() ) ) {
		$step = 'demos' === ( $_GET['step'] ?? '' ) ? 'demos' : 'plugins';
		return admin_url( 'themes.php?page=mytheme-setup&step=' . $step );
	}
	return $url;
}, PHP_INT_MAX );

function mytheme_dependency_state( $file ) {
	clearstatcache( true, WP_PLUGIN_DIR . '/' . $file );
	if ( ! is_file( WP_PLUGIN_DIR . '/' . $file ) ) { return 'missing'; }
	return is_plugin_active( $file ) ? 'active' : 'installed';
}

function mytheme_install_package( $file ) {
	require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	$api = plugins_api( 'plugin_information', array( 'slug' => dirname( $file ), 'fields' => array( 'sections' => false ) ) );
	if ( is_wp_error( $api ) ) { return $api; }
	$skin = new WP_Ajax_Upgrader_Skin();
	$result = ( new Plugin_Upgrader( $skin ) )->install( $api->download_link );
	foreach ( array( $result, $skin->result, $skin->get_errors() ) as $error ) {
		if ( is_wp_error( $error ) && $error->has_errors() ) { return $error; }
	}
	return $result ? true : new WP_Error( 'filesystem', 'Could not write the plugin using WP_Filesystem.' );
}

function mytheme_dependency_request( $operation ) {
	check_ajax_referer( 'mytheme_installer', 'installerNonce' );
	$file = sanitize_text_field( wp_unslash( $_POST['plugin'] ?? '' ) );
	$plugins = mytheme_dependencies();
	if ( ! isset( $plugins[ $file ] ) || ! current_user_can( 'manage_options' ) ) { wp_send_json_error( array( 'reason' => 'permission' ), 403 ); }
	if ( 'status' === $operation ) { wp_send_json_success( mytheme_dependency_state( $file ) ); }
	if ( ! in_array( $operation, array( 'install', 'activate' ), true ) ) { wp_send_json_error( array( 'reason' => 'invalid' ), 400 ); }
	if ( ! current_user_can( 'activate_plugin', $file ) || ( 'install' === $operation && ! current_user_can( 'install_plugins' ) ) ) {
		wp_send_json_error( array( 'reason' => 'permission' ), 403 );
	}
	global $wpdb;
	// Shared by tabs and administrators. The DB releases the lock if PHP terminates.
	$lock = 'mytheme_install_' . md5( WP_PLUGIN_DIR );
	if ( ! $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', $lock ) ) ) { wp_send_json_error( array( 'reason' => 'busy' ), 409 ); }
	$reason = $error = null;
	$level = ob_get_level();
	ob_start();
	try {
		$state = mytheme_dependency_state( $file );
		if ( 'install' === $operation && 'missing' === $state ) {
			$error = mytheme_install_package( $file );
		} elseif ( 'activate' === $operation && 'active' !== $state ) {
			$required = array( 'persian-woocommerce' => 'woocommerce/woocommerce.php', 'essential-addons-for-elementor-lite' => 'elementor/elementor.php', 'premium-addons-for-elementor' => 'elementor/elementor.php' );
			$parent = $required[ dirname( $file ) ] ?? '';
			$error = $parent && 'active' !== mytheme_dependency_state( $parent ) ? new WP_Error( 'dependency', 'Dependency inactive: ' . $parent ) : activate_plugin( $file );
		}
		$state = mytheme_dependency_state( $file );
		if ( ! is_wp_error( $error ) && ( 'missing' === $state || ( 'activate' === $operation && 'active' !== $state ) ) ) {
			$error = new WP_Error( 'incomplete', 'WordPress did not reach the requested plugin state.' );
		}
	} catch ( Throwable $exception ) {
		$error = new WP_Error( 'exception', $exception->getMessage() );
	} finally {
		while ( ob_get_level() > $level ) { ob_end_clean(); }
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
	}
	if ( is_wp_error( $error ) ) {
		$code = (string) $error->get_error_code();
		error_log( sprintf( '[mytheme] %s %s [%s] %s', $operation, $file, $code, wp_strip_all_tags( $error->get_error_message(), true ) ) );
		$reason = $operation;
		if ( preg_match( '/filesystem|mkdir|copy|writ|permission|fs_|disk_full|remove_old|folder_exists/i', $code ) ) { $reason = 'filesystem'; }
		elseif ( preg_match( '/http|download|plugins_api|connect/i', $code ) ) { $reason = 'download'; }
		elseif ( preg_match( '/php|wordpress_version|incompatible/i', $code ) ) { $reason = 'incompatible'; }
		elseif ( 'dependency' === $code ) { $reason = 'dependency'; }
		wp_send_json_error( array( 'reason' => $reason ) );
	}
	wp_send_json_success( $state );
}
add_action( 'wp_ajax_mytheme_dependency', function () {
	mytheme_dependency_request( sanitize_key( wp_unslash( $_POST['operation'] ?? '' ) ) );
} );
// Keep WordPress's filesystem credential dialog and install queue for shared hosting.
add_action( 'wp_ajax_install-plugin', function () {
	if ( isset( $_POST['installerNonce'] ) ) {
		check_ajax_referer( 'updates' );
		mytheme_dependency_request( 'install' );
	}
}, 0 );
