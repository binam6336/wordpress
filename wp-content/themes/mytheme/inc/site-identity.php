<?php
/** Shared identity and navigation across every demo. */
function mytheme_identity_image_id( $option ) {
	$id = absint( get_option( $option, 0 ) );
	return $id && wp_attachment_is_image( $id ) ? $id : 0;
}

add_action( 'admin_post_mytheme_identity', function () {
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_theme_options' ) ) { wp_die( 'دسترسی مجاز نیست.', '', array( 'response' => 403 ) ); }
	check_admin_referer( 'mytheme_identity' );
	foreach ( array( 'mytheme_logo_id', 'mytheme_favicon_id' ) as $key ) {
		$id = absint( wp_unslash( $_POST[ $key ] ?? 0 ) );
		if ( $id && ! wp_attachment_is_image( $id ) ) { wp_die( 'فایل انتخابی باید تصویر کتابخانهٔ وردپرس باشد.', '', array( 'response' => 400 ) ); }
		update_option( $key, $id );
	}
	$favicon = mytheme_identity_image_id( 'mytheme_favicon_id' );
	update_option( 'site_icon', $favicon );
	wp_safe_redirect( admin_url( 'admin.php?page=mytheme-settings&step=identity&saved=1' ) );
	exit;
} );

function mytheme_identity_screen() {
	?>
	<div class="mytheme-panel"><h2>لوگو و فاوآیکون</h2><p>این تصاویر در همهٔ دموها مشترک‌اند. لوگو جای نام متنی هدر می‌نشیند و فاوآیکون در سربرگ مرورگر نمایش داده می‌شود.</p>
	<?php if ( ! empty( $_GET['saved'] ) ) : ?><p class="mytheme-feedback is-success" role="status">تصاویر سایت ذخیره شدند.</p><?php endif; ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-mytheme-identity>
	<input type="hidden" name="action" value="mytheme_identity"><?php wp_nonce_field( 'mytheme_identity' ); ?>
	<?php foreach ( array( 'mytheme_logo_id' => array( 'لوگو', 'پیشنهاد: PNG یا WebP شفاف، عرض ۳۲۰ تا ۶۴۰ پیکسل و نسبت افقی نزدیک ۳ به ۱. ارتفاع نمایش در هدر حداکثر ۴۸ پیکسل است.' ), 'mytheme_favicon_id' => array( 'فاوآیکون', 'پیشنهاد: تصویر مربع PNG با اندازهٔ حداقل ۵۱۲ × ۵۱۲ پیکسل.' ) ) as $key => $item ) : $id = mytheme_identity_image_id( $key ); ?>
	<div class="mytheme-media-field" data-media-field><div><strong><?php echo esc_html( $item[0] ); ?></strong><p class="description"><?php echo esc_html( $item[1] ); ?></p><div class="mytheme-media-preview" data-media-preview><?php if ( $id ) { echo wp_get_attachment_image( $id, 'thumbnail' ); } ?></div></div>
	<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $id ); ?>" data-media-id><p><button type="button" class="button" data-media-select>انتخاب یا بارگذاری</button> <button type="button" class="button" data-media-remove <?php disabled( ! $id ); ?>>حذف تصویر</button></p></div>
	<?php endforeach; ?><p><button class="button button-primary" type="submit">ذخیرهٔ هویت سایت</button></p></form></div>
	<?php
}

function mytheme_default_menu_items( $demo ) {
	$items = array( array( 'title' => 'خانه', 'target' => 'home' ), array( 'title' => 'news' === $demo ? 'خبرها' : 'فروشگاه', 'target' => 'shop' ) );
	foreach ( mytheme_demos()[ $demo ]['nav'] ?? array() as $slug => $label ) { $items[] = array( 'title' => $label, 'target' => 'term:' . $slug ); }
	$items[] = array( 'title' => 'تماس با ما', 'target' => 'contact' );
	if ( 'news' !== $demo ) { $items[] = array( 'title' => 'سبد خرید', 'target' => 'cart' ); $items[] = array( 'title' => 'حساب کاربری', 'target' => 'account' ); }
	return $items;
}

function mytheme_menu_items( $demo ) {
	$stored = get_option( 'mytheme_menu_items_' . sanitize_key( $demo ), false );
	return false !== $stored && is_array( $stored ) ? $stored : mytheme_default_menu_items( $demo );
}

function mytheme_menu_target_url( $target, $demo ) {
	$prefix = str_replace( '-', '_', $demo );
	if ( 'home' === $target ) { return home_url( '/' ); }
	if ( 'shop' === $target ) { return 'news' === $demo ? get_permalink( (int) get_option( 'news_page_shop' ) ) : get_post_type_archive_link( 'product' ); }
	if ( 'account' === $target ) { return wc_get_page_permalink( 'myaccount' ); }
	if ( in_array( $target, array( 'contact', 'cart', 'checkout' ), true ) ) { $id = (int) get_option( $prefix . '_page_' . $target ); return $id ? get_permalink( $id ) : ''; }
	if ( 0 === strpos( $target, 'term:' ) ) { $slug = substr( $target, 5 ); $term = get_term_by( 'slug', $slug, 0 === strpos( $slug, 'news-' ) ? 'category' : 'product_cat' ); return $term && ! is_wp_error( $term ) ? get_term_link( $term ) : ''; }
	if ( 0 === strpos( $target, 'page:' ) ) { $id = absint( substr( $target, 5 ) ); return 'publish' === get_post_status( $id ) ? get_permalink( $id ) : ''; }
	if ( 0 === strpos( $target, 'url:' ) ) { return esc_url( substr( $target, 4 ) ); }
	return '';
}

add_action( 'admin_post_mytheme_menu', function () {
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_theme_options' ) ) { wp_die( 'دسترسی مجاز نیست.', '', array( 'response' => 403 ) ); }
	check_admin_referer( 'mytheme_menu' );
	$demo = sanitize_key( wp_unslash( $_POST['demo'] ?? '' ) );
	if ( ! isset( mytheme_demos()[ $demo ] ) ) { wp_die( 'دموی نامعتبر.', '', array( 'response' => 400 ) ); }
	$rows = (array) wp_unslash( $_POST['items'] ?? array() );
	if ( count( $rows ) > 20 ) { wp_die( 'حداکثر ۲۰ گزینهٔ فهرست مجاز است.', '', array( 'response' => 400 ) ); }
	$items = array();
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) { continue; }
		$title = sanitize_text_field( $row['title'] ?? '' );
		$target = sanitize_text_field( $row['target'] ?? '' );
		if ( '' === $title ) { continue; }
		if ( 'custom' === $target ) {
			$url = esc_url_raw( $row['url'] ?? '', array( 'http', 'https' ) );
			if ( ! $url ) { continue; }
			$target = 'url:' . $url;
		} elseif ( ! in_array( $target, array( 'home', 'shop', 'contact', 'cart', 'checkout', 'account' ), true ) && ! ( 0 === strpos( $target, 'page:' ) && 'publish' === get_post_status( absint( substr( $target, 5 ) ) ) ) && ! ( 0 === strpos( $target, 'term:' ) && get_term_by( 'slug', substr( $target, 5 ), 0 === strpos( $target, 'term:news-' ) ? 'category' : 'product_cat' ) ) ) { continue; }
		$items[] = array( 'title' => function_exists( 'mb_substr' ) ? mb_substr( $title, 0, 70 ) : substr( $title, 0, 70 ), 'target' => $target );
	}
	update_option( 'mytheme_menu_items_' . $demo, $items, false );
	wp_safe_redirect( add_query_arg( array( 'page' => 'mytheme-settings', 'step' => 'menu', 'demo' => $demo, 'saved' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
} );

function mytheme_menu_screen() {
	$demo = sanitize_key( wp_unslash( $_GET['demo'] ?? mytheme_active_demo() ) );
	if ( ! isset( mytheme_demos()[ $demo ] ) ) { $demo = 'velour'; }
	$items = mytheme_menu_items( $demo );
	$pages = get_pages( array( 'post_status' => 'publish', 'number' => 200 ) );
	?>
	<div class="mytheme-panel"><h2>فهرست هدر</h2><p>عنوان، مقصد و ترتیب گزینه‌های هر دمو را جدا مدیریت کنید. مقصدهای اصلی مانند «فروشگاه» به صفحهٔ همان دمو می‌روند.</p>
	<div class="mytheme-demo-switch"><?php foreach ( mytheme_demos() as $id => $data ) : ?><a class="button <?php echo $id === $demo ? 'button-primary' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'mytheme-settings', 'step' => 'menu', 'demo' => $id ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $data['title'] ); ?></a><?php endforeach; ?></div>
	<?php if ( ! empty( $_GET['saved'] ) ) : ?><p class="mytheme-feedback is-success" role="status">فهرست ذخیره شد.</p><?php endif; ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-mytheme-menu><input type="hidden" name="action" value="mytheme_menu"><input type="hidden" name="demo" value="<?php echo esc_attr( $demo ); ?>"><?php wp_nonce_field( 'mytheme_menu' ); ?>
	<div data-menu-rows><?php foreach ( $items as $i => $item ) : mytheme_menu_row( $i, $item, $pages ); endforeach; ?></div>
	<p class="mytheme-actions"><button type="button" class="button" data-menu-add>افزودن گزینه</button> <button class="button button-primary" type="submit">ذخیرهٔ فهرست</button></p></form>
	<template id="mytheme-menu-template"><?php mytheme_menu_row( '__INDEX__', array( 'title' => '', 'target' => 'home' ), $pages ); ?></template></div>
	<?php
}

function mytheme_menu_row( $index, $item, $pages ) {
	$target = $item['target'] ?? 'home';
	$custom = 0 === strpos( $target, 'url:' );
	$choices = array( 'home' => 'صفحهٔ اصلی', 'shop' => 'فروشگاه', 'contact' => 'تماس با ما', 'cart' => 'سبد خرید', 'checkout' => 'پرداخت', 'account' => 'حساب کاربری' );
	?>
	<div class="mytheme-menu-row" data-menu-row><label>عنوان<input type="text" maxlength="70" name="items[<?php echo esc_attr( $index ); ?>][title]" value="<?php echo esc_attr( $item['title'] ?? '' ); ?>" required></label>
	<label>مقصد<select name="items[<?php echo esc_attr( $index ); ?>][target]" data-menu-target><?php foreach ( $choices as $value => $name ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $target, $value ); ?>><?php echo esc_html( $name ); ?></option><?php endforeach; ?>
	<optgroup label="دسته‌های دموها"><?php foreach ( mytheme_demos() as $demo ) : foreach ( $demo['nav'] as $slug => $name ) : ?><option value="term:<?php echo esc_attr( $slug ); ?>" <?php selected( $target, 'term:' . $slug ); ?>><?php echo esc_html( $name ); ?></option><?php endforeach; endforeach; ?></optgroup>
	<optgroup label="صفحه‌های سایت"><?php foreach ( $pages as $page ) : ?><option value="page:<?php echo absint( $page->ID ); ?>" <?php selected( $target, 'page:' . $page->ID ); ?>><?php echo esc_html( $page->post_title ); ?></option><?php endforeach; ?></optgroup><option value="custom" <?php selected( $custom ); ?>>لینک دلخواه</option></select></label>
	<label data-menu-url-wrap <?php echo $custom ? '' : 'hidden'; ?>>آدرس لینک<input type="url" name="items[<?php echo esc_attr( $index ); ?>][url]" value="<?php echo $custom ? esc_attr( substr( $target, 4 ) ) : ''; ?>" placeholder="https://example.com"></label>
	<div class="mytheme-menu-actions"><button type="button" class="button" data-menu-up aria-label="انتقال به بالا">↑</button><button type="button" class="button" data-menu-down aria-label="انتقال به پایین">↓</button><button type="button" class="button" data-menu-remove>حذف</button></div></div>
	<?php
}
