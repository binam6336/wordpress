<?php
/** Update only known demo widgets. Keep a copy of each page's Elementor JSON. */
function mytheme_migrate_demo_media_node( &$node, $demo, $images, &$changed ) {
	if ( ! is_array( $node ) ) { return; }
	if ( 'container' === ( $node['elType'] ?? '' ) && preg_match( '/\b(?:velour|decor)-grid\b/', $node['settings']['css_classes'] ?? '' ) ) {
		$node['elements'] = array( array( 'id' => substr( md5( wp_generate_uuid4() ), 0, 8 ), 'elType' => 'widget', 'widgetType' => 'mytheme-product-grid', 'settings' => array( 'sort' => 'latest', 'limit' => 'velour' === $demo ? 6 : 5 ), 'elements' => array() ) );
		$changed = true;
		return;
	}
	if ( 'widget' === ( $node['elType'] ?? '' ) && 'image' === ( $node['widgetType'] ?? '' ) ) {
		$image_id = (int) ( $node['settings']['image']['id'] ?? 0 );
		if ( $image_id && isset( $images[ $image_id ] ) ) {
			$key = $images[ $image_id ];
			$category = 'hero' === $key ? 'rose' : $key;
			$old_link = $node['settings']['link']['url'] ?? '';
			$node['widgetType'] = 'mytheme-product-image';
			$node['settings'] = array( 'category' => ( 'velour' === $demo ? 'velour-' : 'decor-' ) . $category, 'sort' => 'price_desc', 'link_url' => array( 'url' => $old_link ) );
			$changed = true;
		}
	}
	foreach ( $node['elements'] ?? array() as &$child ) { mytheme_migrate_demo_media_node( $child, $demo, $images, $changed ); }
}

add_action( 'admin_init', static function () {
	if ( ! current_user_can( 'manage_options' ) || get_option( 'mytheme_dynamic_media_version' ) ) { return; }
	foreach ( array( 'velour', 'home-decor' ) as $demo ) {
		$prefix = str_replace( '-', '_', $demo );
		$id = (int) get_option( $prefix . '_page_home' );
		if ( ! $id || 'page' !== get_post_type( $id ) || false === strpos( get_post_field( 'post_content', $id ), 'mytheme-demo:' . $demo . ':home' ) ) { continue; }
		$json = get_post_meta( $id, '_elementor_data', true );
		$data = json_decode( $json, true );
		if ( ! is_array( $data ) ) { continue; }
		$images = array();
		$keys = 'velour' === $demo ? array( 'hero', 'makeup', 'skin', 'hair', 'scent', 'rose' ) : array( 'wall', 'table', 'gramophone' );
		foreach ( $keys as $key ) { $image_id = (int) get_option( $prefix . '_image_' . $key ); if ( $image_id ) { $images[ $image_id ] = $key; } }
		$changed = false;
		foreach ( $data as &$node ) { mytheme_migrate_demo_media_node( $node, $demo, $images, $changed ); }
		unset( $node );
		if ( $changed ) {
			add_post_meta( $id, '_mytheme_elementor_backup_dynamic_v1', $json, true );
			update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
			if ( did_action( 'elementor/loaded' ) ) { \Elementor\Plugin::$instance->files_manager->clear_cache(); }
		}
	}
	update_option( 'mytheme_dynamic_media_version', 1 );
}, 20 );
