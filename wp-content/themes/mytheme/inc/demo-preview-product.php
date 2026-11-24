<!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class( 'mytheme-preview-product' ); ?>>
<?php wp_body_open(); ?>
<main class="mytheme-preview-detail" dir="rtl">
	<p class="mytheme-preview-note">پیش‌نمایش محصول نمونه · پس از فعال‌سازی دمو قابل خرید خواهد بود</p>
	<div class="mytheme-preview-detail-grid">
		<div class="mytheme-preview-detail-image"><?php echo wp_kses_post( $product->get_image( 'woocommerce_single' ) ); ?></div>
		<div><p class="mytheme-preview-kicker">محصول دموی <?php echo esc_html( mytheme_demos()[ $id ]['title'] ); ?></p>
			<h1><?php echo esc_html( $product->get_name() ); ?></h1>
			<p class="mytheme-preview-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
			<p>جزئیات، تصاویر و قیمت این محصول را در نمای دمو بررسی کنید.</p>
			<a class="button" href="<?php echo esc_url( mytheme_demo_preview_url( $id, 'shop' ) ); ?>">بازگشت به فروشگاه</a>
		</div>
	</div>
</main>
<?php wp_footer(); ?>
</body></html>
