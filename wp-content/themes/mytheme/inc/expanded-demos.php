<?php
function mytheme_expanded_catalog( $id ) {
	$allowed = array( 'news', 'leather', 'fashion', 'market', 'home-decor' );
	if ( ! in_array( $id, $allowed, true ) ) { throw new RuntimeException( 'دموی نامعتبر.' ); }
	$data = json_decode( file_get_contents( dirname( __DIR__ ) . '/demos/expanded/' . $id . '.json' ), true );
	if ( ! is_array( $data ) || empty( $data['items'] ) ) { throw new RuntimeException( 'فهرست دمو خوانده نشد.' ); }
	return $data;
}

function mytheme_expanded_widget( $type, $settings ) {
	return array( 'id' => substr( md5( wp_generate_uuid4() ), 0, 8 ), 'elType' => 'widget', 'widgetType' => $type, 'settings' => $settings, 'elements' => array() );
}
function mytheme_expanded_box( $class, $elements ) {
	return array( 'id' => substr( md5( wp_generate_uuid4() ), 0, 8 ), 'elType' => 'container', 'settings' => array( 'css_classes' => 'mtx-' . $class, 'content_width' => 'full' ), 'elements' => $elements );
}
function mytheme_expanded_heading( $text, $size = 'h2' ) { return mytheme_expanded_widget( 'heading', array( 'title' => $text, 'header_size' => $size ) ); }
function mytheme_expanded_link( $text, $url ) { return mytheme_expanded_widget( 'button', array( 'text' => $text, 'link' => array( 'url' => $url ) ) ); }

function mytheme_expanded_layout( $id, $page, $title, $catalog ) {
	$prefix = str_replace( '-', '_', $id );
	$urls = array();
	foreach ( array( 'home', 'shop', 'contact', 'cart', 'checkout' ) as $key ) { $urls[ $key ] = get_permalink( (int) get_option( $prefix . '_page_' . $key ) ); }
	$first = $catalog['items'][0];
	$hero_image = 'news' === $id
		? mytheme_expanded_widget( 'image', array( 'image' => array( 'id' => (int) get_option( $prefix . '_image_' . $first['key'] ), 'url' => wp_get_attachment_url( (int) get_option( $prefix . '_image_' . $first['key'] ) ) ), 'image_size' => 'full' ) )
		: mytheme_expanded_widget( 'mytheme-product-image', array( 'category' => $id . '-' . $first['category'], 'sort' => 'price_desc' ) );
	$sections = array();
	if ( 'home' === $page ) {
		$sections[] = mytheme_expanded_box( 'eyebrow', array( mytheme_expanded_heading( $catalog['kicker'], 'p' ), mytheme_expanded_heading( '۰۱ / ۰۶', 'span' ) ) );
		$sections[] = mytheme_expanded_box( 'hero', array( mytheme_expanded_box( 'hero-copy', array( mytheme_expanded_heading( $catalog['kicker'], 'p' ), mytheme_expanded_heading( $catalog['hero_title'], 'h1' ), mytheme_expanded_widget( 'text-editor', array( 'editor' => $catalog['hero_copy'] ) ), mytheme_expanded_link( 'news' === $id ? 'خواندن گزارش‌ها ←' : 'کشف مجموعه ←', $urls['shop'] ) ) ), mytheme_expanded_box( 'hero-media', array( $hero_image ) ) ) );
		$tiles = array();
		foreach ( $catalog['categories'] as $category => $label ) {
			$slug = ( 'news' === $id ? 'news-' : $id . '-' ) . $category;
			$term = get_term_by( 'slug', $slug, 'news' === $id ? 'category' : 'product_cat' );
			$url = $term && ! is_wp_error( $term ) ? get_term_link( $term ) : $urls['shop'];
			$tiles[] = mytheme_expanded_box( 'category', array( mytheme_expanded_heading( $label, 'h3' ), mytheme_expanded_link( 'مشاهده ←', $url ) ) );
		}
		$sections[] = mytheme_expanded_box( 'category-row', $tiles );
		$sections[] = mytheme_expanded_box( 'section-head', array( mytheme_expanded_heading( 'news' === $id ? 'تازه‌ترین روایت‌ها' : 'انتخاب‌های این مجموعه' ), mytheme_expanded_link( 'همهٔ موارد ←', $urls['shop'] ) ) );
		$sections[] = mytheme_expanded_box( 'products', array( mytheme_expanded_widget( 'news' === $id ? 'mytheme-news-feed' : 'mytheme-product-grid', array( 'sort' => 'latest', 'limit' => 6 ) ) ) );
		$sections[] = mytheme_expanded_box( 'closing', array( mytheme_expanded_heading( 'news' === $id ? 'خبر را با زمینه‌اش دنبال کنید' : 'جزئیات، تجربه را می‌سازند' ), mytheme_expanded_widget( 'text-editor', array( 'editor' => 'news' === $id ? 'مطالب این دمو نمونه‌اند. روایت‌های واقعی خود را با ابزارهای وردپرس منتشر کنید.' : 'مجموعه‌ای برای انتخاب آگاهانه، خرید آسان و تجربه‌ای ماندگار.' ) ), mytheme_expanded_link( 'ارتباط با ما ←', $urls['contact'] ) ) );
	} else {
		$sections[] = mytheme_expanded_box( 'page-intro', array( mytheme_expanded_heading( $catalog['kicker'], 'p' ), mytheme_expanded_heading( $title, 'h1' ), mytheme_expanded_widget( 'text-editor', array( 'editor' => $catalog['hero_copy'] ) ) ) );
		if ( 'contact' === $page ) {
			$sections[] = mytheme_expanded_box( 'contact', array( mytheme_expanded_heading( 'برای گفتگو با ما', 'h2' ), mytheme_expanded_link( 'ارسال ایمیل ←', 'mailto:' . sanitize_email( get_option( 'admin_email' ) ) ) ) );
		} elseif ( 'news' === $id && 'shop' === $page ) {
			$sections[] = mytheme_expanded_box( 'products', array( mytheme_expanded_widget( 'mytheme-news-feed', array( 'limit' => 12 ) ) ) );
		} else {
			$sections[] = mytheme_expanded_box( 'commerce', array( mytheme_expanded_widget( 'mytheme-store', array( 'view' => 'shop' === $page ? 'catalog' : $page ) ) ) );
		}
	}
	return array( mytheme_expanded_box( 'site', $sections ) );
}
