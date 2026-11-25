<?php
/** Shared stepwise importer for the four new demos and the refreshed watch demo. */
require_once dirname( __DIR__, 2 ) . '/inc/demo-import.php';
require_once dirname( __DIR__, 2 ) . '/inc/expanded-demos.php';
$catalog = mytheme_expanded_catalog( $id );
$steps = array();
$prefix = str_replace( '-', '_', $id );
$image_dir = 'home-decor' === $id ? dirname( __DIR__ ) . '/home-decor/images/' : __DIR__ . '/images/';
foreach ( $catalog['items'] as $item ) {
	$steps[] = static function () use ( $id, $item, $image_dir ) {
		mytheme_import_demo_image( $id, $item['key'], $item['image'], $image_dir . $item['image'] );
	};
}
foreach ( $catalog['items'] as $item ) {
	$steps[] = static function () use ( $id, $prefix, $item, $catalog ) {
		$key = $item['key'];
		if ( 'news' === $id ) {
			$slug = 'mytheme-news-' . $key;
			$existing = get_page_by_path( $slug, OBJECT, 'post' );
			if ( $existing && get_post_meta( $existing->ID, '_mytheme_demo', true ) !== 'news' ) { throw new RuntimeException( 'شناسهٔ خبر نمونه با محتوای واقعی سایت تداخل دارد.' ); }
			if ( $existing ) { return; }
			$category = 'news-' . $item['category'];
			$term = get_term_by( 'slug', $category, 'category' );
			if ( ! $term ) {
				$result = wp_insert_term( $catalog['categories'][ $item['category'] ], 'category', array( 'slug' => $category ) );
				if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
				$term_id = $result['term_id'];
			} else { $term_id = $term->term_id; }
			$post_id = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'draft', 'post_name' => $slug, 'post_title' => $item['name'], 'post_excerpt' => 'این نوشته برای نمایش دموی خبری قالب ایجاد شده است.', 'post_content' => '<!-- wp:paragraph --><p>این متن نمونه برای پیش‌نمایش قالب خبری است. محتوای واقعی و منبع خبر را پیش از انتشار جایگزین کنید.</p><!-- /wp:paragraph -->', 'post_category' => array( $term_id ) ), true );
			if ( is_wp_error( $post_id ) ) { throw new RuntimeException( $post_id->get_error_message() ); }
			update_post_meta( $post_id, '_mytheme_demo', 'news' );
			set_post_thumbnail( $post_id, (int) get_option( $prefix . '_image_' . $key ) );
			return;
		}
		$sku = 'mytheme-' . $id . '-' . $key;
		$existing = wc_get_product_id_by_sku( $sku );
		if ( $existing ) {
			if ( get_post_meta( $existing, '_mytheme_demo', true ) !== $id ) { throw new RuntimeException( 'شناسهٔ محصول نمونه با محصول واقعی سایت تداخل دارد.' ); }
			return;
		}
		$slug = $id . '-' . $item['category'];
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( ! $term ) {
			$result = wp_insert_term( $catalog['categories'][ $item['category'] ], 'product_cat', array( 'slug' => $slug ) );
			if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
			$term_id = $result['term_id'];
		} else { $term_id = $term->term_id; }
		$product = new WC_Product_Simple();
		$product->set_name( $item['name'] );
		$product->set_sku( $sku );
		$product->set_regular_price( $item['price'] );
		$product->set_image_id( (int) get_option( $prefix . '_image_' . $key ) );
		$product->set_category_ids( array( $term_id ) );
		$product_id = $product->save();
		if ( ! $product_id ) { throw new RuntimeException( 'محصول نمونه ذخیره نشد.' ); }
		update_post_meta( $product_id, '_mytheme_demo', $id );
	};
}
$pages = array( 'home' => 'صفحه اصلی', 'shop' => 'فروشگاه', 'contact' => 'تماس با ما', 'cart' => 'سبد خرید', 'checkout' => 'پرداخت' );
if ( 'news' === $id ) { $pages['shop'] = 'بایگانی خبرها'; }
foreach ( $pages as $key => $title ) {
	$steps[] = static function () use ( $id, $key, $title ) { mytheme_import_demo_page( $id, $key, $title ); };
}
foreach ( $pages as $key => $title ) {
	$steps[] = static function () use ( $prefix, $key ) {
		$page_id = (int) get_option( $prefix . '_page_' . $key );
		if ( 'publish' === get_post_status( $page_id ) ) { return; }
		$result = wp_update_post( array( 'ID' => $page_id, 'post_status' => 'publish' ), true );
		if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
	};
}
foreach ( $pages as $key => $title ) {
	$steps[] = static function () use ( $id, $prefix, $key, $title, $catalog ) {
		$page_id = (int) get_option( $prefix . '_page_' . $key );
		update_post_meta( $page_id, '_elementor_data', wp_slash( wp_json_encode( mytheme_expanded_layout( $id, $key, $title, $catalog ) ) ) );
		update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
		update_post_meta( $page_id, '_elementor_version', ELEMENTOR_VERSION );
		update_post_meta( $page_id, '_wp_page_template', 'elementor_canvas' );
	};
}
$steps[] = static function () use ( $id ) {
	if ( 'home-decor' === $id ) {
		foreach ( mytheme_demo_sample_products( 'home-decor' ) as $product_id ) {
			$sku = get_post_meta( $product_id, '_sku', true );
			if ( 0 !== strpos( $sku, 'mytheme-home-decor-item-' ) && 'publish' === get_post_status( $product_id ) ) {
				$result = wp_update_post( array( 'ID' => $product_id, 'post_status' => 'trash' ), true );
				if ( is_wp_error( $result ) || 'trash' !== get_post_status( $product_id ) ) { throw new RuntimeException( 'product_state' ); }
			}
		}
		$appearance = (array) get_option( 'mytheme_appearance_home-decor', array() );
		if ( empty( $appearance['palette'] ) || 'sage' === $appearance['palette'] ) {
			$appearance['palette'] = 'obsidian';
			if ( empty( $appearance['accent'] ) || '#354b41' === $appearance['accent'] ) { $appearance['accent'] = '#c9a873'; }
			update_option( 'mytheme_appearance_home-decor', $appearance );
		}
		update_option( 'mytheme_home_decor_luxury_version', 1 );
	}
	\Elementor\Plugin::$instance->files_manager->clear_cache();
};
return $steps;
