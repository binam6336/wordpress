<?php
require_once __DIR__ . '/layout.php';
$catalog = json_decode( file_get_contents( __DIR__ . '/catalog.json' ), true );
$steps = array();
foreach ( $catalog['images'] as $key => $filename ) {
	$steps[] = static function () use ( $key, $filename ) {
		if ( get_post( (int) get_option( 'velour_image_' . $key ) ) && get_option( 'velour_image_' . $key ) ) { return; }
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		$tmp = wp_tempnam( $filename );
		if ( ! $tmp || ! copy( __DIR__ . '/images/' . $filename, $tmp ) ) { throw new RuntimeException( 'کپی تصویر ناموفق بود.' ); }
		$id = media_handle_sideload( array( 'name' => $filename, 'tmp_name' => $tmp ), 0 );
		if ( is_wp_error( $id ) ) { wp_delete_file( $tmp ); throw new RuntimeException( $id->get_error_message() ); }
		update_option( 'velour_image_' . $key, $id );
	};
}
foreach ( $catalog['products'] as $item ) {
	$steps[] = static function () use ( $item ) {
		$sku = 'mytheme-velour-' . $item['image'];
		if ( wc_get_product_id_by_sku( $sku ) ) { return; }
		$product = new WC_Product_Simple();
		$product->set_name( $item['name'] );
		$product->set_sku( $sku );
		$product->set_regular_price( $item['price'] );
		$product->set_sale_price( $item['sale'] );
		$product->set_image_id( get_option( 'velour_image_' . $item['image'] ) );
		$terms = array();
		foreach ( $item['tags'] as $tag ) {
			$slug = 'velour-' . $tag;
			$term = get_term_by( 'slug', $slug, 'product_cat' );
			if ( $term ) { $terms[] = $term->term_id; continue; }
			$new = wp_insert_term( velour_categories()[ $tag ], 'product_cat', array( 'slug' => $slug ) );
			if ( is_wp_error( $new ) ) { throw new RuntimeException( $new->get_error_message() ); }
			$terms[] = $new['term_id'];
		}
		$product->set_category_ids( $terms );
		$product->save();
	};
}
$pages = array( 'home' => 'صفحه اصلی', 'shop' => 'فروشگاه', 'contact' => 'تماس با ما', 'cart' => 'سبد خرید', 'checkout' => 'پرداخت' );
foreach ( $pages as $key => $title ) {
	$steps[] = static function () use ( $key, $title ) {
		$id = get_option( 'velour_page_' . $key );
		if ( $id && get_post( $id ) ) { return; }
		$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => $title, 'post_name' => 'velour-' . $key ), true );
		if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
		update_option( 'velour_page_' . $key, $id );
	};
}
foreach ( $pages as $key => $title ) {
	$steps[] = static function () use ( $key ) {
		$id = get_option( 'velour_page_' . $key );
		$result = wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ), true );
		if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
	};
}
foreach ( $pages as $key => $title ) {
	$steps[] = static function () use ( $key, $title, $catalog ) {
		$id = get_option( 'velour_page_' . $key );
		update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( velour_layout( $key, $title, $catalog ) ) ) );
		update_post_meta( $id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $id, '_elementor_template_type', 'wp-page' );
		update_post_meta( $id, '_elementor_version', ELEMENTOR_VERSION );
		update_post_meta( $id, '_wp_page_template', 'elementor_canvas' );
	};
}
$steps[] = static function () {
	foreach ( array( 'shop', 'cart', 'checkout' ) as $key ) { update_option( 'woocommerce_' . $key . '_page_id', get_option( 'velour_page_' . $key ) ); }
	update_option( 'page_on_front', get_option( 'velour_page_home' ) );
	update_option( 'show_on_front', 'page' );
	\Elementor\Plugin::$instance->files_manager->clear_cache();
};
return $steps;
