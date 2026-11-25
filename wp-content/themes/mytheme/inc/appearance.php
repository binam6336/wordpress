<?php
function mytheme_appearance_defaults( $demo ) {
	return array(
		'accent' => 'velour' === $demo ? '#e4b4b3' : '#354b41',
		'palette' => 'velour' === $demo ? 'rose-night' : 'sage',
		'font' => 'vazirmatn',
		'header' => 'full',
		'footer' => 'full',
	);
}

function mytheme_appearance( $demo ) {
	$stored = (array) get_option( 'mytheme_appearance_' . $demo, array() );
	return array_merge( mytheme_appearance_defaults( $demo ), $stored );
}

add_action( 'admin_post_mytheme_appearance', function () {
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_theme_options' ) ) { wp_die( 'اجازهٔ تغییر ظاهر قالب را ندارید.', '', array( 'response' => 403 ) ); }
	check_admin_referer( 'mytheme_appearance' );
	$demo = sanitize_key( wp_unslash( $_POST['demo'] ?? '' ) );
	if ( ! isset( mytheme_demos()[ $demo ] ) ) { wp_die( 'دمو یافت نشد.', '', array( 'response' => 400 ) ); }
	$stored = (array) get_option( 'mytheme_appearance_' . $demo, array() );
	$revision = (int) ( $stored['revision'] ?? 0 );
	$submitted = absint( wp_unslash( $_POST['revision'] ?? 0 ) );
	$status = 'saved';
	if ( $revision !== $submitted ) { $status = 'conflict'; }
	else {
		$next = mytheme_appearance_defaults( $demo );
		if ( 'reset' !== sanitize_key( wp_unslash( $_POST['mode'] ?? '' ) ) ) {
			$accent = sanitize_hex_color( wp_unslash( $_POST['accent'] ?? '' ) );
			$palette = sanitize_key( wp_unslash( $_POST['palette'] ?? '' ) );
			$font = sanitize_key( wp_unslash( $_POST['font'] ?? '' ) );
			$header = sanitize_key( wp_unslash( $_POST['header'] ?? '' ) );
			$footer = sanitize_key( wp_unslash( $_POST['footer'] ?? '' ) );
			if ( ! $accent || ! isset( mytheme_palette_options( $demo )[ $palette ] ) || ! isset( mytheme_font_options()[ $font ] ) || ! in_array( $header, array( 'full', 'compact' ), true ) || ! in_array( $footer, array( 'full', 'simple' ), true ) ) {
				$status = 'invalid';
			} else { $next = compact( 'accent', 'palette', 'font', 'header', 'footer' ); }
		}
		if ( 'saved' === $status ) {
			$next['revision'] = $revision + 1;
			update_option( 'mytheme_appearance_' . $demo, $next );
			if ( mytheme_appearance( $demo )['revision'] !== $next['revision'] ) { $status = 'error'; }
		}
	}
	wp_safe_redirect( add_query_arg( array( 'page' => 'mytheme-settings', 'step' => 'appearance', 'demo' => $demo, 'result' => $status ), admin_url( 'admin.php' ) ) );
	exit;
} );

function mytheme_appearance_screen() {
	$demo = sanitize_key( wp_unslash( $_GET['demo'] ?? mytheme_active_demo() ) );
	if ( ! isset( mytheme_demos()[ $demo ] ) ) { $demo = 'velour'; }
	$settings = mytheme_appearance( $demo );
	$result = sanitize_key( wp_unslash( $_GET['result'] ?? '' ) );
	$feedback = array( 'saved' => 'تنظیمات ظاهر ذخیره شد.', 'conflict' => 'این تنظیمات در صفحهٔ دیگری تغییر کرده‌اند. صفحه را تازه‌سازی کنید.', 'invalid' => 'مقدار یکی از فیلدها معتبر نیست.', 'error' => 'ذخیرهٔ تنظیمات انجام نشد.' );
	?>
	<div class="mytheme-panel"><h2>ظاهر دمو</h2>
	<p>رنگ، فونت و چیدمان هدر و فوتر برای هر دمو جدا ذخیره می‌شوند.</p>
	<div class="mytheme-demo-switch"><?php foreach ( mytheme_demos() as $id => $data ) : ?>
		<a class="button <?php echo $id === $demo ? 'button-primary' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'mytheme-settings', 'step' => 'appearance', 'demo' => $id ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $data['title'] ); ?></a>
	<?php endforeach; ?></div>
	<?php if ( isset( $feedback[ $result ] ) ) : ?><p role="status" class="mytheme-feedback <?php echo 'saved' === $result ? 'is-success' : 'is-error'; ?>"><?php echo esc_html( $feedback[ $result ] ); ?></p><?php endif; ?>
	<div class="mytheme-appearance-grid"><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-appearance-form>
		<input type="hidden" name="action" value="mytheme_appearance"><input type="hidden" name="demo" value="<?php echo esc_attr( $demo ); ?>"><input type="hidden" name="revision" value="<?php echo esc_attr( $settings['revision'] ?? 0 ); ?>"><input type="hidden" name="mode" value="save">
		<?php wp_nonce_field( 'mytheme_appearance' ); ?>
		<fieldset class="mytheme-palette-list"><legend>پالت رنگی</legend><p>یک پایهٔ هماهنگ برای تمام صفحات این دمو انتخاب کنید.</p>
		<?php foreach ( mytheme_palette_options( $demo ) as $slug => $palette ) : ?>
		<label class="mytheme-palette"><input type="radio" name="palette" value="<?php echo esc_attr( $slug ); ?>" data-accent="<?php echo esc_attr( $palette['accent'] ); ?>" data-canvas="<?php echo esc_attr( $palette['canvas'] ); ?>" data-surface="<?php echo esc_attr( $palette['surface'] ); ?>" data-ink="<?php echo esc_attr( $palette['ink'] ); ?>" <?php checked( $settings['palette'], $slug ); ?>><span class="mytheme-palette-swatches" aria-hidden="true"><i style="background:<?php echo esc_attr( $palette['canvas'] ); ?>"></i><i style="background:<?php echo esc_attr( $palette['surface'] ); ?>"></i><i style="background:<?php echo esc_attr( $palette['accent'] ); ?>"></i></span><span><?php echo esc_html( $palette['name'] ); ?></span></label>
		<?php endforeach; ?></fieldset>
		<p><label for="mytheme-accent">رنگ تأکیدی دلخواه</label><br><input id="mytheme-accent" type="color" name="accent" value="<?php echo esc_attr( $settings['accent'] ); ?>"></p>
		<p><label for="mytheme-font">فونت متن</label><br><select id="mytheme-font" name="font">
			<?php foreach ( mytheme_font_options() as $key => $name ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $settings['font'], $key ); ?>><?php echo esc_html( $name ); ?></option><?php endforeach; ?>
		</select></p>
		<p><label for="mytheme-header">هدر</label><br><select id="mytheme-header" name="header"><option value="full" <?php selected( $settings['header'], 'full' ); ?>>کامل</option><option value="compact" <?php selected( $settings['header'], 'compact' ); ?>>فشرده</option></select></p>
		<p><label for="mytheme-footer">فوتر</label><br><select id="mytheme-footer" name="footer"><option value="full" <?php selected( $settings['footer'], 'full' ); ?>>کامل</option><option value="simple" <?php selected( $settings['footer'], 'simple' ); ?>>ساده</option></select></p>
		<p class="mytheme-actions"><button class="button button-primary" type="submit" data-save-appearance disabled>ذخیرهٔ تغییرات</button><button class="button" type="submit" data-reset-appearance>بازنشانی این دمو</button></p>
		<p role="status" data-appearance-status>تغییری ثبت نشده است.</p>
	</form><div class="mytheme-appearance-preview" data-appearance-preview><div class="sample-header"><strong><?php echo esc_html( mytheme_demos()[ $demo ]['brand'] ); ?></strong><span>خانه　 فروشگاه　 سبد خرید</span></div><div class="sample-body"><h3>نمای رنگ و فونت</h3><p>نمونه‌ای از ظاهر دمو پیش از ذخیره</p><button type="button">مشاهدهٔ محصول</button></div><div class="sample-footer">فروشگاه　 تماس با ما</div></div></div>
	</div>
	<?php
}
