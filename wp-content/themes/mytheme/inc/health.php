<?php
function mytheme_health_parts() {
	$parts = array( 'core', 'uploads' );
	foreach ( mytheme_dependencies() as $file => $title ) { $parts[] = dirname( $file ); }
	return $parts;
}

function mytheme_health_findings( $part ) {
	$findings = array();
	$checked = 0;
	if ( 'core' === $part ) {
		require_once ABSPATH . 'wp-admin/includes/update.php';
		global $wp_version;
		$checksums = get_core_checksums( $wp_version, get_locale() );
		if ( ! $checksums ) { throw new RuntimeException( 'دریافت چک‌سام رسمی هسته ناموفق بود.' ); }
		foreach ( $checksums as $relative => $expected ) {
			if ( ! preg_match( '/\.(php|js)$/i', $relative ) ) { continue; }
			if ( false !== strpos( $relative, '..' ) || 0 === strpos( $relative, '/' ) || false !== strpos( $relative, '\\' ) ) { continue; }
			$path = ABSPATH . $relative;
			$checked++;
			if ( ! is_file( $path ) || md5_file( $path ) !== $expected ) { $findings[] = array( 'severity' => 'high', 'file' => $relative, 'reason' => 'فایل هسته با نسخهٔ رسمی یکسان نیست یا وجود ندارد.' ); }
			if ( count( $findings ) >= 50 ) { break; }
		}
	} elseif ( 'uploads' === $part ) {
		$uploads = wp_get_upload_dir();
		$base = $uploads['basedir'] ?? '';
		if ( ! is_dir( $base ) ) { return array( 'checked' => 0, 'findings' => array() ); }
		$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS ) );
		foreach ( $files as $file ) {
			if ( $file->isLink() || ! $file->isFile() ) { continue; }
			$checked++;
			if ( preg_match( '/\.(php[0-9]?|phtml|phar)$/i', $file->getFilename() ) ) {
				$findings[] = array( 'severity' => 'medium', 'file' => 'uploads/' . substr( $file->getPathname(), strlen( $base ) + 1 ), 'reason' => 'فایل اجرایی در پوشهٔ بارگذاری‌ها؛ نیازمند بررسی دستی.' );
			}
			if ( $checked >= 5000 || count( $findings ) >= 50 ) { break; }
		}
	} else {
		$plugin_files = array_filter( array_keys( mytheme_dependencies() ), static function ( $file ) use ( $part ) { return dirname( $file ) === $part; } );
		if ( ! $plugin_files ) { throw new RuntimeException( 'افزونهٔ موردنظر در فهرست قالب نیست.' ); }
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$plugins = get_plugins();
		$file = reset( $plugin_files );
		if ( ! isset( $plugins[ $file ] ) ) { throw new RuntimeException( 'افزونه نصب نشده است.' ); }
		$version = $plugins[ $file ]['Version'];
		$url = 'https://downloads.wordpress.org/plugin-checksums/' . rawurlencode( $part ) . '/' . rawurlencode( $version ) . '.json';
		$response = wp_remote_get( $url, array( 'timeout' => 10, 'limit_response_size' => 12 * MB_IN_BYTES ) );
		$data = ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ? json_decode( wp_remote_retrieve_body( $response ), true ) : null;
		if ( ! is_array( $data ) || ( $data['plugin'] ?? '' ) !== $part || ( $data['version'] ?? '' ) !== $version || empty( $data['files'] ) ) { throw new RuntimeException( 'چک‌سام رسمی این نسخه از افزونه در دسترس نیست.' ); }
		$base = trailingslashit( WP_PLUGIN_DIR ) . $part . '/';
		foreach ( $data['files'] as $relative => $hashes ) {
			if ( ! preg_match( '/\.(php|js)$/i', $relative ) ) { continue; }
			if ( false !== strpos( $relative, '..' ) || 0 === strpos( $relative, '/' ) || false !== strpos( $relative, '\\' ) ) { continue; }
			$checked++;
			$expected = $hashes['sha256'] ?? '';
			if ( ! $expected || ! is_file( $base . $relative ) || hash_file( 'sha256', $base . $relative ) !== $expected ) {
				$findings[] = array( 'severity' => 'high', 'file' => $part . '/' . $relative, 'reason' => 'فایل افزونه با نسخهٔ رسمی یکسان نیست یا وجود ندارد.' );
			}
			if ( count( $findings ) >= 50 ) { break; }
		}
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS ) );
		foreach ( $iterator as $item ) {
			if ( $item->isLink() || ! $item->isFile() ) { continue; }
			$relative = substr( $item->getPathname(), strlen( $base ) );
			if ( preg_match( '/\.php$/i', $relative ) && ! isset( $data['files'][ $relative ] ) ) {
				$findings[] = array( 'severity' => 'medium', 'file' => $part . '/' . $relative, 'reason' => 'فایل PHP در فهرست رسمی این نسخه نیست؛ بررسی دستی لازم است.' );
			}
			if ( count( $findings ) >= 50 ) { break; }
		}
	}
	return array( 'checked' => $checked, 'findings' => $findings, 'at' => time() );
}

add_action( 'wp_ajax_mytheme_health_scan', static function () {
	if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'mytheme_health_scan', '_ajax_nonce', false ) ) { wp_send_json_error( array( 'message' => 'درخواست مجاز نیست.' ), 403 ); }
	$part = sanitize_key( wp_unslash( $_POST['part'] ?? '' ) );
	if ( ! in_array( $part, mytheme_health_parts(), true ) ) { wp_send_json_error( array( 'message' => 'بخش نامعتبر است.' ), 400 ); }
	try {
		$result = mytheme_health_findings( $part );
		$stored = (array) get_option( 'mytheme_health_results', array() );
		$stored[ $part ] = $result;
		update_option( 'mytheme_health_results', $stored, false );
		wp_send_json_success( $result );
	} catch ( Throwable $error ) {
		mytheme_log_event( 'warning', 'health_scan', $error->getMessage(), __FILE__ );
		wp_send_json_error( array( 'message' => 'بررسی «' . $part . '» کامل نشد. ارتباط یا دسترسی فایل را بررسی و دوباره تلاش کنید.' ), 500 );
	}
} );

function mytheme_health_details() {
	$results = (array) get_option( 'mytheme_health_results', array() );
	$recent = mytheme_recent_logs( 100 );
	$components = array();
	foreach ( $recent as $event ) {
		$component = $event['component'] ?? 'wordpress';
		if ( ! isset( $components[ $component ] ) ) { $components[ $component ] = array( 'count' => 0, 'critical' => 0, 'warning' => 0 ); }
		$components[ $component ]['count']++;
		if ( 'critical' === ( $event['level'] ?? '' ) ) { $components[ $component ]['critical']++; }
		elseif ( 'warning' === ( $event['level'] ?? '' ) ) { $components[ $component ]['warning']++; }
	}
	?>
	<div class="mytheme-panel"><h2>بررسی یکپارچگی و نشانه‌های تداخل</h2>
	<p>چک‌سام فایل‌های PHP و JavaScript هسته و افزونه‌های ضروری با نسخهٔ رسمی وردپرس مقایسه می‌شود. فایل اجرایی در بارگذاری‌ها نیز گزارش می‌شود. این بررسی، تشخیص قطعی بدافزار یا تداخل نیست و فایل‌ها را تغییر نمی‌دهد.</p>
	<h3>وضعیت افزونه‌های ضروری</h3><ul class="mytheme-health-list">
	<?php foreach ( mytheme_dependencies() as $file => $title ) : $state = mytheme_dependency_state( $file ); ?>
	<li><strong><?php echo esc_html( $title ); ?></strong><span><?php echo esc_html( array( 'active' => 'فعال', 'installed' => 'نصب‌شده، غیرفعال', 'missing' => 'نصب‌نشده' )[ $state ] ?? $state ); ?></span></li>
	<?php endforeach; ?></ul>
	<p><button type="button" class="button button-primary" data-health-scan>اجرای بررسی</button> <span role="status" data-health-status></span></p>
	<progress max="<?php echo count( mytheme_health_parts() ); ?>" value="0" data-health-progress hidden></progress>
	<?php if ( $results ) : ?><ul class="mytheme-health-list">
	<?php foreach ( $results as $part => $result ) : ?><li><strong><?php echo esc_html( $part ); ?></strong><span><?php echo esc_html( (string) ( $result['checked'] ?? 0 ) ); ?> فایل بررسی شد · <?php echo esc_html( (string) count( $result['findings'] ?? array() ) ); ?> مورد نیازمند بررسی</span></li><?php endforeach; ?></ul>
	<?php foreach ( $results as $part => $result ) : foreach ( $result['findings'] ?? array() as $finding ) : ?><p class="mytheme-health-finding"><strong><?php echo esc_html( $finding['severity'] ); ?></strong> <code><?php echo esc_html( $finding['file'] ); ?></code> — <?php echo esc_html( $finding['reason'] ); ?></p><?php endforeach; endforeach; ?>
	<?php endif; ?>
	<h3>منابع خطا در ۱۰۰ رویداد اخیر</h3>
	<?php if ( $components ) : ?><ul class="mytheme-health-list"><?php foreach ( $components as $part => $summary ) : $level = $summary['critical'] ? 'زیاد' : ( $summary['warning'] ? 'متوسط' : 'کم' ); ?><li><strong><?php echo esc_html( $part ); ?></strong><span><?php echo esc_html( (string) $summary['count'] ); ?> رویداد · اولویت بررسی: <?php echo esc_html( $level ); ?>؛ تداخل قطعی احراز نشده است.</span></li><?php endforeach; ?></ul><?php else : ?><p>رویدادی برای ارزیابی تداخل ثبت نشده است.</p><?php endif; ?>
	</div>
	<?php
}
