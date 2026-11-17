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
function mytheme_setup_screen() {
	echo '<div class="wrap"><h1>راه‌اندازی mytheme</h1><h2>۱. افزونه‌های ضروری</h2>';
	foreach ( mytheme_dependencies() as $slug => $title ) {
		$file = "$slug/$slug.php";
		echo '<p>' . esc_html( $title ) . ' ';
		if ( is_plugin_active( $file ) ) {
			echo 'فعال است';
		} elseif ( file_exists( WP_PLUGIN_DIR . '/' . $file ) ) {
			$url = wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $file ) ), 'activate-plugin_' . $file );
			echo '<a class="button" href="' . esc_url( $url ) . '">فعال‌سازی</a>';
		} else {
			$url = wp_nonce_url( admin_url( 'update.php?action=install-plugin&plugin=' . $slug ), 'install-plugin_' . $slug );
			echo '<a class="button" href="' . esc_url( $url ) . '">نصب از مخزن وردپرس</a>';
		}
		echo '</p>';
	}
	echo '<h2>۲. انتخاب دمو</h2>';
	if ( ! mytheme_ready() ) { echo '<p>پس از فعال‌سازی هر دو افزونه به همین صفحه برگردید.</p></div>'; return; }
	foreach ( mytheme_demos() as $id => $demo ) {
		$state = get_option( 'mytheme_progress_' . $id, 0 );
		echo '<section><h3>' . esc_html( $demo['title'] ) . '</h3><p>با نصب دمو، صفحات اصلی، فروشگاه، سبد خرید و پرداخت سایت به صفحات این دمو تغییر می‌کنند.</p><button class="button button-primary" data-demo="' . esc_attr( $id ) . '">نصب یا ادامهٔ دمو</button> <progress max="100" value="' . absint( $state ) . '"></progress> <output aria-live="polite">' . absint( $state ) . '%</output></section>';
	}
	wp_enqueue_script( 'mytheme-setup', get_template_directory_uri() . '/inc/setup.js', array(), '0.2.0', true );
	wp_localize_script( 'mytheme-setup', 'mythemeSetup', array( 'nonce' => wp_create_nonce( 'mytheme_import' ) ) );
	echo '</div>';
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
