<?php
/**
 * Load the theme stylesheet.
 */

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'mytheme', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
}, 90 );

require_once __DIR__ . '/inc/setup.php';
require_once __DIR__ . '/inc/mini-cart.php';
require_once __DIR__ . '/inc/demo-runtime.php';
add_action( 'after_setup_theme', function () {
	add_theme_support( 'woocommerce' );
	add_theme_support( 'title-tag' );
} );
add_action( 'elementor/widgets/register', function ( $manager ) {
	require_once __DIR__ . '/inc/store-widget.php';
	$manager->register( new Mytheme_Store_Widget() );
} );
