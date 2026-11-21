<?php
add_action( 'wp_enqueue_scripts', function () {
	if ( get_option( 'velour_page_home' ) ) {
		wp_enqueue_style( 'mytheme-velour', get_template_directory_uri() . '/demos/velour/style.css', array( 'mytheme' ), '0.3.0' );
	}
} );
// WooCommerce routes the assigned shop page through its product archive.
add_filter( 'template_include', function ( $template ) {
	if ( function_exists( 'is_shop' ) && is_shop() && ! is_search() && did_action( 'elementor/loaded' ) && (int) get_option( 'velour_page_shop' ) === wc_get_page_id( 'shop' ) ) {
		return __DIR__ . '/shop.php';
	}
	return $template;
}, 999 );
