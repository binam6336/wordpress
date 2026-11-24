<?php
function home_decor_categories() {
	return array( 'wall' => 'ساعت دیواری', 'table' => 'میز و کنسول', 'gramophone' => 'گرامافون' );
}

function home_decor_widget( $type, $settings ) {
	return array( 'id' => substr( md5( wp_generate_uuid4() ), 0, 8 ), 'elType' => 'widget', 'widgetType' => $type, 'settings' => $settings, 'elements' => array() );
}

function home_decor_box( $class, $children ) {
	return array( 'id' => substr( md5( wp_generate_uuid4() ), 0, 8 ), 'elType' => 'container', 'settings' => array( 'css_classes' => 'decor-' . $class, 'content_width' => 'full' ), 'elements' => $children );
}

function home_decor_heading( $text, $level = 'h2' ) {
	return home_decor_widget( 'heading', array( 'title' => $text, 'header_size' => $level ) );
}

function home_decor_link( $text, $url ) {
	return home_decor_widget( 'button', array( 'text' => $text, 'link' => array( 'url' => $url ) ) );
}

function home_decor_image( $key, $url = '' ) {
	$id = (int) get_option( 'home_decor_image_' . $key );
	return home_decor_widget( 'image', array( 'image' => array( 'id' => $id, 'url' => wp_get_attachment_url( $id ) ), 'image_size' => 'full', 'link_to' => $url ? 'custom' : 'none', 'link' => array( 'url' => $url ) ) );
}

function home_decor_product_card( $item ) {
	$id = wc_get_product_id_by_sku( 'mytheme-home-decor-' . $item['image'] );
	return home_decor_box( 'card', array(
		home_decor_image( $item['image'], get_permalink( $id ) ),
		home_decor_widget( 'heading', array( 'title' => $item['name'], 'header_size' => 'h3', 'link' => array( 'url' => get_permalink( $id ) ) ) ),
		home_decor_widget( 'mytheme-store', array( 'view' => 'buy', 'product' => $id ) ),
	) );
}

function home_decor_layout( $key, $title, $catalog ) {
	$links = array();
	foreach ( array( 'home', 'shop', 'contact', 'cart', 'checkout' ) as $page ) {
		$links[ $page ] = get_permalink( get_option( 'home_decor_page_' . $page ) );
	}
	$category_links = array();
	foreach ( home_decor_categories() as $slug => $label ) {
		$link = get_term_link( 'decor-' . $slug, 'product_cat' );
		$category_links[ $slug ] = is_wp_error( $link ) ? $links['shop'] : $link;
	}
	$nav = array( home_decor_link( 'خانه', $links['home'] ) );
	foreach ( home_decor_categories() as $slug => $label ) {
		$nav[] = home_decor_link( $label, $category_links[ $slug ] );
	}
	$nav[] = home_decor_link( 'تازه‌ها', $links['shop'] );
	$nav[] = home_decor_link( 'تماس با ما', $links['contact'] );
	$content = array(
		home_decor_box( 'notice', array( home_decor_heading( 'انتخابی ماندگار برای خانه شما · مجموعه ساعت و دکوراسیون', 'p' ) ) ),
		home_decor_box( 'utility', array( home_decor_link( 'درباره فروشگاه', $links['contact'] ), home_decor_link( 'مجله خانه', $links['home'] . '#journal' ) ) ),
		home_decor_box( 'mast', array( home_decor_link( 'خانه زمان', $links['home'] ), home_decor_widget( 'mytheme-store', array( 'view' => 'search' ) ), home_decor_widget( 'mytheme-store', array( 'view' => 'mini-cart' ) ) ) ),
		home_decor_box( 'nav', $nav ),
	);
	if ( 'home' === $key ) {
		$stories = array();
		foreach ( array( 'wall', 'table', 'gramophone', 'wall', 'table' ) as $index => $slug ) {
			$story_url = 4 === $index ? add_query_arg( 'mt_sort', 'date', $links['shop'] ) : $category_links[ $slug ];
			$stories[] = home_decor_box( 'story', array( home_decor_image( $slug, $story_url ), home_decor_link( array( 'ساعت‌ها', 'چوب و خانه', 'صدای خاطره', 'منتخب‌ها', 'تازه‌ها' )[ $index ], $story_url ) ) );
		}
		$content[] = home_decor_box( 'stories', $stories );
		$content[] = home_decor_box( 'section-heading', array( home_decor_heading( 'مجموعه‌های خانه', 'h1' ), home_decor_heading( 'از ساعت‌های کلاسیک تا جزئیات دلنشین دکوراسیون', 'p' ) ) );
		$categories = array();
		foreach ( home_decor_categories() as $slug => $label ) {
			$categories[] = home_decor_box( 'category', array( home_decor_image( $slug, $category_links[ $slug ] ), home_decor_heading( $label, 'h2' ), home_decor_link( 'مشاهده مجموعه ←', $category_links[ $slug ] ) ) );
		}
		$content[] = home_decor_box( 'categories', $categories );
		$content[] = home_decor_box( 'section-heading', array( home_decor_heading( 'انتخاب‌های محبوب' ), home_decor_link( 'تازه‌های فروشگاه ←', $links['shop'] ) ) );
		$content[] = home_decor_box( 'grid', array_map( 'home_decor_product_card', array_slice( $catalog['products'], 0, 5 ) ) );
		$content[] = home_decor_box( 'section-heading', array( home_decor_heading( 'برای خانه‌ای دلنشین‌تر' ) ) );
		$articles = array(
			array( 'wall', 'چطور ساعت مناسب انتخاب کنیم؟', 'تناسب ابعاد ساعت با دیوار و مبلمان، نقطه شروع یک انتخاب خوب است.' ),
			array( 'table', 'گرمای چوب در خانه', 'بافت‌های طبیعی، رنگ‌های آرام و جزئیات کاربردی را کنار هم قرار دهید.' ),
			array( 'gramophone', 'گوشه‌ای برای خاطره‌ها', 'یک وسیله کلاسیک می‌تواند به نقطه توجه اتاق تبدیل شود.' ),
		);
		$article_boxes = array();
		foreach ( $articles as $article ) {
			$article_boxes[] = home_decor_box( 'article', array( home_decor_image( $article[0] ), home_decor_heading( $article[1], 'h3' ), home_decor_widget( 'text-editor', array( 'editor' => $article[2] ) ) ) );
		}
		$content[] = home_decor_box( 'journal', $article_boxes );
		$content[] = home_decor_box( 'about', array( home_decor_heading( 'جزئیاتی که خانه را می‌سازند' ), home_decor_widget( 'text-editor', array( 'editor' => 'ساعت، یک میز چوبی یا صدای یک گرامافون می‌تواند حال‌وهوای خانه را تغییر دهد. در این مجموعه، انتخاب‌هایی با الهام از طراحی کلاسیک و گرمای متریال طبیعی کنار هم قرار گرفته‌اند.' ) ) ) );
	} else {
		$content[] = home_decor_box( 'page-heading', array( home_decor_heading( $title, 'h1' ) ) );
		if ( 'contact' === $key ) {
			$content[] = home_decor_box( 'contact', array( home_decor_heading( 'برای راهنمایی خرید با ما در تماس باشید', 'h2' ), home_decor_link( 'ارسال ایمیل به فروشگاه', 'mailto:' . sanitize_email( get_option( 'admin_email' ) ) ) ) );
		} else {
			$content[] = home_decor_box( 'commerce', array( home_decor_widget( 'mytheme-store', array( 'view' => 'shop' === $key ? 'catalog' : $key ) ) ) );
		}
	}
	$content[] = home_decor_box( 'footer', array( home_decor_link( 'خانه زمان', $links['home'] ), home_decor_link( 'فروشگاه', $links['shop'] ), home_decor_link( 'تماس با ما', $links['contact'] ), home_decor_link( 'سبد خرید', $links['cart'] ) ) );
	return array( home_decor_box( 'site', $content ) );
}
