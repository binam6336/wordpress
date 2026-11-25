<?php
/** Local, OFL-licensed Arabic/Persian font subsets; see assets/fonts/*-LICENSE.txt. */
function mytheme_font_options() {
	return array(
		'vazirmatn' => 'وزیرمتن', 'noto-sans-arabic' => 'نوتو سنس عربی', 'noto-naskh-arabic' => 'نوتو نسخ',
		'amiri' => 'امیری', 'cairo' => 'قاهره', 'tajawal' => 'تجوال', 'almarai' => 'المرعی', 'changa' => 'چنگا',
		'el-messiri' => 'المسیری', 'harmattan' => 'هرمتان', 'lateef' => 'لطیف', 'markazi-text' => 'مرکزی',
		'reem-kufi' => 'ریم کوفی', 'aref-ruqaa' => 'عارف رقعه', 'ibm-plex-sans-arabic' => 'آی‌بی‌ام پلکس',
		'readex-pro' => 'ریدکس', 'noto-kufi-arabic' => 'نوتو کوفی', 'mada' => 'مدی',
		'baloo-bhaijaan-2' => 'بالو', 'scheherazade-new' => 'شهرزاد نو',
		'tahoma' => 'تاهوما (سیستمی)', 'arial' => 'آریال (سیستمی)', 'system' => 'فونت دستگاه',
	);
}

function mytheme_font_family( $slug ) {
	if ( 'tahoma' === $slug ) { return 'Tahoma,Arial,sans-serif'; }
	if ( 'arial' === $slug ) { return 'Arial,Tahoma,sans-serif'; }
	if ( 'system' === $slug ) { return 'system-ui,Tahoma,sans-serif'; }
	$family = 'ibm-plex-sans-arabic' === $slug ? 'IBM Plex Sans Arabic' : str_replace( '-', ' ', ucwords( $slug, '-' ) );
	return isset( mytheme_font_options()[ $slug ] ) ? '"MT ' . $family . '",Tahoma,sans-serif' : 'Tahoma,Arial,sans-serif';
}

function mytheme_palette_options( $demo ) {
	if ( 'velour' === $demo ) {
		return array(
			'rose-night' => array( 'name' => 'رز شب', 'canvas' => '#140f16', 'surface' => '#211923', 'raised' => '#2c202c', 'ink' => '#fbf3f0', 'muted' => '#cbbbc4', 'line' => '#493947', 'accent' => '#e4b4b3' ),
			'gold-noir' => array( 'name' => 'طلایی و مشکی', 'canvas' => '#121312', 'surface' => '#20231f', 'raised' => '#2b3028', 'ink' => '#f5f3e8', 'muted' => '#c9c5ad', 'line' => '#4b5043', 'accent' => '#d9bf82' ),
			'blue-hour' => array( 'name' => 'آبی نیمه‌شب', 'canvas' => '#101822', 'surface' => '#1c2935', 'raised' => '#29394a', 'ink' => '#edf5f8', 'muted' => '#b8cbd5', 'line' => '#405465', 'accent' => '#a9cbdc' ),
			'forest' => array( 'name' => 'زمردی', 'canvas' => '#0d1918', 'surface' => '#192725', 'raised' => '#273834', 'ink' => '#eff6ef', 'muted' => '#b8cdc3', 'line' => '#3d554c', 'accent' => '#a4ceb0' ),
			'copper' => array( 'name' => 'مسی', 'canvas' => '#1e1413', 'surface' => '#30211f', 'raised' => '#412d29', 'ink' => '#fff2e9', 'muted' => '#dcc1b4', 'line' => '#654740', 'accent' => '#e2a885' ),
		);
	}
	return array(
		'sage' => array( 'name' => 'مریم‌گلی', 'canvas' => '#f4f2ec', 'surface' => '#fffdf8', 'raised' => '#e9ece5', 'ink' => '#23302d', 'muted' => '#596963', 'line' => '#d4ddd5', 'accent' => '#354b41' ),
		'sand' => array( 'name' => 'شن و سفال', 'canvas' => '#f6f0e6', 'surface' => '#fffbf3', 'raised' => '#f0e2d1', 'ink' => '#3b3028', 'muted' => '#746558', 'line' => '#e2d2bf', 'accent' => '#9b6144' ),
		'blue' => array( 'name' => 'آبی مه‌آلود', 'canvas' => '#eff3f5', 'surface' => '#fbfdfe', 'raised' => '#e0eaf0', 'ink' => '#243541', 'muted' => '#607582', 'line' => '#cddce4', 'accent' => '#3c697d' ),
		'blush' => array( 'name' => 'صورتی خاکی', 'canvas' => '#f8f1ef', 'surface' => '#fffbfa', 'raised' => '#f2e2df', 'ink' => '#403032', 'muted' => '#806b6e', 'line' => '#e9d6d5', 'accent' => '#9a5d67' ),
		'olive' => array( 'name' => 'زیتونی', 'canvas' => '#f3f2e9', 'surface' => '#fcfdf6', 'raised' => '#e7eacb', 'ink' => '#303726', 'muted' => '#687357', 'line' => '#d7ddbe', 'accent' => '#566b3e' ),
	);
}

function mytheme_palette_for( $demo, $slug ) {
	$all = mytheme_palette_options( $demo );
	return $all[ $slug ] ?? reset( $all );
}
