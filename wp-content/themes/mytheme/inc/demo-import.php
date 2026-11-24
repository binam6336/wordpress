<?php
function mytheme_import_demo_image( $demo, $key, $filename, $source ) {
	$option = str_replace( '-', '_', $demo ) . '_image_' . $key;
	$id = (int) get_option( $option );
	if ( $id && 'attachment' === get_post_type( $id ) ) { return; }
	$marker = $demo . ':' . $key;
	$existing = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_mytheme_demo_asset', 'meta_value' => $marker, 'fields' => 'ids', 'posts_per_page' => 1 ) );
	if ( $existing ) { update_option( $option, (int) $existing[0] ); return; }
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	$tmp = wp_tempnam( $filename );
	if ( ! $tmp || ! copy( $source, $tmp ) ) { throw new RuntimeException( 'کپی تصویر ناموفق بود.' ); }
	$mark = static function ( $attachment_id ) use ( $marker ) { update_post_meta( $attachment_id, '_mytheme_demo_asset', $marker ); };
	add_action( 'add_attachment', $mark );
	try { $id = media_handle_sideload( array( 'name' => $filename, 'tmp_name' => $tmp ), 0 ); }
	catch ( Throwable $error ) { wp_delete_file( $tmp ); throw $error; }
	finally { remove_action( 'add_attachment', $mark ); }
	if ( is_wp_error( $id ) ) { wp_delete_file( $tmp ); throw new RuntimeException( $id->get_error_message() ); }
	update_option( $option, $id );
}

function mytheme_import_demo_page( $demo, $key, $title ) {
	$option = str_replace( '-', '_', $demo ) . '_page_' . $key;
	$id = (int) get_option( $option );
	if ( $id && 'page' === get_post_type( $id ) ) { return; }
	global $wpdb;
	$marker = '<!-- mytheme-demo:' . $demo . ':' . $key . ' -->';
	$existing = (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_content = %s LIMIT 1", $marker ) );
	if ( $existing ) { update_option( $option, $existing ); return; }
	$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => $title, 'post_name' => $demo . '-' . $key, 'post_content' => $marker ), true );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	update_option( $option, $id );
}
