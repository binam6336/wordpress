<?php
function velour_categories() {
	return array( 'makeup' => 'آرایش', 'skin' => 'مراقبت پوست', 'hair' => 'مراقبت مو', 'scent' => 'عطر و رایحه', 'rose' => 'مجموعهٔ رز' );
}
function velour_widget( $type, $settings ) {
	return array( 'id' => substr( md5( wp_generate_uuid4() ), 0, 8 ), 'elType' => 'widget', 'widgetType' => $type, 'settings' => $settings, 'elements' => array() );
}
function velour_box( $class, $children ) {
	return array( 'id' => substr( md5( wp_generate_uuid4() ), 0, 8 ), 'elType' => 'container', 'settings' => array( 'css_classes' => 'velour-' . $class, 'content_width' => 'full' ), 'elements' => $children );
}
function velour_heading( $text, $level = 'h2' ) {
	return velour_widget( 'heading', array( 'title' => $text, 'header_size' => $level ) );
}
function velour_link( $text, $url ) {
	return velour_widget( 'button', array( 'text' => $text, 'link' => array( 'url' => $url ) ) );
}
function velour_image( $key, $url = '' ) {
	$id = (int) get_option( 'velour_image_' . $key );
	return velour_widget( 'image', array( 'image' => array( 'id' => $id, 'url' => wp_get_attachment_url( $id ) ), 'image_size' => 'full', 'link_to' => $url ? 'custom' : 'none', 'link' => array( 'url' => $url ) ) );
}
function velour_layout( $key, $title, $catalog ) {
	$links = array();
	foreach ( array( 'home', 'shop', 'contact', 'cart', 'checkout' ) as $page ) { $links[ $page ] = get_permalink( get_option( 'velour_page_' . $page ) ); }
	$categories = array();
	$nav = array( velour_link( 'همهٔ محصولات', $links['shop'] ) );
	foreach ( velour_categories() as $slug => $label ) {
		$url = get_term_link( 'velour-' . $slug, 'product_cat' );
		if ( is_wp_error( $url ) ) { $url = $links['shop']; }
		$nav[] = velour_link( $label, $url );
		$categories[] = velour_box( 'category', array( velour_image( $slug, $url ), velour_link( $label, $url ) ) );
	}
	$content = array(
		velour_box( 'promo', array( velour_heading( '۱۵٪ تخفیف روی محصولات منتخب مجموعهٔ رز', 'p' ) ) ),
		velour_box( 'mast', array( velour_link( 'VELOUR ✦', $links['home'] ), velour_widget( 'mytheme-store', array( 'view' => 'search' ) ), velour_link( 'سبد خرید', $links['cart'] ) ) ),
		velour_box( 'nav', $nav ),
	);
	if ( 'home' === $key ) {
		$content[] = velour_box( 'hero', array( velour_image( 'hero' ), velour_box( 'copy', array( velour_heading( 'مجموعهٔ آرایشی رز', 'p' ), velour_heading( 'رز، در نورِ شب', 'h1' ), velour_widget( 'text-editor', array( 'editor' => 'طیف رز چوبی برای لب و گونه، با بافت‌های سبک و رنگ‌های گرم.' ) ), velour_link( 'دیدن مجموعهٔ رز ←', $url ) ) ) ) );
		$content[] = velour_box( 'categories', $categories );
		$content[] = velour_box( 'section-title', array( velour_heading( 'منتخب وِلور' ), velour_link( 'همهٔ محصولات ←', $links['shop'] ) ) );
		$cards = array();
		foreach ( $catalog['products'] as $item ) {
			$id = wc_get_product_id_by_sku( 'mytheme-velour-' . $item['image'] );
			$bits = array( velour_image( $item['image'], get_permalink( $id ) ) );
			if ( $item['sale'] ) { $bits[] = velour_heading( '۱۵٪ تخفیف', 'span' ); }
			$bits[] = velour_heading( $item['label'], 'span' );
			$bits[] = velour_widget( 'heading', array( 'title' => $item['name'], 'header_size' => 'h3', 'link' => array( 'url' => get_permalink( $id ) ) ) );
			$bits[] = velour_widget( 'mytheme-store', array( 'view' => 'buy', 'product' => $id ) );
			$cards[] = velour_box( 'card', $bits );
		}
		$content[] = velour_box( 'grid', $cards );
		$content[] = velour_box( 'services', array_map( function ( $text ) { return velour_heading( $text, 'p' ); }, array( 'تضمین اصالت کالا', 'ارسال سراسری', 'بسته‌بندی مناسب هدیه' ) ) );
	} else {
		$content[] = velour_heading( $title, 'h1' );
		if ( 'contact' === $key ) {
			$content[] = velour_link( 'ارسال ایمیل به فروشگاه', 'mailto:' . sanitize_email( get_option( 'admin_email' ) ) );
		} else {
			$content[] = velour_widget( 'mytheme-store', array( 'view' => 'shop' === $key ? 'catalog' : $key ) );
		}
	}
	$content[] = velour_box( 'footer', array( velour_link( 'VELOUR ✦', $links['home'] ), velour_link( 'فروشگاه', $links['shop'] ), velour_link( 'تماس با ما', $links['contact'] ), velour_link( 'پرداخت', $links['checkout'] ) ) );
	return array( velour_box( 'site', $content ) );
}
