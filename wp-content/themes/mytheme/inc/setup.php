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
			'brand'   => 'VELOUR ✦',
			'notice'  => '۱۵٪ تخفیف روی محصولات منتخب مجموعهٔ رز',
			'nav'     => array( 'velour-makeup' => 'آرایش', 'velour-skin' => 'مراقبت پوست', 'velour-hair' => 'مراقبت مو', 'velour-scent' => 'عطر و رایحه' ),
			'file'     => $base . 'velour/import.php',
		),
		'home-decor' => array(
			'title' => 'خانه زمان، فروشگاه ساعت و دکوراسیون',
			'description' => 'فروشگاه روشن و مینیمال برای ساعت و دکوراسیون خانه',
			'previews' => array( 'demos/home-decor/previews/home.jpg', 'demos/home-decor/previews/products.jpg', 'demos/home-decor/previews/mobile.jpg' ),
			'style' => 'demos/home-decor/style.css',
			'brand' => 'خانه زمان',
			'notice' => 'انتخابی ماندگار برای خانه شما · مجموعه ساعت و دکوراسیون',
			'nav' => array( 'decor-wall' => 'ساعت دیواری', 'decor-table' => 'میز و کنسول', 'decor-gramophone' => 'گرامافون' ),
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
		wp_safe_redirect( admin_url( 'themes.php?page=mytheme-setup&step=plugins' ) );
		exit;
	}
}, 100 );
add_action( 'admin_menu', function () {
	add_theme_page( 'مدیریت mytheme', 'mytheme', 'manage_options', 'mytheme-setup', 'mytheme_setup_screen' );
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
	$step = sanitize_key( wp_unslash( $_GET['step'] ?? 'home' ) );
	$show_store = $ready && 'store' === $step;
	$show_demos = $ready && 'demos' === $step;
	$next = admin_url( 'themes.php?page=mytheme-setup&step=store' );
	?>
	<div class="wrap mytheme-setup" dir="rtl"><header><h1>مدیریت قالب <bdi>mytheme</bdi></h1></header>
	<nav class="mytheme-tabs" aria-label="مدیریت قالب">
	<?php foreach ( array( 'home' => 'خانه', 'plugins' => 'راه‌اندازی', 'demos' => 'دموها', 'appearance' => 'ظاهر', 'health' => 'سلامت' ) as $tab => $label ) : ?>
		<a href="<?php echo esc_url( admin_url( 'themes.php?page=mytheme-setup&step=' . $tab ) ); ?>" <?php echo $step === $tab ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
	<?php endforeach; ?></nav>
	<?php if ( 'home' === $step ) : mytheme_dashboard_screen();
	elseif ( 'appearance' === $step ) : mytheme_appearance_screen();
	elseif ( 'health' === $step ) : mytheme_health_screen();
	elseif ( ! $show_store && ! $show_demos ) : ?>
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
	<p>مقادیر فعلی ووکامرس نمایش داده شده‌اند. تغییر واحد پول، مبلغ ذخیره‌شدهٔ محصولات و سفارش‌ها را تبدیل نمی‌کند.</p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="mytheme_store_settings">
		<?php wp_nonce_field( 'mytheme_store_settings' ); ?>
		<p><label for="mytheme-currency-search">جست‌وجوی واحد پول</label><br><input id="mytheme-currency-search" type="search" placeholder="نام یا کد واحد پول"></p>
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
		<p class="mytheme-actions"><button type="submit" class="button button-primary">ذخیره و ادامه</button>
		<a class="button" href="<?php echo esc_url( admin_url( 'themes.php?page=mytheme-setup&step=demos' ) ); ?>">ادامه با مقادیر فعلی</a>
		<a class="button" href="<?php echo esc_url( admin_url( 'themes.php?page=mytheme-setup' ) ); ?>">بازگشت به افزونه‌ها</a></p>
	</form></div>
	<?php else : ?>
	<div class="mytheme-panel"><p>افزونه‌های ضروری نصب و فعال هستند. <a href="<?php echo esc_url( admin_url( 'themes.php?page=mytheme-setup&step=store' ) ); ?>">ویرایش تنظیمات فروشگاه</a></p><h2>۳. انتخاب دمو</h2>
	<p>با نصب دمو، صفحات اصلی، فروشگاه، سبد خرید و پرداخت سایت به صفحات دمو تغییر می‌کنند.</p>
	<p>با تغییر دمو، ظاهر و صفحات نمایشی عوض می‌شوند. محصولات واقعی، سفارش‌ها و اطلاعات مشتریان حفظ می‌شوند.</p>
	<div class="mytheme-demo-gallery">
	<?php foreach ( mytheme_demos() as $id => $demo ) :
		$state = absint( get_option( 'mytheme_progress_' . $id, 0 ) );
		if ( isset( $demo['pending'] ) && call_user_func( $demo['pending'] ) ) { $state = min( 99, $state ); }
		$active = mytheme_active_demo() === $id;
		?>
		<section class="mytheme-demo" data-active="<?php echo $active ? 'true' : 'false'; ?>" data-title="<?php echo esc_attr( $demo['title'] ); ?>"><label>
			<input type="radio" name="demo" value="<?php echo esc_attr( $id ); ?>">
			<strong><?php echo esc_html( $demo['title'] ); ?><?php if ( $active ) : ?> <em>دموی فعال</em><?php endif; ?></strong>
			<small><?php echo esc_html( $demo['description'] ?? '' ); ?></small>
			<?php foreach ( $demo['previews'] ?? array() as $index => $path ) : ?>
			<img src="<?php echo esc_url( get_template_directory_uri() . '/' . $path ); ?>" alt="<?php echo esc_attr( array( 'صفحه اصلی', 'محصولات', 'نمای موبایل' )[ $index ] ?? '' ); ?>">
			<?php endforeach; ?>
		</label>
		<a class="button" target="_blank" rel="noopener" href="<?php echo esc_url( get_template_directory_uri() . '/' . $demo['previews'][0] ); ?>">پیش‌نمایش دمو</a>
		<?php foreach ( array( 'home' => 'خانه', 'shop' => 'فروشگاه', 'product' => 'محصول' ) as $page => $label ) :
			$url = 100 === $state ? mytheme_demo_preview_url( $id, $page ) : '';
			?><a class="button" data-preview-live="<?php echo esc_attr( $page ); ?>" target="_blank" rel="noopener" href="<?php echo esc_url( $url ); ?>" <?php echo $url ? '' : 'hidden'; ?>>نمای زندهٔ <?php echo esc_html( $label ); ?></a><?php
		endforeach; ?>
		<button class="button button-primary" data-demo="<?php echo esc_attr( $id ); ?>" disabled><?php echo 100 === $state ? 'بررسی فعال‌سازی' : 'نصب دمو'; ?></button>
		<progress aria-label="پیشرفت نصب" max="100" value="<?php echo $state; ?>"></progress> <output aria-live="polite"><?php echo $state; ?>%</output>
		<a class="button button-primary" data-visit href="<?php echo esc_url( home_url( '/' ) ); ?>" <?php echo $active && 100 === $state ? '' : 'hidden'; ?>>ورود به سایت</a>
		</section>
	<?php endforeach; ?></div>
	<?php $history = (array) get_option( 'mytheme_demo_history', array() ); $last = end( $history );
	if ( $last && ! empty( $last['from'] ) && mytheme_active_demo() === $last['to'] ) : ?>
		<p><button type="button" class="button" data-rollback="<?php echo esc_attr( $last['from'] ); ?>">بازگشت به دموی قبلی</button></p>
	<?php endif; ?>
	<p role="status" data-demo-message></p>
	<dialog data-confirm-demo aria-labelledby="mytheme-confirm-title"><h3 id="mytheme-confirm-title">مرور تغییر دمو</h3>
		<p data-confirm-description></p><p>صفحهٔ اصلی، فروشگاه، سبد و پرداخت به دمو انتخابی متصل می‌شوند. محصولات نمونهٔ دموی قبلی به زباله‌دان منتقل می‌شوند و در بازگشت به آن دمو دوباره فعال خواهند شد. محصولات واقعی، سفارش‌ها و مشتریان حفظ می‌شوند.</p>
		<div class="mytheme-actions"><button type="button" class="button" data-cancel-demo>انصراف</button><button type="button" class="button button-primary" data-accept-demo>تأیید فعال‌سازی</button></div>
	</dialog></div>
	<?php endif; ?></div>
	<?php
	wp_print_request_filesystem_credentials_modal();
}
function mytheme_dashboard_screen() {
	$active = mytheme_active_demo();
	$demos = mytheme_demos();
	?>
	<div class="mytheme-panel"><h2>وضعیت فروشگاه</h2>
	<p>دموی فعال <strong><?php echo esc_html( $active ? $demos[ $active ]['title'] : 'هنوز انتخاب نشده' ); ?></strong></p>
	<p>افزونه‌های ضروری <?php echo mytheme_ready() ? 'آماده‌اند' : 'نیاز به بررسی دارند'; ?>. واحد پول <?php echo esc_html( function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'نامشخص' ); ?>.</p>
	<div class="mytheme-dashboard-grid">
		<a href="<?php echo esc_url( admin_url( 'themes.php?page=mytheme-setup&step=plugins' ) ); ?>"><strong>راه‌اندازی</strong><span>افزونه‌ها و تنظیمات فروشگاه</span></a>
		<a href="<?php echo esc_url( admin_url( 'themes.php?page=mytheme-setup&step=demos' ) ); ?>"><strong>دموها</strong><span>پیش‌نمایش، نصب و تغییر دمو</span></a>
		<a href="<?php echo esc_url( admin_url( 'themes.php?page=mytheme-setup&step=appearance' ) ); ?>"><strong>ظاهر</strong><span>رنگ، فونت، هدر و فوتر</span></a>
		<a href="<?php echo esc_url( admin_url( 'themes.php?page=mytheme-setup&step=health' ) ); ?>"><strong>سلامت</strong><span>وضعیت صفحات و وابستگی‌ها</span></a>
	</div>
	<p>تنظیمات ارسال و پرداخت در <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings' ) ); ?>">تنظیمات ووکامرس</a> مدیریت می‌شوند.</p>
	</div>
	<?php
}
function mytheme_health_screen() {
	$active = mytheme_active_demo();
	$prefix = str_replace( '-', '_', $active );
	?>
	<div class="mytheme-panel"><h2>سلامت قالب</h2>
	<p>نسخهٔ قالب <?php echo esc_html( wp_get_theme()->get( 'Version' ) ); ?>. دموی فعال <?php echo esc_html( $active ?: 'انتخاب نشده' ); ?>.</p>
	<ul class="mytheme-health-list">
	<?php foreach ( mytheme_dependencies() as $file => $title ) : ?>
		<li><strong><?php echo esc_html( $title ); ?></strong><span><?php echo esc_html( mytheme_dependency_state( $file ) ); ?></span></li>
	<?php endforeach;
	foreach ( array( 'home' => 'خانه', 'shop' => 'فروشگاه', 'cart' => 'سبد', 'checkout' => 'پرداخت' ) as $page => $title ) :
		$page_id = $active ? (int) get_option( $prefix . '_page_' . $page ) : 0;
		?><li><strong><?php echo esc_html( $title ); ?></strong><span><?php echo $page_id && get_post( $page_id ) ? 'آماده' : 'نیاز به نصب دمو'; ?></span></li><?php
	endforeach; ?>
	</ul>
	<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings' ) ); ?>">تنظیمات ووکامرس</a> · <a href="<?php echo esc_url( admin_url( 'site-health.php' ) ); ?>">سلامت وردپرس</a></p>
	</div>
	<?php
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
function mytheme_demo_ajax_access() {
	if ( ! check_ajax_referer( 'mytheme_import', '_ajax_nonce', false ) ) { wp_send_json_error( array( 'code' => 'session', 'message' => 'اعتبار درخواست تمام شده است. صفحه را تازه‌سازی کنید.' ), 403 ); }
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_pages' ) || ! current_user_can( 'manage_woocommerce' ) || ! mytheme_ready() ) {
		wp_send_json_error( array( 'code' => 'permission', 'message' => 'مجوز یا افزونهٔ لازم برای این عملیات موجود نیست.' ), 403 );
	}
}
function mytheme_with_demo_lock( $callback ) {
	global $wpdb;
	$lock = 'mytheme_' . md5( DB_NAME . $wpdb->prefix );
	if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', $lock ) ) ) { throw new RuntimeException( 'busy', 409 ); }
	try {
		return $callback();
	} finally {
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
	}
}
function mytheme_demo_ajax_failure( $error ) {
	$messages = array(
		'busy' => 'عملیات دیگری در حال اجراست. چند لحظه بعد دوباره تلاش کنید.',
		'incomplete' => 'صفحات دمو کامل نشده‌اند. نصب دمو را ادامه دهید.',
		'storage' => 'ثبت پیشرفت ناموفق بود. دوباره تلاش کنید.',
		'product_state' => 'تغییر وضعیت محصولات نمونه ناموفق بود. دوباره تلاش کنید.',
	);
	$code = isset( $messages[ $error->getMessage() ] ) ? $error->getMessage() : 'server';
	$event = wp_generate_password( 8, false, false );
	error_log( '[mytheme demo ' . $event . '] ' . $error );
	wp_send_json_error( array( 'code' => $code, 'message' => $messages[ $code ] ?? 'عملیات انجام نشد. شناسه پیگیری ' . $event ), 'busy' === $code ? 409 : ( 'incomplete' === $code ? 400 : 500 ) );
}
add_action( 'wp_ajax_mytheme_import', function () {
	mytheme_demo_ajax_access();
	$id = sanitize_key( wp_unslash( $_POST['demo'] ?? '' ) );
	$demos = mytheme_demos();
	if ( ! isset( $demos[ $id ] ) ) { wp_send_json_error( array( 'code' => 'missing', 'message' => 'دمو یافت نشد.' ), 404 ); }
	try {
		$pct = mytheme_with_demo_lock( function () use ( $id, $demos ) {
			$steps = require $demos[ $id ]['file'];
			$key = 'mytheme_step_' . $id;
			$n = (int) get_option( $key, 0 );
			if ( isset( $steps[ $n ] ) ) {
				update_option( 'mytheme_demo_status_' . $id, 'importing' );
				$steps[ $n ]();
				update_option( $key, ++$n );
				if ( (int) get_option( $key ) !== $n ) { throw new RuntimeException( 'storage' ); }
			}
			$pct = min( 100, (int) floor( 100 * $n / count( $steps ) ) );
			update_option( 'mytheme_progress_' . $id, $pct );
			if ( (int) get_option( 'mytheme_progress_' . $id ) !== $pct ) { throw new RuntimeException( 'storage' ); }
			update_option( 'mytheme_demo_status_' . $id, 100 === $pct ? 'installed' : 'importing' );
			return $pct;
		} );
	} catch ( Throwable $error ) {
		if ( 'busy' !== $error->getMessage() ) { update_option( 'mytheme_demo_status_' . $id, 'incomplete' ); }
		mytheme_demo_ajax_failure( $error );
	}
	$previews = array();
	if ( 100 === $pct ) {
		foreach ( array( 'home', 'shop', 'product' ) as $page ) { $previews[ $page ] = mytheme_demo_preview_url( $id, $page ); }
	}
	wp_send_json_success( array( 'progress' => $pct, 'previews' => $previews ) );
} );
add_action( 'wp_ajax_mytheme_activate_demo', function () {
	mytheme_demo_ajax_access();
	$id = sanitize_key( wp_unslash( $_POST['demo'] ?? '' ) );
	if ( ! isset( mytheme_demos()[ $id ] ) || 100 !== (int) get_option( 'mytheme_progress_' . $id ) ) { wp_send_json_error( array( 'code' => 'incomplete', 'message' => 'نصب دمو کامل نشده است.' ), 400 ); }
	try { mytheme_with_demo_lock( function () use ( $id ) { mytheme_activate_demo( $id ); } ); }
	catch ( Throwable $error ) { mytheme_demo_ajax_failure( $error ); }
	wp_send_json_success( array( 'active' => $id ) );
} );
add_action( 'wp_ajax_mytheme_rollback_demo', function () {
	mytheme_demo_ajax_access();
	$history = (array) get_option( 'mytheme_demo_history', array() );
	$last = end( $history );
	if ( ! $last || empty( $last['from'] ) || mytheme_active_demo() !== $last['to'] || 100 !== (int) get_option( 'mytheme_progress_' . $last['from'] ) ) {
		wp_send_json_error( array( 'code' => 'incomplete', 'message' => 'نسخهٔ قبلی برای بازگشت آماده نیست.' ), 400 );
	}
	try { mytheme_with_demo_lock( function () use ( $last ) { mytheme_activate_demo( $last['from'] ); } ); }
	catch ( Throwable $error ) { mytheme_demo_ajax_failure( $error ); }
	wp_send_json_success( array( 'active' => $last['from'] ) );
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
