<?php
/** Bounded diagnostic logging. Never write raw request bodies, cookies, URLs or credentials. */
function mytheme_log_root() {
	static $root = null;
	if ( null !== $root ) { return $root; }
	$outside = trailingslashit( dirname( untrailingslashit( ABSPATH ) ) ) . 'mytheme-logs-' . substr( hash( 'sha256', ABSPATH . AUTH_KEY ), 0, 12 );
	$inside = trailingslashit( WP_CONTENT_DIR ) . 'mytheme-private-logs-' . substr( hash( 'sha256', ABSPATH . AUTH_KEY ), 0, 12 );
	$root = is_dir( $outside ) || wp_mkdir_p( $outside ) ? $outside : $inside;
	if ( ! is_dir( $root ) && ! wp_mkdir_p( $root ) ) { $root = ''; return $root; }
	foreach ( array( 'index.php' => '<?php // Silence is golden.', '.htaccess' => "Require all denied\nDeny from all\n", 'web.config' => '<configuration><system.webServer><security><authorization><deny users="*" /></authorization></security></system.webServer></configuration>' ) as $name => $contents ) {
		$file = trailingslashit( $root ) . $name;
		if ( ! file_exists( $file ) ) { @file_put_contents( $file, $contents ); }
	}
	return $root;
}

function mytheme_log_component( $file ) {
	$file = wp_normalize_path( (string) $file );
	$plugins = wp_normalize_path( WP_PLUGIN_DIR ) . '/';
	if ( 0 === strpos( $file, $plugins ) ) {
		$parts = explode( '/', substr( $file, strlen( $plugins ) ) );
		return sanitize_key( $parts[0] );
	}
	if ( 0 === strpos( $file, wp_normalize_path( get_template_directory() ) . '/' ) ) { return 'mytheme'; }
	return 'wordpress';
}

function mytheme_log_event( $level, $code, $message, $file = '' ) {
	static $busy = false;
	if ( $busy ) { return; }
	$busy = true;
	try {
		$root = mytheme_log_root();
		if ( ! $root ) { return; }
		$component = mytheme_log_component( $file );
		$folder = trailingslashit( $root ) . $component;
		if ( ! is_dir( $folder ) && ! wp_mkdir_p( $folder ) ) { return; }
		$source = wp_normalize_path( (string) $file );
		$source = 0 === strpos( $source, wp_normalize_path( ABSPATH ) ) ? substr( $source, strlen( wp_normalize_path( ABSPATH ) ) ) : basename( $source );
		$message = wp_strip_all_tags( (string) $message );
		// PHP fatal messages can contain stack traces with function arguments and secrets.
		$message = strtok( $message, "\r\n" ) ?: '';
		$message = preg_replace( '~(?:https?://|/)[^\s]+~u', '[مسیر]', $message );
		$message = preg_replace( '/\b(?:password|token|secret|nonce|key)\s*[:=]\s*\S+/iu', '[مقدار محرمانه]', $message );
		$short_message = function_exists( 'mb_substr' ) ? mb_substr( $message, 0, 500 ) : substr( $message, 0, 500 );
		$entry = array( 'time' => gmdate( 'c' ), 'level' => sanitize_key( $level ), 'code' => sanitize_key( $code ), 'component' => $component, 'source' => sanitize_text_field( $source ), 'message' => $short_message );
		$path = $folder . '/' . gmdate( 'Y-m-d' ) . '.jsonl';
		if ( is_file( $path ) && filesize( $path ) >= 2 * 1024 * 1024 ) { return; }
		$handle = @fopen( $path, 'ab' );
		if ( $handle ) {
			if ( flock( $handle, LOCK_EX ) ) { fwrite( $handle, wp_json_encode( $entry, JSON_UNESCAPED_UNICODE ) . "\n" ); flock( $handle, LOCK_UN ); }
			fclose( $handle );
		}
	} finally { $busy = false; }
}

set_error_handler( static function ( $severity, $message, $file ) {
	if ( error_reporting() & $severity ) {
		$level = in_array( $severity, array( E_WARNING, E_USER_WARNING, E_CORE_WARNING, E_COMPILE_WARNING ), true ) ? 'warning' : 'notice';
		mytheme_log_event( $level, 'php_' . $severity, $message, $file );
	}
	return false; // Preserve WordPress/PHP's normal error handling.
} );
register_shutdown_function( static function () {
	$error = error_get_last();
	if ( $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ), true ) ) {
		mytheme_log_event( 'critical', 'php_' . $error['type'], $error['message'], $error['file'] );
	}
} );
add_action( 'http_api_debug', static function ( $response, $context, $class, $args, $url ) {
	if ( 'response' !== $context || ! is_wp_error( $response ) ) { return; }
	$host = wp_parse_url( $url, PHP_URL_HOST );
	mytheme_log_event( 'warning', 'http_' . $response->get_error_code(), 'درخواست HTTP به ' . sanitize_text_field( $host ) . ' ناموفق بود: ' . $response->get_error_message(), '' );
}, 10, 5 );

add_action( 'wp_enqueue_scripts', static function () {
	wp_enqueue_script( 'mytheme-diagnostics', get_template_directory_uri() . '/inc/logger.js', array(), wp_get_theme()->get( 'Version' ), true );
	wp_localize_script( 'mytheme-diagnostics', 'mythemeDiagnostics', array( 'url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'mytheme_client_log' ) ) );
} );

function mytheme_client_log_ajax() {
	if ( ! check_ajax_referer( 'mytheme_client_log', '_ajax_nonce', false ) ) { wp_send_json_error( array( 'message' => 'درخواست نامعتبر است.' ), 403 ); }
	$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
	$key = 'mytheme_log_rate_' . substr( hash( 'sha256', $ip . AUTH_KEY ), 0, 32 );
	$count = (int) get_transient( $key );
	if ( $count >= 20 ) { wp_send_json_error( array( 'message' => 'محدودیت ثبت رویداد.' ), 429 ); }
	set_transient( $key, $count + 1, 10 * MINUTE_IN_SECONDS );
	$code = sanitize_key( wp_unslash( $_POST['code'] ?? 'js_error' ) );
	$message = sanitize_text_field( wp_unslash( $_POST['message'] ?? '' ) );
	$source = sanitize_file_name( basename( wp_parse_url( esc_url_raw( wp_unslash( $_POST['source'] ?? '' ) ), PHP_URL_PATH ) ?: '' ) );
	mytheme_log_event( 'warning', $code, $message, $source );
	wp_send_json_success();
}
add_action( 'wp_ajax_mytheme_client_log', 'mytheme_client_log_ajax' );
add_action( 'wp_ajax_nopriv_mytheme_client_log', 'mytheme_client_log_ajax' );

function mytheme_recent_logs( $limit = 50 ) {
	$root = mytheme_log_root();
	if ( ! $root ) { return array(); }
	$events = array();
	foreach ( glob( $root . '/*/*.jsonl' ) ?: array() as $path ) {
		if ( filemtime( $path ) < time() - 30 * DAY_IN_SECONDS ) { continue; }
		$lines = @file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		foreach ( array_slice( $lines ?: array(), -$limit ) as $line ) {
			$event = json_decode( $line, true );
			if ( is_array( $event ) ) { $events[] = $event; }
		}
	}
	usort( $events, static function ( $a, $b ) { return strcmp( $b['time'] ?? '', $a['time'] ?? '' ); } );
	return array_slice( $events, 0, $limit );
}

function mytheme_logger_screen() {
	$root = mytheme_log_root();
	$events = mytheme_recent_logs();
	?>
	<div class="mytheme-panel"><h2>گزارش خطاها</h2>
	<p>خطاهای PHP پس از بارگذاری قالب، خطاهای گزارش‌شدهٔ مرورگر و شکست درخواست‌های HTTP در پوشه‌های جداگانهٔ منبع و تاریخ ثبت می‌شوند. دادهٔ درخواست، کوکی و آدرس کامل ذخیره نمی‌شود.</p>
	<?php if ( ! $root ) : ?><p class="mytheme-feedback is-error" role="alert">مسیر امن و قابل‌نوشتن برای لاگ پیدا نشد.</p><?php endif; ?>
	<p>رویدادهای ۳۰ روز اخیر: <?php echo esc_html( (string) count( $events ) ); ?> مورد آخر. حجم هر فایل روزانه حداکثر ۲ مگابایت است.</p>
	<?php if ( $events ) : ?><div class="mytheme-log-scroll"><table class="widefat striped"><thead><tr><th scope="col">زمان UTC</th><th scope="col">شدت</th><th scope="col">منبع</th><th scope="col">شرح</th></tr></thead><tbody>
	<?php foreach ( $events as $event ) : ?><tr><td><?php echo esc_html( $event['time'] ?? '' ); ?></td><td><?php echo esc_html( $event['level'] ?? '' ); ?></td><td><?php echo esc_html( $event['component'] ?? '' ); ?><small><?php echo esc_html( $event['source'] ?? '' ); ?></small></td><td><?php echo esc_html( $event['message'] ?? '' ); ?></td></tr><?php endforeach; ?>
	</tbody></table></div><?php else : ?><p>هنوز رویدادی ثبت نشده است.</p><?php endif; ?>
	</div>
	<?php
}
