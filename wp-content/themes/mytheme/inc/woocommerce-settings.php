<?php
function mytheme_checkout_mode() {
	return 'block' === get_option( 'mytheme_checkout_mode', 'shortcode' ) ? 'block' : 'shortcode';
}

function mytheme_ensure_block_checkout_page() {
	$content = file_get_contents( get_template_directory() . '/assets/checkout-block.html' );
	if ( ! $content ) { throw new RuntimeException( 'ساختار بلوک پرداخت در قالب یافت نشد.' ); }
	$id = (int) get_option( 'mytheme_block_checkout_page' );
	if ( $id && 'page' === get_post_type( $id ) ) {
		$post = get_post( $id );
		if ( '<!-- wp:woocommerce/checkout /-->' === trim( $post->post_content ) && get_post_meta( $id, '_mytheme_owned_checkout', true ) ) {
			$updated = wp_update_post( array( 'ID' => $id, 'post_content' => $content ), true );
			if ( is_wp_error( $updated ) ) { throw new RuntimeException( 'اصلاح صفحهٔ پرداخت بلاکی ناموفق بود.' ); }
		} elseif ( ! has_block( 'woocommerce/checkout', $post ) ) { throw new RuntimeException( 'صفحهٔ پرداخت بلاکی تغییر کرده و بلوک پرداخت در آن یافت نشد.' ); }
		return $id;
	}
	$id = wp_insert_post( array(
		'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'پرداخت بلاکی',
		'post_name' => 'mytheme-block-checkout', 'post_content' => $content,
	), true );
	if ( is_wp_error( $id ) || ! $id ) { throw new RuntimeException( 'ایجاد صفحهٔ پرداخت بلاکی ناموفق بود.' ); }
	update_post_meta( $id, '_mytheme_owned_checkout', '1' );
	update_option( 'mytheme_block_checkout_page', $id );
	return $id;
}

add_action( 'admin_post_mytheme_woocommerce_settings', function () {
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'دسترسی مجاز نیست.', '', array( 'response' => 403 ) ); }
	check_admin_referer( 'mytheme_woocommerce_settings' );
	$mode = sanitize_key( wp_unslash( $_POST['checkout_mode'] ?? '' ) );
	if ( ! in_array( $mode, array( 'shortcode', 'block' ), true ) ) { wp_die( 'روش پرداخت نامعتبر است.', '', array( 'response' => 400 ) ); }
	$result = 'saved';
	try {
		if ( 'block' === $mode ) { mytheme_ensure_block_checkout_page(); }
		update_option( 'mytheme_checkout_mode', $mode );
		if ( mytheme_checkout_mode() !== $mode ) { throw new RuntimeException( 'ذخیرهٔ تنظیمات انجام نشد.' ); }
	} catch ( Throwable $error ) {
		error_log( '[mytheme checkout] ' . $error->getMessage() );
		$result = 'error';
	}
	wp_safe_redirect( add_query_arg( array( 'page' => 'mytheme-settings', 'step' => 'woocommerce', 'result' => $result ), admin_url( 'admin.php' ) ) );
	exit;
} );

function mytheme_woocommerce_screen() {
	$mode = mytheme_checkout_mode();
	$result = sanitize_key( wp_unslash( $_GET['result'] ?? '' ) );
	?>
	<div class="mytheme-panel"><h2>ووکامرس و قالب</h2>
	<p>نوع صفحهٔ پرداخت را انتخاب کنید. تغییر این گزینه مبلغ، سفارش یا تنظیمات درگاه را تغییر نمی‌دهد.</p>
	<?php if ( 'saved' === $result ) : ?><p class="mytheme-feedback is-success" role="status">تنظیمات پرداخت ذخیره شد.</p><?php elseif ( 'error' === $result ) : ?><p class="mytheme-feedback is-error" role="alert">تنظیمات پرداخت ذخیره نشد. سلامت صفحهٔ پرداخت و گزارش خطا را بررسی کنید.</p><?php endif; ?>
	<?php if ( ! class_exists( 'WooCommerce' ) ) : ?><p class="mytheme-feedback is-error">ووکامرس باید فعال باشد.</p><?php else : ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="mytheme_woocommerce_settings"><?php wp_nonce_field( 'mytheme_woocommerce_settings' ); ?>
	<fieldset class="mytheme-choice-list"><legend>نمایش پرداخت</legend>
	<label><input type="radio" name="checkout_mode" value="shortcode" <?php checked( $mode, 'shortcode' ); ?>><strong>پرداخت کلاسیک</strong><span>شورت‌کد ووکامرس در صفحهٔ المنتوری دمو</span></label>
	<label><input type="radio" name="checkout_mode" value="block" <?php checked( $mode, 'block' ); ?>><strong>پرداخت بلاکی</strong><span>بلوک رسمی WooCommerce Checkout در صفحه‌ای مستقل؛ صفحهٔ المنتوری حفظ می‌شود</span></label>
	</fieldset><p><button type="submit" class="button button-primary">ذخیرهٔ روش پرداخت</button></p></form>
	<p>صفحهٔ فعلی: <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" target="_blank" rel="noopener">مشاهدهٔ پرداخت</a> · <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings' ) ); ?>">تنظیمات اصلی ووکامرس</a></p>
	<?php endif; ?></div>
	<?php
}
