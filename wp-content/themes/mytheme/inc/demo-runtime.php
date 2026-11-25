<?php
function mytheme_active_demo() {
	$id = get_option( 'mytheme_active_demo', '' );
	return isset( mytheme_demos()[ $id ] ) ? $id : '';
}

function mytheme_preview_allowed( $id ) {
	$nonce = sanitize_text_field( wp_unslash( $_GET['mytheme_preview_nonce'] ?? '' ) );
	return $id && isset( mytheme_demos()[ $id ] ) && current_user_can( 'manage_options' ) && wp_verify_nonce( $nonce, 'mytheme_preview_' . $id ) && 100 === (int) get_option( 'mytheme_progress_' . $id );
}

function mytheme_view_demo() {
	$id = sanitize_key( wp_unslash( $_GET['mytheme_preview'] ?? '' ) );
	return mytheme_preview_allowed( $id ) ? $id : mytheme_active_demo();
}

function mytheme_demo_preview_url( $id, $page ) {
	$prefix = str_replace( '-', '_', $id );
	if ( 'product' === $page ) {
		$samples = mytheme_demo_sample_products( $id );
		$url = $samples ? ( 'trash' === get_post_status( $samples[0] ) ? add_query_arg( 'mytheme_preview_product', $samples[0], home_url( '/' ) ) : get_permalink( $samples[0] ) ) : '';
	} elseif ( 'shop' === $page ) {
		// WooCommerce redirects its shop page to the product archive and drops preview arguments.
		$url = get_post_type_archive_link( 'product' );
	} else {
		$page_id = (int) get_option( $prefix . '_page_' . $page );
		$url = $page_id ? get_permalink( $page_id ) : '';
	}
	return $url ? add_query_arg( array( 'mytheme_preview' => $id, 'mytheme_preview_nonce' => wp_create_nonce( 'mytheme_preview_' . $id ) ), $url ) : '';
}

// One-time migration for installations created before the demo registry existed.
add_action( 'init', function () {
	if ( (int) get_option( 'mytheme_runtime_version', 0 ) >= 1 ) { return; }
	if ( ! get_option( 'mytheme_active_demo' ) ) {
		$id = (int) get_option( 'velour_page_home' );
		if ( $id && (int) get_option( 'page_on_front' ) === $id ) { update_option( 'mytheme_active_demo', 'velour' ); }
	}
	update_option( 'mytheme_runtime_version', 1 );
}, 0 );

add_filter( 'option_show_on_front', function ( $value ) { return mytheme_view_demo() ? 'page' : $value; } );
add_filter( 'option_page_on_front', function ( $value ) {
	$id = mytheme_view_demo();
	return $id ? (int) get_option( str_replace( '-', '_', $id ) . '_page_home', $value ) : $value;
} );
foreach ( array( 'shop', 'cart', 'checkout' ) as $mytheme_wc_page ) {
	add_filter( 'option_woocommerce_' . $mytheme_wc_page . '_page_id', function ( $value ) use ( $mytheme_wc_page ) {
		if ( 'checkout' === $mytheme_wc_page && 'block' === mytheme_checkout_mode() ) {
			$block_page = (int) get_option( 'mytheme_block_checkout_page' );
			if ( $block_page && 'publish' === get_post_status( $block_page ) && has_block( 'woocommerce/checkout', get_post( $block_page ) ) ) { return $block_page; }
		}
		$id = mytheme_view_demo();
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
			throw new RuntimeException( 'incomplete' );
		}
	}
	$previous = mytheme_active_demo();
	if ( $previous === $id ) { return; }
	// Only products created by this theme qualify. Both markers must match, so
	// merchant-created products, orders, and customers are never modified.
	$changes = array();
	try {
		foreach ( mytheme_demo_sample_products( $id ) as $product_id ) {
			if ( 'trash' === get_post_status( $product_id ) ) {
				$changes[ $product_id ] = 'trash';
				$result = wp_update_post( array( 'ID' => $product_id, 'post_status' => 'publish' ), true );
				if ( is_wp_error( $result ) || 'publish' !== get_post_status( $product_id ) ) { throw new RuntimeException( 'product_state' ); }
			}
		}
		if ( $previous ) {
			foreach ( mytheme_demo_sample_products( $previous ) as $product_id ) {
				if ( 'publish' === get_post_status( $product_id ) ) {
					$changes[ $product_id ] = 'publish';
					$result = wp_update_post( array( 'ID' => $product_id, 'post_status' => 'trash' ), true );
					if ( is_wp_error( $result ) || 'trash' !== get_post_status( $product_id ) ) { throw new RuntimeException( 'product_state' ); }
				}
			}
		}
	} catch ( Throwable $error ) {
		foreach ( $changes as $product_id => $status ) { wp_update_post( array( 'ID' => $product_id, 'post_status' => $status ) ); }
		throw $error;
	}
	$history = (array) get_option( 'mytheme_demo_history', array() );
	$history[] = array( 'version' => 1, 'from' => $previous, 'to' => $id, 'at' => time() );
	$history = array_slice( $history, -10 );
	update_option( 'mytheme_demo_history', $history );
	if ( get_option( 'mytheme_demo_history' ) !== $history ) {
		foreach ( $changes as $product_id => $status ) { wp_update_post( array( 'ID' => $product_id, 'post_status' => $status ) ); }
		throw new RuntimeException( 'storage' );
	}
	update_option( 'mytheme_active_demo', $id );
	if ( get_option( 'mytheme_active_demo' ) !== $id ) {
		array_pop( $history );
		update_option( 'mytheme_demo_history', $history );
		foreach ( $changes as $product_id => $status ) { wp_update_post( array( 'ID' => $product_id, 'post_status' => $status ) ); }
		throw new RuntimeException( 'storage' );
	}
	if ( did_action( 'elementor/loaded' ) ) { \Elementor\Plugin::$instance->files_manager->clear_cache(); }
	if ( function_exists( 'wc_delete_product_transients' ) ) { wc_delete_product_transients(); }
	update_option( 'mytheme_demo_status_' . $id, 'active' );
	if ( $previous ) { update_option( 'mytheme_demo_status_' . $previous, 'installed' ); }
}

function mytheme_demo_sample_products( $id ) {
	global $wpdb;
	if ( ! isset( mytheme_demos()[ $id ] ) ) { return array(); }
	$prefix = $wpdb->esc_like( 'mytheme-' . $id . '-' ) . '%';
	return array_map( 'intval', $wpdb->get_col( $wpdb->prepare(
		"SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} marker ON marker.post_id = p.ID AND marker.meta_key = '_mytheme_demo' AND marker.meta_value = %s INNER JOIN {$wpdb->postmeta} sku ON sku.post_id = p.ID AND sku.meta_key = '_sku' AND sku.meta_value LIKE %s WHERE p.post_type = 'product' AND p.post_status IN ('publish','trash')",
		$id, $prefix
	) ) );
}

add_filter( 'body_class', function ( $classes ) {
	$id = mytheme_view_demo();
	if ( $id ) {
		$classes[] = 'mytheme-demo-' . sanitize_html_class( $id );
		$appearance = mytheme_appearance( $id );
		$classes[] = 'mytheme-header-' . sanitize_html_class( $appearance['header'] );
		$classes[] = 'mytheme-footer-' . sanitize_html_class( $appearance['footer'] );
		if ( mytheme_canvas_shell() ) { $classes[] = 'mytheme-canvas-shell'; }
	}
	return $classes;
} );
add_action( 'wp_enqueue_scripts', function () {
	$id = mytheme_view_demo();
	$demos = mytheme_demos();
	if ( $id && isset( $demos[ $id ]['style'] ) ) {
		wp_enqueue_style( 'mytheme-demo', get_template_directory_uri() . '/' . $demos[ $id ]['style'], array( 'mytheme' ), wp_get_theme()->get( 'Version' ) );
		$appearance = mytheme_appearance( $id );
		$font = mytheme_font_family( $appearance['font'] );
		$palette = mytheme_palette_for( $id, $appearance['palette'] );
		wp_enqueue_style( 'mytheme-fonts', get_template_directory_uri() . '/assets/fonts/fonts.css', array(), wp_get_theme()->get( 'Version' ) );
		wp_enqueue_style( 'mytheme-appearance', get_template_directory_uri() . '/assets/appearance.css', array( 'mytheme-demo', 'mytheme-fonts' ), wp_get_theme()->get( 'Version' ) );
		$selector = 'body.mytheme-demo-' . sanitize_html_class( $id );
		$variables = '';
		foreach ( array( 'canvas', 'surface', 'raised', 'ink', 'muted', 'line' ) as $key ) { $variables .= '--mt-' . $key . ':' . $palette[ $key ] . ';'; }
		$variables .= '--mytheme-accent:' . $appearance['accent'] . ';--mt-rose:' . $appearance['accent'] . ';--mytheme-font:' . $font . ';';
		wp_add_inline_style( 'mytheme-appearance', $selector . '{' . $variables . '}' );
	}
}, 95 );

// Elementor add-ons can enqueue its frontend bundle on Woo canvas pages even
// though Elementor skips its own configuration for these shortcode pages.
add_action( 'wp_enqueue_scripts', function () {
	if ( ! mytheme_view_demo() || ! did_action( 'elementor/loaded' ) || ! is_page() ) { return; }
	$page = get_queried_object_id();
	if ( ! in_array( $page, array( wc_get_page_id( 'cart' ), wc_get_page_id( 'checkout' ) ), true ) ) { return; }
	\Elementor\Plugin::$instance->frontend->enqueue_scripts();
}, 100 );

add_filter( 'template_include', function ( $template ) {
	$id = mytheme_view_demo();
	if ( $id && function_exists( 'is_shop' ) && is_shop() && ! is_search() && did_action( 'elementor/loaded' ) ) {
		return __DIR__ . '/demo-shop.php';
	}
	return $template;
}, 999 );

function mytheme_inactive_demo_posts() {
	static $cache = array();
	$active = mytheme_view_demo();
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
	if ( is_admin() || ! mytheme_view_demo() ) { return; }
	$types = (array) $query->get( 'post_type' );
	$catalog = array_intersect( $types, array( 'product', 'page', 'any' ) );
	if ( ! $catalog && ! ( $query->is_main_query() && ( $query->is_search() || $query->is_page() || $query->is_singular( 'product' ) || $query->is_post_type_archive( 'product' ) || $query->is_tax( array( 'product_cat', 'product_tag' ) ) ) ) ) { return; }
	$query->set( 'post__not_in', array_values( array_unique( array_merge( (array) $query->get( 'post__not_in' ), mytheme_inactive_demo_posts() ) ) ) );
} );
add_action( 'template_redirect', function () {
	if ( isset( $_GET['mytheme_preview'] ) ) {
		$id = sanitize_key( wp_unslash( $_GET['mytheme_preview'] ) );
		if ( ! mytheme_preview_allowed( $id ) ) { wp_die( 'پیش‌نمایش مجاز نیست.', '', array( 'response' => 403 ) ); }
		nocache_headers();
		if ( isset( $_GET['mytheme_preview_product'] ) ) {
			$product_id = absint( wp_unslash( $_GET['mytheme_preview_product'] ) );
			if ( ! in_array( $product_id, mytheme_demo_sample_products( $id ), true ) || 'trash' !== get_post_status( $product_id ) ) { wp_die( 'محصول پیش‌نمایش یافت نشد.', '', array( 'response' => 404 ) ); }
			$product = wc_get_product( $product_id );
			include __DIR__ . '/demo-preview-product.php';
			exit;
		}
	}
	if ( ! mytheme_view_demo() || ! ( is_page() || is_singular( 'product' ) || is_tax( 'product_cat' ) ) ) { return; }
	$inactive = is_tax() ? false : in_array( get_queried_object_id(), mytheme_inactive_demo_posts(), true );
	if ( is_tax( 'product_cat' ) ) {
		$term = get_queried_object();
		$inactive = $term && ( 0 === strpos( $term->slug, 'velour-' ) && 'velour' !== mytheme_view_demo() || 0 === strpos( $term->slug, 'decor-' ) && 'home-decor' !== mytheme_view_demo() );
	}
	if ( $inactive ) { wp_safe_redirect( wc_get_page_permalink( 'shop' ), 302 ); exit; }
} );

function mytheme_demo_front_url( $url, $id ) {
	if ( ! isset( $_GET['mytheme_preview'] ) || ! mytheme_preview_allowed( $id ) ) { return $url; }
	return add_query_arg( array( 'mytheme_preview' => $id, 'mytheme_preview_nonce' => wp_create_nonce( 'mytheme_preview_' . $id ) ), $url );
}

function mytheme_canvas_shell() {
	return isset( $_GET['mytheme_preview_product'] ) || function_exists( 'is_shop' ) && is_shop() || is_page() && 'elementor_canvas' === get_page_template_slug( get_queried_object_id() );
}

function mytheme_demo_shell( $area ) {
	$id = mytheme_view_demo();
	if ( ! $id ) { return ''; }
	$demo = mytheme_demos()[ $id ];
	$prefix = str_replace( '-', '_', $id );
	$links = array( 'home' => 'خانه', 'shop' => 'فروشگاه' );
	foreach ( $demo['nav'] as $slug => $label ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $term ) {
			$url = get_term_link( $term );
			if ( ! is_wp_error( $url ) ) { $links[ $url ] = $label; }
		}
	}
	$links['contact'] = 'تماس با ما';
	$links['cart'] = 'سبد خرید';
	$links['account'] = 'حساب کاربری';
	$nav = '<nav class="mytheme-demo-nav" aria-label="راهبری فروشگاه">';
	foreach ( $links as $page => $label ) {
		if ( 0 === strpos( $page, 'http' ) ) { $url = $page; }
		elseif ( 'account' === $page ) { $url = wc_get_page_permalink( 'myaccount' ); }
		elseif ( 'shop' === $page ) { $url = get_post_type_archive_link( 'product' ); }
		else { $page_id = (int) get_option( $prefix . '_page_' . $page ); $url = $page_id ? get_permalink( $page_id ) : ''; }
		if ( $url ) { $nav .= '<a href="' . esc_url( mytheme_demo_front_url( $url, $id ) ) . '">' . esc_html( $label ) . '</a>'; }
	}
	$nav .= '</nav>';
	$brand = '<a class="mytheme-demo-brand" href="' . esc_url( mytheme_demo_front_url( home_url( '/' ), $id ) ) . '">' . esc_html( $demo['brand'] ) . '</a>';
	if ( 'footer' === $area ) { return '<footer class="mytheme-demo-footer" dir="rtl"><div class="mytheme-demo-inner">' . $brand . $nav . '</div></footer>'; }
	$search = '<form class="mytheme-demo-search" role="search" method="get" action="' . esc_url( home_url( '/' ) ) . '"><input type="search" name="s" value="' . esc_attr( get_search_query( false ) ) . '" aria-label="جست‌وجوی محصولات" placeholder="جست‌وجو در فروشگاه"><input type="hidden" name="post_type" value="product">';
	if ( isset( $_GET['mytheme_preview'] ) && mytheme_preview_allowed( $id ) ) {
		$search .= '<input type="hidden" name="mytheme_preview" value="' . esc_attr( $id ) . '"><input type="hidden" name="mytheme_preview_nonce" value="' . esc_attr( wp_create_nonce( 'mytheme_preview_' . $id ) ) . '">';
	}
	$search .= '<button type="submit">جست‌وجو</button></form>';
	return '<div class="mytheme-demo-promo">' . esc_html( $demo['notice'] ) . '</div><header class="mytheme-demo-header" dir="rtl"><div class="mytheme-demo-inner">' . $brand . $search . mytheme_cart_trigger() . '</div>' . $nav . '</header>';
}
add_filter( 'render_block_core/template-part', function ( $html, $block ) {
	if ( is_admin() || ! mytheme_view_demo() ) { return $html; }
	$area = $block['attrs']['slug'] ?? '';
	return in_array( $area, array( 'header', 'footer' ), true ) ? mytheme_demo_shell( $area ) : $html;
}, 10, 2 );
add_action( 'wp_body_open', function () {
	if ( mytheme_view_demo() && mytheme_canvas_shell() ) { echo mytheme_demo_shell( 'header' ); }
}, 20 );
add_action( 'wp_footer', function () {
	if ( mytheme_view_demo() && mytheme_canvas_shell() ) { echo mytheme_demo_shell( 'footer' ); }
}, 1 );
