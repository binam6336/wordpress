<?php
/**
 * WooCommerce remains the source of prices and currency settings. These filters
 * affect presentation only and never convert stored product or order amounts.
 */
add_filter( 'wc_get_price_decimals', function ( $decimals ) {
	if ( ! function_exists( 'get_woocommerce_currency' ) ) { return $decimals; }
	return in_array( get_woocommerce_currency(), array( 'IRR', 'IRT' ), true ) ? 0 : $decimals;
} );

add_filter( 'woocommerce_price_format', function ( $format ) {
	if ( ! function_exists( 'get_woocommerce_currency' ) ) { return $format; }
	return in_array( get_woocommerce_currency(), array( 'IRR', 'IRT' ), true ) ? '%2$s&nbsp;%1$s' : $format;
} );

add_filter( 'formatted_woocommerce_price', function ( $price ) {
	if ( ! function_exists( 'get_woocommerce_currency' ) || ! in_array( get_woocommerce_currency(), array( 'IRR', 'IRT' ), true ) ) { return $price; }
	return strtr( $price, array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' ) );
} );
