<?php
function mytheme_product_sort_options() {
	return array( 'latest' => 'جدیدترین', 'oldest' => 'قدیمی‌ترین', 'price_asc' => 'ارزان‌ترین', 'price_desc' => 'گران‌ترین', 'popular' => 'پرفروش‌ترین', 'featured' => 'ویژه' );
}

function mytheme_dynamic_products( $category = '', $sort = 'latest', $limit = 6 ) {
	if ( ! class_exists( 'WooCommerce' ) ) { return array(); }
	$demo = mytheme_view_demo();
	$limit = max( 1, min( 24, (int) $limit ) );
	$sort = isset( mytheme_product_sort_options()[ $sort ] ) ? $sort : 'latest';
	$category = sanitize_title( $category );
	$excluded = function_exists( 'mytheme_inactive_demo_posts' ) ? mytheme_inactive_demo_posts() : array();
	$query = array( 'limit' => $limit, 'status' => 'publish', 'exclude' => $excluded );
	if ( $category ) { $query['category'] = array( $category ); }
	if ( 'featured' === $sort ) { $query['featured'] = true; }
	if ( 'price_asc' === $sort || 'price_desc' === $sort ) {
		$query['orderby'] = 'price';
		$query['order'] = 'price_asc' === $sort ? 'ASC' : 'DESC';
	} elseif ( 'popular' === $sort ) {
		$query['orderby'] = 'popularity';
		$query['order'] = 'DESC';
	} else {
		$query['orderby'] = 'date';
		$query['order'] = 'oldest' === $sort ? 'ASC' : 'DESC';
	}
	$products = wc_get_products( $query );
	if ( isset( $_GET['mytheme_preview'] ) && mytheme_preview_allowed( $demo ) ) {
		foreach ( mytheme_demo_sample_products( $demo ) as $id ) {
			if ( 'trash' === get_post_status( $id ) ) { $product = wc_get_product( $id ); if ( $product ) { $products[] = $product; } }
		}
	}
	$products = array_values( array_filter( $products, static function ( $product ) use ( $category, $sort ) {
		return ( ! $category || has_term( $category, 'product_cat', $product->get_id() ) ) && ( 'featured' !== $sort || $product->is_featured() );
	} ) );
	usort( $products, static function ( $a, $b ) use ( $sort ) {
		if ( 'price_asc' === $sort || 'price_desc' === $sort ) { return ( 'price_asc' === $sort ? 1 : -1 ) * ( (float) $a->get_price() <=> (float) $b->get_price() ); }
		if ( 'popular' === $sort ) { return (int) $b->get_total_sales() <=> (int) $a->get_total_sales(); }
		$left = $a->get_date_created() ? $a->get_date_created()->getTimestamp() : 0;
		$right = $b->get_date_created() ? $b->get_date_created()->getTimestamp() : 0;
		return ( 'oldest' === $sort ? 1 : -1 ) * ( $left <=> $right );
	} );
	return array_slice( $products, 0, $limit );
}

function mytheme_dynamic_product_url( $product ) {
	$demo = mytheme_view_demo();
	if ( 'trash' === get_post_status( $product->get_id() ) && isset( $_GET['mytheme_preview'] ) && mytheme_preview_allowed( $demo ) ) {
		return add_query_arg( array( 'mytheme_preview' => $demo, 'mytheme_preview_nonce' => wp_create_nonce( 'mytheme_preview_' . $demo ), 'mytheme_preview_product' => $product->get_id() ), home_url( '/' ) );
	}
	return get_permalink( $product->get_id() );
}

class Mytheme_Product_Image_Widget extends \Elementor\Widget_Base {
	public function get_name() { return 'mytheme-product-image'; }
	public function get_title() { return 'تصویر محصول پویا'; }
	public function get_icon() { return 'eicon-product-images'; }
	protected function register_controls() {
		$this->start_controls_section( 'source', array( 'label' => 'منبع تصویر' ) );
		$this->add_control( 'category', array( 'label' => 'شناسهٔ دسته', 'type' => 'text', 'default' => '' ) );
		$this->add_control( 'sort', array( 'label' => 'انتخاب محصول', 'type' => 'select', 'options' => mytheme_product_sort_options(), 'default' => 'latest' ) );
		$this->add_control( 'link_url', array( 'label' => 'پیوند دلخواه', 'type' => 'url' ) );
		$this->end_controls_section();
	}
	protected function render() {
		$s = $this->get_settings_for_display();
		$products = mytheme_dynamic_products( $s['category'] ?? '', $s['sort'] ?? 'latest', 1 );
		if ( ! $products ) { echo '<p>هنوز محصولی برای این دسته وجود ندارد.</p>'; return; }
		$product = $products[0];
		$url = ! empty( $s['link_url']['url'] ) ? $s['link_url']['url'] : mytheme_dynamic_product_url( $product );
		$url = mytheme_demo_front_url( $url, mytheme_view_demo() );
		echo '<figure class="mytheme-product-media"><a href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $product->get_name() ) . '">' . wp_kses_post( $product->get_image( 'woocommerce_single', array( 'loading' => 'lazy', 'alt' => $product->get_name() ) ) ) . '</a></figure>';
	}
}

class Mytheme_Product_Grid_Widget extends \Elementor\Widget_Base {
	public function get_name() { return 'mytheme-product-grid'; }
	public function get_title() { return 'فهرست محصولات پویا'; }
	public function get_icon() { return 'eicon-products'; }
	protected function register_controls() {
		$this->start_controls_section( 'source', array( 'label' => 'محصولات' ) );
		$this->add_control( 'category', array( 'label' => 'شناسهٔ دسته (خالی = همه)', 'type' => 'text', 'default' => '' ) );
		$this->add_control( 'sort', array( 'label' => 'مرتب‌سازی', 'type' => 'select', 'options' => mytheme_product_sort_options(), 'default' => 'latest' ) );
		$this->add_control( 'limit', array( 'label' => 'تعداد', 'type' => 'number', 'default' => 6, 'min' => 1, 'max' => 24 ) );
		$this->end_controls_section();
	}
	protected function render() {
		$s = $this->get_settings_for_display();
		$products = mytheme_dynamic_products( $s['category'] ?? '', $s['sort'] ?? 'latest', $s['limit'] ?? 6 );
		if ( ! $products ) { echo '<p class="mytheme-no-products">محصولی برای نمایش پیدا نشد.</p>'; return; }
		echo '<div class="mytheme-featured-products"><ul class="products">';
		foreach ( $products as $product ) {
			$url = mytheme_dynamic_product_url( $product );
			$name = $product->get_name();
			echo '<li class="product"><a class="mytheme-featured-link" href="' . esc_url( $url ) . '">' . wp_kses_post( $product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy', 'alt' => $name ) ) ) . '<h3>' . esc_html( $name ) . '</h3></a><span class="price">' . wp_kses_post( $product->get_price_html() ) . '</span>';
			if ( 'publish' === get_post_status( $product->get_id() ) && $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) {
				echo '<a class="button" data-mytheme-add="' . absint( $product->get_id() ) . '" href="' . esc_url( $product->add_to_cart_url() ) . '">افزودن به سبد</a>';
			} else { echo '<a class="button" href="' . esc_url( $url ) . '">مشاهدهٔ محصول</a>'; }
			echo '</li>';
		}
		echo '</ul></div>';
	}
}
