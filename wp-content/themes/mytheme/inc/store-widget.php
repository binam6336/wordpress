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
			case 'search':
				?>
				<form class="velour-search" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<input type="search" name="s" aria-label="جست‌وجوی محصولات" placeholder="جست‌وجو در فروشگاه" value="<?php echo esc_attr( get_search_query( false ) ); ?>">
					<input type="hidden" name="post_type" value="product"><button>جست‌وجو</button>
				</form>
				<?php
				break;
			case 'buy':
				$product = wc_get_product( absint( $s['product'] ) );
				if ( ! $product ) { break; }
				echo '<div class="velour-buy"><span>' . wp_kses_post( $product->get_price_html() ) . '</span>';
				$label = $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ? 'افزودن به سبد' : 'مشاهده محصول';
				echo '<a class="elementor-button" href="' . esc_url( $product->add_to_cart_url() ) . '">' . esc_html( $label ) . '</a></div>';
				break;
			case 'cart': echo do_shortcode( '[woocommerce_cart]' ); break;
			case 'checkout': echo do_shortcode( '[woocommerce_checkout]' ); break;
			case 'catalog': echo do_shortcode( '[products paginate="true" limit="12" columns="3"]' ); break;
		}
	}
}
