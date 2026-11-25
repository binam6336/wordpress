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
	if ( 'home-decor' === $demo && get_option( 'home_decor_image_wall' ) && ! get_option( 'mytheme_home_decor_luxury_version' ) ) { $demo = 'home-decor-legacy'; }
	$sets = array(
		'home-decor' => array(
			'obsidian' => array( 'ابسیدین', '#111212', '#1e2020', '#2a2d2b', '#f7f3e8', '#bcb5a4', '#494a42', '#c9a873' ),
			'midnight' => array( 'نیمه‌شب', '#101820', '#1b2731', '#293643', '#f4f5f1', '#b4bec4', '#40505a', '#d3b887' ),
			'bronze' => array( 'برنز', '#1c1613', '#2b211c', '#3a2d25', '#f9f1e3', '#c7b6a3', '#59483a', '#d7a878' ),
			'graphite' => array( 'گرافیت', '#18191b', '#292a2e', '#36383d', '#f1f0ee', '#b9bbc1', '#505258', '#b8b9c2' ),
			'ivory' => array( 'عاج', '#f3efe6', '#fffcf6', '#e7dfd2', '#282722', '#6e675b', '#cfc5b5', '#8b6a3b' ),
		),
		'news' => array(
			'paper' => array( 'کاغذ', '#f7f5ef', '#fffefa', '#edeae1', '#17232a', '#596970', '#d9dedb', '#245a75' ),
			'ink' => array( 'جوهر', '#f1f1ed', '#ffffff', '#e4e8e8', '#172026', '#596970', '#ccd3d4', '#283b58' ),
			'blue' => array( 'آبی تحریریه', '#eff4f6', '#fbfdfe', '#dfe9ed', '#183142', '#587180', '#c9dce4', '#286b88' ),
			'sepia' => array( 'سپیا', '#f6f0e4', '#fffaf1', '#ece2d0', '#30291e', '#706658', '#dbd0bb', '#8c5638' ),
			'dusk' => array( 'خبر شب', '#111d26', '#1b2b36', '#273a46', '#f1f5f3', '#afc0c6', '#3c5562', '#9ec8d1' ),
		),
		'leather' => array(
			'chestnut' => array( 'شاه‌بلوطی', '#f3eee6', '#fffcf6', '#eae0d3', '#29211d', '#77685e', '#d9cbbb', '#9b5b3d' ),
			'plum' => array( 'آلویی', '#f3edf0', '#fffafd', '#ebdfe7', '#31222d', '#796673', '#dccbd5', '#8c526b' ),
			'olive' => array( 'زیتونی', '#f2f2ea', '#fffef9', '#e4e8d7', '#272c22', '#6d7765', '#d3d9c6', '#63734b' ),
			'slate' => array( 'سنگی', '#eff0f1', '#fcfdfe', '#dce2e6', '#222c32', '#68757d', '#cbd5da', '#526b79' ),
			'sand' => array( 'شن', '#f9f4e9', '#fffefa', '#ece3d3', '#332c24', '#7a6d5c', '#ded1bd', '#a1744c' ),
		),
		'fashion' => array(
			'monochrome' => array( 'تک‌رنگ', '#f4f3ef', '#ffffff', '#e9e8e3', '#171717', '#676765', '#d6d6d0', '#171717' ),
			'cobalt' => array( 'کبالت', '#f0f3fa', '#ffffff', '#e1e8f6', '#1b2338', '#66738b', '#ced8e9', '#2f52a3' ),
			'blush' => array( 'صورتی ملایم', '#f8f0ef', '#fffaf9', '#f1e1df', '#35272a', '#79686c', '#e5d1d0', '#ad6d79' ),
			'sage' => array( 'مریم‌گلی', '#f1f4ef', '#fcfffa', '#e2eae0', '#263028', '#68776a', '#cedbce', '#496d55' ),
			'night' => array( 'شب', '#18191b', '#252729', '#34373a', '#f4f3ef', '#bdc0c1', '#505457', '#cfdbde' ),
		),
		'market' => array(
			'fresh' => array( 'تازه', '#f5f8ef', '#ffffff', '#e8f0de', '#19372a', '#597361', '#d4e4d5', '#24734b' ),
			'citrus' => array( 'مرکبات', '#f9f8ed', '#fffefa', '#f0efd4', '#354022', '#75805d', '#e0e2be', '#7a8d28' ),
			'tomato' => array( 'گوجه‌ای', '#fff6ee', '#ffffff', '#f9e5d8', '#3a2923', '#85685c', '#e8d1c3', '#be5e3b' ),
			'berry' => array( 'توتی', '#fbf4f6', '#ffffff', '#f1e4e9', '#352630', '#806d77', '#e4d3da', '#9b526e' ),
			'night-market' => array( 'بازار شب', '#14261e', '#20372c', '#2d4839', '#f1f8eb', '#b4cbbb', '#456050', '#acd16e' ),
		),
	);
	if ( isset( $sets[ $demo ] ) ) {
		$palettes = array();
		foreach ( $sets[ $demo ] as $slug => $values ) { $palettes[ $slug ] = array_combine( array( 'name', 'canvas', 'surface', 'raised', 'ink', 'muted', 'line', 'accent' ), $values ); }
		return $palettes;
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
