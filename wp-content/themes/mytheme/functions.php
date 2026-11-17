<?php
/**
 * Load the theme stylesheet.
 */

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'mytheme', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
} );
