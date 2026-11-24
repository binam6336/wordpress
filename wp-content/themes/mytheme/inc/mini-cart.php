<?php
function mytheme_cart_trigger() {
	if ( ! function_exists( 'wc_get_cart_url' ) ) { return ''; }
	ob_start();
	?>
	<a class="mt-cart-trigger" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="سبد خرید" aria-haspopup="dialog">
		<span class="dashicons dashicons-cart" aria-hidden="true"></span>
		<span class="mt-count" aria-hidden="true" hidden></span>
	</a>
	<?php
	return ob_get_clean();
}
add_shortcode( 'mytheme_cart', 'mytheme_cart_trigger' );

add_action( 'wp_enqueue_scripts', function () {
	if ( ! class_exists( 'WooCommerce' ) ) { return; }
	wp_enqueue_style( 'dashicons' );
	wp_enqueue_script( 'mytheme-cart', get_template_directory_uri() . '/inc/mini-cart.js', array( 'jquery' ), wp_get_theme( get_template() )->get( 'Version' ), true );
	$routes = array();
	foreach ( array( 'cart', 'cart/add-item', 'cart/remove-item', 'cart/update-item' ) as $route ) {
		$routes[ basename( $route ) ] = rest_url( 'wc/store/v1/' . $route );
	}
	wp_localize_script( 'mytheme-cart', 'mythemeCart', array(
		'routes' => $routes, 'restNonce' => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',
	) );
} );

add_action( 'wp_footer', function () {
	if ( ! class_exists( 'WooCommerce' ) ) { return; }
	?>
	<template id="mt-trigger-template"><?php echo mytheme_cart_trigger(); ?></template>
	<dialog id="mt-cart" dir="rtl" aria-labelledby="mt-cart-title">
		<header><h2 id="mt-cart-title">سبد خرید</h2><button type="button" data-close aria-label="بستن">×</button></header>
		<p role="status" class="mt-status"></p>
		<div class="mt-lines"></div>
		<footer hidden><p><strong>جمع کل</strong><bdi data-total></bdi></p>
			<a class="mt-secondary" href="<?php echo esc_url( wc_get_cart_url() ); ?>">مشاهده سبد خرید</a>
			<a class="mt-primary" href="<?php echo esc_url( wc_get_checkout_url() ); ?>">ادامه به پرداخت</a>
		</footer>
	</dialog>
	<?php
} );
