<?php
require_once __DIR__ . '/layout.php';
$catalog = json_decode( file_get_contents( __DIR__ . '/catalog.json' ), true );
$steps = array();

foreach ( $catalog['images'] as $key => $filename ) {
	$steps[] = static function () use ( $key, $filename ) {
		$id = (int) get_option( 'home_decor_image_' . $key );
		if ( $id && get_post( $id ) ) { return; }
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		$tmp = wp_tempnam( $filename );
		if ( ! $tmp || ! copy( __DIR__ . '/images/' . $filename, $tmp ) ) { throw new RuntimeException( 'کپی تصویر ناموفق بود.' ); }
		$id = media_handle_sideload( array( 'name' => $filename, 'tmp_name' => $tmp ), 0 );
		if ( is_wp_error( $id ) ) { wp_delete_file( $tmp ); throw new RuntimeException( $id->get_error_message() ); }
		update_option( 'home_decor_image_' . $key, $id );
	};
}
foreach ( $catalog['products'] as $item ) {
	$steps[] = static function () use ( $item ) {
		$sku = 'mytheme-home-decor-' . $item['image'];
		$id = wc_get_product_id_by_sku( $sku );
		if ( $id ) { update_post_meta( $id, '_mytheme_demo', 'home-decor' ); return; }
		$slug = 'decor-' . $item['category'];
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( ! $term ) {
			$created = wp_insert_term( home_decor_categories()[ $item['category'] ], 'product_cat', array( 'slug' => $slug ) );
			if ( is_wp_error( $created ) ) { throw new RuntimeException( $created->get_error_message() ); }
			$term_id = $created['term_id'];
		} else { $term_id = $term->term_id; }
		$product = new WC_Product_Simple();
		$product->set_name( $item['name'] );
		$product->set_sku( $sku );
		$product->set_regular_price( $item['price'] );
		$product->set_image_id( (int) get_option( 'home_decor_image_' . $item['image'] ) );
		$product->set_category_ids( array( $term_id ) );
		$id = $product->save();
		if ( ! $id ) { throw new RuntimeException( 'محصول ذخیره نشد.' ); }
		update_post_meta( $id, '_mytheme_demo', 'home-decor' );
	};
}
$pages = array( 'home' => 'صفحه اصلی', 'shop' => 'فروشگاه', 'contact' => 'تماس با ما', 'cart' => 'سبد خرید', 'checkout' => 'پرداخت' );
foreach ( $pages as $key => $title ) {
	$steps[] = static function () use ( $key, $title ) {
		$id = (int) get_option( 'home_decor_page_' . $key );
		if ( $id && get_post( $id ) ) { return; }
		$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => $title, 'post_name' => 'home-decor-' . $key ), true );
		if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
		update_option( 'home_decor_page_' . $key, $id );
	};
}
foreach ( $pages as $key => $title ) {
	$steps[] = static function () use ( $key ) {
		$id = (int) get_option( 'home_decor_page_' . $key );
		$result = wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ), true );
		if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
	};
}
foreach ( $pages as $key => $title ) {
	$steps[] = static function () use ( $key, $title, $catalog ) {
		$id = (int) get_option( 'home_decor_page_' . $key );
		update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( home_decor_layout( $key, $title, $catalog ) ) ) );
		update_post_meta( $id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $id, '_elementor_template_type', 'wp-page' );
		update_post_meta( $id, '_elementor_version', ELEMENTOR_VERSION );
		update_post_meta( $id, '_wp_page_template', 'elementor_canvas' );
	};
}
return $steps;
