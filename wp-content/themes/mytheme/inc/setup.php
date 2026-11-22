<?php
function mytheme_demos() {
	$base = dirname( __DIR__ ) . '/demos/';
	return apply_filters( 'mytheme_demos', array(
		'velour' => array(
			'title'    => 'فروشگاه زیبایی وِلور',
			'description' => 'فروشگاه آرایشی با رنگ‌های تیره و رزگون',
			'pending'  => function () { return ! get_option( 'velour_dark_widgets' ); },
			'previews' => array( 'demos/velour/previews/home.jpg', 'demos/velour/previews/collection.jpg', 'demos/velour/previews/mobile.jpg' ),
			'style'   => 'demos/velour/style.css',
			'file'     => $base . 'velour/import.php',
		),
		'home-decor' => array(
			'title' => 'خانه زمان، فروشگاه ساعت و دکوراسیون',
			'description' => 'فروشگاه روشن و مینیمال برای ساعت و دکوراسیون خانه',
			'previews' => array( 'demos/home-decor/previews/home.jpg', 'demos/home-decor/previews/products.jpg', 'demos/home-decor/previews/mobile.jpg' ),
			'style' => 'demos/home-decor/style.css',
			'file' => $base . 'home-decor/import.php',
		),
	) );
}
function mytheme_dependencies() {
	return array(
		'elementor/elementor.php' => 'المنتور',
		'woocommerce/woocommerce.php' => 'ووکامرس',
		'persian-woocommerce/woocommerce-persian.php' => 'ووکامرس فارسی',
		'essential-addons-for-elementor-lite/essential_adons_elementor.php' => 'Essential Addons',
		'premium-addons-for-elementor/premium-addons-for-elementor.php' => 'Premium Addons',
	);
}
require_once __DIR__ . '/installer.php';
function mytheme_ready() {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	foreach ( mytheme_dependencies() as $file => $title ) {
		if ( 'active' !== mytheme_dependency_state( $file ) ) { return false; }
	}
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
	wp_localize_script( 'mytheme-setup', 'mythemeSetup', array( 'nonce' => wp_create_nonce( 'mytheme_import' ), 'installerNonce' => wp_create_nonce( 'mytheme_installer' ), 'user' => get_current_user_id() ) );
} );
function mytheme_setup_screen() {
	$ready = mytheme_ready();
	$step = sanitize_key( wp_unslash( $_GET['step'] ?? '' ) );
	$show_store = $ready && 'store' === $step;
	$show_demos = $ready && 'demos' === $step;
	$next = admin_url( 'themes.php?page=mytheme-setup&step=store' );
	?>
	<div class="wrap mytheme-setup" dir="rtl"><header><h1>راه‌اندازی قالب <bdi>mytheme</bdi></h1></header>
	<?php if ( ! $show_store && ! $show_demos ) : ?>
	<div class="mytheme-panel"><h2>۱. افزونه‌های ضروری</h2>
	<?php foreach ( mytheme_dependencies() as $file => $title ) :
		$state = mytheme_dependency_state( $file );
		?>
		<div class="mytheme-plugin" data-file="<?php echo esc_attr( $file ); ?>" data-state="<?php echo esc_attr( $state ); ?>">
			<strong><?php echo esc_html( $title ); ?></strong>
			<span role="status"><?php echo 'active' === $state ? 'فعال' : ( 'installed' === $state ? 'نصب شده' : 'در انتظار نصب' ); ?></span>
			<div class="mytheme-failure" hidden><b aria-hidden="true">!</b><span role="alert"></span><button type="button" class="button" data-retry>تلاش مجدد</button></div>
		</div>
	<?php endforeach; ?>
	<p role="status" data-message></p>
	<button class="button button-primary" data-install <?php disabled( $ready ); ?>>نصب و فعال‌سازی</button>
	<button type="button" class="button" data-retry-all hidden>تلاش مجدد برای موارد ناموفق</button>
	<button class="button" data-next="<?php echo esc_url( $next ); ?>" <?php disabled( ! $ready ); ?>>ادامه</button>
	</div>
	<?php elseif ( $show_store ) : ?>
	<div class="mytheme-panel"><h2>۲. تنظیمات فروشگاه</h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="mytheme_store_settings">
		<?php wp_nonce_field( 'mytheme_store_settings' ); ?>
		<p><label for="mytheme-currency">واحد پول</label><br><select id="mytheme-currency" name="currency">
		<?php foreach ( get_woocommerce_currencies() as $code => $name ) : ?>
			<option value="<?php echo esc_attr( $code ); ?>" <?php selected( get_option( 'woocommerce_currency', 'USD' ), $code ); ?>><?php echo esc_html( $name . ' (' . $code . ')' ); ?></option>
		<?php endforeach; ?>
		</select></p>
		<p><label for="mytheme-weight">واحد وزن</label><br><select id="mytheme-weight" name="weight">
		<?php foreach ( array( 'kg' => 'کیلوگرم', 'g' => 'گرم', 'lbs' => 'پوند', 'oz' => 'اونس' ) as $code => $name ) : ?>
			<option value="<?php echo esc_attr( $code ); ?>" <?php selected( get_option( 'woocommerce_weight_unit', 'kg' ), $code ); ?>><?php echo esc_html( $name ); ?></option>
		<?php endforeach; ?>
		</select></p>
		<button type="submit" class="button button-primary">ذخیره و ادامه</button>
	</form></div>
	<?php else : ?>
	<div class="mytheme-panel"><p>افزونه‌های ضروری نصب و فعال هستند. <a href="<?php echo esc_url( admin_url( 'themes.php?page=mytheme-setup&step=store' ) ); ?>">ویرایش تنظیمات فروشگاه</a></p><h2>۳. انتخاب دمو</h2>
	<p>با نصب دمو، صفحات اصلی، فروشگاه، سبد خرید و پرداخت سایت به صفحات دمو تغییر می‌کنند.</p>
	<p>با تغییر دمو، ظاهر و صفحات نمایشی عوض می‌شوند. محصولات واقعی، سفارش‌ها و اطلاعات مشتریان حفظ می‌شوند.</p>
	<?php foreach ( mytheme_demos() as $id => $demo ) :
		$state = absint( get_option( 'mytheme_progress_' . $id, 0 ) );
		if ( isset( $demo['pending'] ) && call_user_func( $demo['pending'] ) ) { $state = min( 99, $state ); }
		$active = mytheme_active_demo() === $id;
		?>
		<section class="mytheme-demo" data-active="<?php echo $active ? 'true' : 'false'; ?>"><label>
			<input type="radio" name="demo" value="<?php echo esc_attr( $id ); ?>">
			<strong><?php echo esc_html( $demo['title'] ); ?><?php if ( $active ) : ?> <em>دموی فعال</em><?php endif; ?></strong>
			<small><?php echo esc_html( $demo['description'] ?? '' ); ?></small>
			<?php foreach ( $demo['previews'] ?? array() as $index => $path ) : ?>
			<img src="<?php echo esc_url( get_template_directory_uri() . '/' . $path ); ?>" alt="<?php echo esc_attr( array( 'صفحه اصلی', 'محصولات', 'نمای موبایل' )[ $index ] ?? '' ); ?>">
			<?php endforeach; ?>
		</label>
		<a class="button" target="_blank" rel="noopener" href="<?php echo esc_url( get_template_directory_uri() . '/' . $demo['previews'][0] ); ?>">پیش‌نمایش دمو</a>
		<button class="button button-primary" data-demo="<?php echo esc_attr( $id ); ?>" disabled><?php echo 100 === $state ? 'فعال‌سازی دمو' : 'نصب و تکمیل'; ?></button>
		<progress aria-label="پیشرفت نصب" max="100" value="<?php echo $state; ?>"></progress> <output aria-live="polite"><?php echo $state; ?>%</output>
		<a class="button button-primary" data-visit href="<?php echo esc_url( home_url( '/' ) ); ?>" <?php echo $active && 100 === $state ? '' : 'hidden'; ?>>ورود به سایت</a>
		</section>
	<?php endforeach; ?></div>
	<?php endif; ?></div>
	<?php
	wp_print_request_filesystem_credentials_modal();
}
add_action( 'admin_post_mytheme_store_settings', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) || ! current_user_can( 'manage_options' ) || ! mytheme_ready() ) { wp_die( 'دسترسی یا ووکامرس موجود نیست.', '', array( 'response' => 403 ) ); }
	check_admin_referer( 'mytheme_store_settings' );
	$currency = sanitize_text_field( wp_unslash( $_POST['currency'] ?? '' ) );
	$weight = sanitize_text_field( wp_unslash( $_POST['weight'] ?? '' ) );
	if ( ! isset( get_woocommerce_currencies()[ $currency ] ) || ! in_array( $weight, array( 'kg', 'g', 'lbs', 'oz' ), true ) ) { wp_die( 'واحد پول یا وزن نامعتبر است.', '', array( 'response' => 400 ) ); }
	update_option( 'woocommerce_currency', $currency );
	update_option( 'woocommerce_weight_unit', $weight );
	wp_safe_redirect( admin_url( 'themes.php?page=mytheme-setup&step=demos' ) );
	exit;
} );
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
		if ( 100 === $pct ) { mytheme_activate_demo( $id ); }
	} catch ( Throwable $error ) {
		$message = $error->getMessage();
	} finally {
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
	}
	if ( isset( $message ) ) { wp_send_json_error( $message, 400 ); }
	wp_send_json_success( $pct );
} );
add_action( 'wp_ajax_mytheme_activate_demo', function () {
	check_ajax_referer( 'mytheme_import' );
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_pages' ) || ! current_user_can( 'manage_woocommerce' ) || ! mytheme_ready() ) { wp_send_json_error( 'دسترسی یا افزونهٔ لازم موجود نیست.', 403 ); }
	$id = sanitize_key( wp_unslash( $_POST['demo'] ?? '' ) );
	if ( ! isset( mytheme_demos()[ $id ] ) || 100 !== (int) get_option( 'mytheme_progress_' . $id ) ) { wp_send_json_error( 'نصب دمو کامل نشده است.', 400 ); }
	try { mytheme_activate_demo( $id ); }
	catch ( Throwable $error ) { wp_send_json_error( $error->getMessage(), 400 ); }
	wp_send_json_success( array( 'active' => $id ) );
} );

add_action( 'admin_notices', function () {
	$screen = get_current_screen();
	if ( ! $screen || 'plugins' !== $screen->id || ! current_user_can( 'manage_options' ) ) { return; }
	echo '<div class="notice notice-info"><p><a href="' . esc_url( admin_url( 'themes.php?page=mytheme-setup' ) ) . '">ادامهٔ راه‌اندازی قالب و انتخاب دمو</a></p></div>';
} );

// Keep the wizard's Continue button on the selected step after bulk activation.
add_action( 'admin_init', function () {
	if ( 'mytheme-setup' !== ( $_GET['page'] ?? '' ) || ! current_user_can( 'manage_options' ) ) { return; }
	foreach ( array( 'elementor_activation_redirect', 'pa_activation_redirect', 'eael_do_activation_redirect', '_wc_activation_redirect' ) as $transient ) {
		delete_transient( $transient );
	}
}, 0 );
