<?php
function mytheme_demos() {
	return apply_filters( 'mytheme_demos', array( 'velour' => array( 'title' => 'فروشگاه زیبایی وِلور', 'frontend' => dirname( __DIR__ ) . '/demos/velour/frontend.php', 'file' => dirname( __DIR__ ) . '/demos/velour/import.php' ) ) );
}
function mytheme_dependencies() {
	return array( 'elementor' => 'Elementor', 'woocommerce' => 'WooCommerce' );
}
function mytheme_ready() {
	return did_action( 'elementor/loaded' ) && class_exists( 'WooCommerce' );
}
add_action( 'after_switch_theme', function () { update_option( 'mytheme_welcome', 1 ); } );
add_action( 'admin_init', function () {
	if ( get_option( 'mytheme_welcome' ) && current_user_can( 'manage_options' ) && ! wp_doing_ajax() && ! is_network_admin() && ! defined( 'WP_CLI' ) ) {
		delete_option( 'mytheme_welcome' );
		wp_safe_redirect( admin_url( 'themes.php?page=mytheme-setup' ) );
		exit;
	}
}, 100 );
add_action( 'admin_menu', function () {
	add_theme_page( 'راه‌اندازی قالب', 'راه‌اندازی قالب', 'manage_options', 'mytheme-setup', 'mytheme_setup_screen' );
} );
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'appearance_page_mytheme-setup' !== $hook ) { return; }
	$uri = get_template_directory_uri() . '/inc/';
	$version = wp_get_theme( get_template() )->get( 'Version' );
	wp_enqueue_style( 'mytheme-setup', $uri . 'setup.css', array(), $version );
	wp_enqueue_script( 'mytheme-setup', $uri . 'setup.js', array( 'updates' ), $version, true );
	wp_localize_script( 'mytheme-setup', 'mythemeSetup', array( 'nonce' => wp_create_nonce( 'mytheme_import' ) ) );
} );
function mytheme_setup_screen() {
	$ready = mytheme_ready();
	?>
	<div class="wrap mytheme-setup" dir="rtl">
		<header><h1>راه‌اندازی قالب <bdi>mytheme</bdi></h1></header>
		<div class="mytheme-panel"><h2>افزونه‌های ضروری</h2>
		<?php foreach ( mytheme_dependencies() as $slug => $title ) :
			$file = "$slug/$slug.php";
			$active = is_plugin_active( $file );
			$installed = file_exists( WP_PLUGIN_DIR . '/' . $file );
			$allowed = current_user_can( 'activate_plugin', $file ) && ( $installed || current_user_can( 'install_plugins' ) );
			?>
			<div class="mytheme-plugin" data-state="<?php echo $active ? 'active' : ( $installed ? 'installed' : 'missing' ); ?>">
				<strong lang="en"><?php echo esc_html( $title ); ?></strong>
				<span role="status"><?php echo $active ? 'فعال' : ( $allowed ? ( $installed ? 'آمادهٔ فعال‌سازی' : 'نصب نشده' ) : 'نیاز به دسترسی مدیر نصب افزونه' ); ?></span>
				<?php if ( ! $active && $allowed ) : ?>
					<button type="button" class="button button-primary" data-plugin="<?php echo esc_attr( $slug ); ?>"><?php echo $installed ? 'فعال‌سازی' : 'نصب و فعال‌سازی'; ?></button>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
		</div>
		<div class="mytheme-panel"><h2>انتخاب دمو</h2>
			<p data-locked <?php echo $ready ? 'hidden' : ''; ?>>برای باز شدن دموها، هر دو افزونه را فعال کنید.</p>
			<div data-demos <?php echo $ready ? '' : 'hidden'; ?>>
			<?php foreach ( mytheme_demos() as $id => $demo ) :
				$state = absint( get_option( 'mytheme_progress_' . $id, 0 ) );
				?>
				<section><h3><?php echo esc_html( $demo['title'] ); ?></h3>
				<p>با نصب دمو، صفحات اصلی، فروشگاه، سبد خرید و پرداخت سایت به صفحات این دمو تغییر می‌کنند.</p>
				<button class="button button-primary" data-demo="<?php echo esc_attr( $id ); ?>" <?php disabled( 100 === $state ); ?>>نصب یا ادامهٔ دمو</button>
				<progress aria-label="پیشرفت نصب دمو" max="100" value="<?php echo $state; ?>"></progress>
				<output aria-live="polite"><?php echo $state; ?>%</output></section>
			<?php endforeach; ?>
			</div>
		</div>
	</div>
	<?php
	wp_print_request_filesystem_credentials_modal();
}
add_action( 'wp_ajax_mytheme_import', function () {
	check_ajax_referer( 'mytheme_import' );
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_pages' ) || ! current_user_can( 'manage_woocommerce' ) || ! mytheme_ready() ) { wp_send_json_error( 'دسترسی یا افزونهٔ لازم موجود نیست.', 403 ); }
	$id = sanitize_key( wp_unslash( $_POST['demo'] ?? '' ) );
	$demos = mytheme_demos();
	if ( ! isset( $demos[ $id ] ) ) { wp_send_json_error( 'دمو یافت نشد.', 404 ); }
	global $wpdb;
	$lock = 'mytheme_' . md5( DB_NAME . $wpdb->prefix );
	if ( ! $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', $lock ) ) ) { wp_send_json_error( 'واردسازی دیگری در حال اجراست. دوباره تلاش کنید.', 409 ); }
	try {
		$steps = require $demos[ $id ]['file'];
		$key = 'mytheme_step_' . $id;
		$n = (int) get_option( $key, 0 );
		if ( isset( $steps[ $n ] ) ) {
			$steps[ $n ]();
			update_option( $key, ++$n );
		}
		$pct = min( 100, (int) floor( 100 * $n / count( $steps ) ) );
		update_option( 'mytheme_progress_' . $id, $pct );
	} catch ( Throwable $error ) {
		$message = $error->getMessage();
	} finally {
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
	}
	if ( isset( $message ) ) { wp_send_json_error( $message, 400 ); }
	wp_send_json_success( $pct );
} );

add_action( 'admin_notices', function () {
	$screen = get_current_screen();
	if ( ! $screen || 'plugins' !== $screen->id || ! current_user_can( 'manage_options' ) ) { return; }
	echo '<div class="notice notice-info"><p><a href="' . esc_url( admin_url( 'themes.php?page=mytheme-setup' ) ) . '">ادامهٔ راه‌اندازی قالب و انتخاب دمو</a></p></div>';
} );
