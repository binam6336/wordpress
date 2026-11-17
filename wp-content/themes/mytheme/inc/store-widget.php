<?php
class Mytheme_Store_Widget extends \Elementor\Widget_Base {
	public function get_name() { return 'mytheme-store'; }
	public function get_title() { return 'ابزار فروشگاه'; }
	public function get_icon() { return 'eicon-cart'; }
	protected function register_controls() {
		$this->start_controls_section( 'store', array( 'label' => 'فروشگاه' ) );
		$this->add_control( 'view', array( 'label' => 'نمایش', 'type' => 'select', 'default' => 'catalog', 'options' => array(
			'catalog' => 'محصولات', 'cart' => 'سبد خرید', 'checkout' => 'پرداخت', 'search' => 'جست‌وجو', 'buy' => 'قیمت و خرید',
		) ) );
		$this->add_control( 'product', array( 'label' => 'شناسه محصول', 'type' => 'number', 'condition' => array( 'view' => 'buy' ) ) );
		$this->end_controls_section();
	}
	protected function render() {
		if ( ! class_exists( 'WooCommerce' ) ) { return; }
		$s = $this->get_settings_for_display();
		switch ( $s['view'] ) {
			case 'search': get_product_search_form(); break;
			case 'buy': echo do_shortcode( '[add_to_cart id="' . absint( $s['product'] ) . '"]' ); break;
			case 'cart': echo do_shortcode( '[woocommerce_cart]' ); break;
			case 'checkout': echo do_shortcode( '[woocommerce_checkout]' ); break;
			case 'catalog': echo do_shortcode( '[products paginate="true" limit="12" columns="3"]' ); break;
		}
	}
}
