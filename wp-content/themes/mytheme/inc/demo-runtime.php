<?php
function mytheme_active_demo() {
	$id = get_option( 'mytheme_active_demo', '' );
	return isset( mytheme_demos()[ $id ] ) ? $id : '';
}

// Existing Velour installations predate the active-demo option.
add_action( 'init', function () {
	if ( get_option( 'mytheme_active_demo' ) ) { return; }
	$id = (int) get_option( 'velour_page_home' );
	if ( $id && (int) get_option( 'page_on_front' ) === $id ) { update_option( 'mytheme_active_demo', 'velour' ); }
}, 0 );

add_filter( 'option_show_on_front', function ( $value ) { return mytheme_active_demo() ? 'page' : $value; } );
add_filter( 'option_page_on_front', function ( $value ) {
	$id = mytheme_active_demo();
	return $id ? (int) get_option( str_replace( '-', '_', $id ) . '_page_home', $value ) : $value;
} );
foreach ( array( 'shop', 'cart', 'checkout' ) as $mytheme_wc_page ) {
	add_filter( 'option_woocommerce_' . $mytheme_wc_page . '_page_id', function ( $value ) use ( $mytheme_wc_page ) {
		$id = mytheme_active_demo();
		return $id ? (int) get_option( str_replace( '-', '_', $id ) . '_page_' . $mytheme_wc_page, $value ) : $value;
	} );
}
unset( $mytheme_wc_page );

function mytheme_activate_demo( $id ) {
	if ( ! isset( mytheme_demos()[ $id ] ) ) { throw new RuntimeException( 'دمو یافت نشد.' ); }
	$prefix = str_replace( '-', '_', $id );
	foreach ( array( 'home', 'shop', 'contact', 'cart', 'checkout' ) as $page ) {
		$page_id = (int) get_option( $prefix . '_page_' . $page );
		if ( ! $page_id || ! get_post( $page_id ) || ! get_post_meta( $page_id, '_elementor_data', true ) ) {
			throw new RuntimeException( 'صفحات دمو کامل نشده‌اند. نصب دمو را ادامه دهید.' );
		}
	}
	if ( did_action( 'elementor/loaded' ) ) { \Elementor\Plugin::$instance->files_manager->clear_cache(); }
	if ( function_exists( 'wc_delete_product_transients' ) ) { wc_delete_product_transients(); }
	update_option( 'mytheme_active_demo', $id );
	if ( get_option( 'mytheme_active_demo' ) !== $id ) { throw new RuntimeException( 'فعال‌سازی دمو ذخیره نشد. دوباره تلاش کنید.' ); }
}

add_filter( 'body_class', function ( $classes ) {
	$id = mytheme_active_demo();
	if ( $id ) { $classes[] = 'mytheme-demo-' . sanitize_html_class( $id ); }
	return $classes;
} );
add_action( 'wp_enqueue_scripts', function () {
	$id = mytheme_active_demo();
	$demos = mytheme_demos();
	if ( $id && isset( $demos[ $id ]['style'] ) ) {
		wp_enqueue_style( 'mytheme-demo', get_template_directory_uri() . '/' . $demos[ $id ]['style'], array( 'mytheme' ), wp_get_theme()->get( 'Version' ) );
	}
}, 95 );

add_filter( 'template_include', function ( $template ) {
	$id = mytheme_active_demo();
	if ( $id && function_exists( 'is_shop' ) && is_shop() && ! is_search() && did_action( 'elementor/loaded' ) ) {
		return __DIR__ . '/demo-shop.php';
	}
	return $template;
}, 999 );

function mytheme_inactive_demo_posts() {
	static $cache = array();
	$active = mytheme_active_demo();
	if ( isset( $cache[ $active ] ) ) { return $cache[ $active ]; }
	global $wpdb;
	$ids = array();
	foreach ( mytheme_demos() as $demo => $settings ) {
		if ( $demo === $active ) { continue; }
		$prefix = str_replace( '-', '_', $demo );
		foreach ( array( 'home', 'shop', 'contact', 'cart', 'checkout' ) as $page ) {
			$page_id = (int) get_option( $prefix . '_page_' . $page );
			if ( $page_id ) { $ids[] = $page_id; }
		}
		$sku = $wpdb->esc_like( 'mytheme-' . $demo . '-' ) . '%';
		$found = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE (meta_key = '_sku' AND meta_value LIKE %s) OR (meta_key = '_mytheme_demo' AND meta_value = %s)", $sku, $demo ) );
		$ids = array_merge( $ids, array_map( 'intval', $found ) );
	}
	$cache[ $active ] = array_values( array_unique( $ids ) );
	return $cache[ $active ];
}
add_action( 'pre_get_posts', function ( $query ) {
	if ( is_admin() || ! mytheme_active_demo() ) { return; }
	$query->set( 'post__not_in', array_values( array_unique( array_merge( (array) $query->get( 'post__not_in' ), mytheme_inactive_demo_posts() ) ) ) );
} );
add_action( 'template_redirect', function () {
	if ( ! mytheme_active_demo() || ! ( is_page() || is_singular( 'product' ) || is_tax( 'product_cat' ) ) ) { return; }
	$inactive = is_tax() ? false : in_array( get_queried_object_id(), mytheme_inactive_demo_posts(), true );
	if ( is_tax( 'product_cat' ) ) {
		$term = get_queried_object();
		$inactive = $term && ( 0 === strpos( $term->slug, 'velour-' ) && 'velour' !== mytheme_active_demo() || 0 === strpos( $term->slug, 'decor-' ) && 'home-decor' !== mytheme_active_demo() );
	}
	if ( $inactive ) { wp_safe_redirect( wc_get_page_permalink( 'shop' ), 302 ); exit; }
} );

add_filter( 'render_block_core/template-part', function ( $html, $block ) {
	if ( is_admin() || ! mytheme_active_demo() ) { return $html; }
	$area = $block['attrs']['slug'] ?? '';
	if ( ! in_array( $area, array( 'header', 'footer' ), true ) ) { return $html; }
	$id = mytheme_active_demo();
	$prefix = str_replace( '-', '_', $id );
	$brand = 'velour' === $id ? 'VELOUR ✦' : 'خانه زمان';
	$links = array( 'home' => 'خانه', 'shop' => 'فروشگاه', 'contact' => 'تماس با ما', 'cart' => 'سبد خرید' );
	$html = '<' . $area . ' class="mytheme-demo-' . esc_attr( $area ) . '"><div class="mytheme-demo-inner"><a class="mytheme-demo-brand" href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( $brand ) . '</a><nav aria-label="راهبری فروشگاه">';
	foreach ( $links as $page => $label ) {
		$page_id = (int) get_option( $prefix . '_page_' . $page );
		if ( $page_id ) { $html .= '<a href="' . esc_url( get_permalink( $page_id ) ) . '">' . esc_html( $label ) . '</a>'; }
	}
	$html .= '</nav>';
	if ( 'header' === $area ) { $html .= mytheme_cart_trigger(); }
	return $html . '</div></' . $area . '>';
}, 10, 2 );
