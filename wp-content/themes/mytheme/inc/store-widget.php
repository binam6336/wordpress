<?php
class Mytheme_Store_Widget extends \Elementor\Widget_Base {
	public function get_name() { return 'mytheme-store'; }
	public function get_title() { return 'ابزار فروشگاه'; }
	public function get_icon() { return 'eicon-cart'; }
	protected function register_controls() {
		$this->start_controls_section( 'store', array( 'label' => 'فروشگاه' ) );
		$this->add_control( 'view', array( 'label' => 'نمایش', 'type' => 'select', 'default' => 'catalog', 'options' => array(
			'mini-cart' => 'سبد خرید هدر', 'catalog' => 'محصولات', 'cart' => 'سبد خرید', 'checkout' => 'پرداخت', 'search' => 'جست‌وجو', 'buy' => 'قیمت و خرید',
		) ) );
		$this->add_control( 'product', array( 'label' => 'شناسه محصول', 'type' => 'number', 'condition' => array( 'view' => 'buy' ) ) );
		$this->end_controls_section();
	}
	protected function render() {
		if ( ! class_exists( 'WooCommerce' ) ) { return; }
		$s = $this->get_settings_for_display();
		switch ( $s['view'] ) {
			case 'mini-cart': echo mytheme_cart_trigger(); break;
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
				$attribute = $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ? ' data-mytheme-add="' . absint( $product->get_id() ) . '"' : '';
				echo '<a class="elementor-button"' . $attribute . ' href="' . esc_url( $product->add_to_cart_url() ) . '">' . esc_html( $label ) . '</a></div>';
				break;
			case 'cart': echo do_shortcode( '[woocommerce_cart]' ); break;
			case 'checkout': echo do_shortcode( '[woocommerce_checkout]' ); break;
			case 'catalog': mytheme_catalog_widget(); break;
		}
	}
}

function mytheme_catalog_widget() {
	$demo = mytheme_view_demo();
	$available = mytheme_demos()[ $demo ]['nav'] ?? array();
	$category = sanitize_title( wp_unslash( $_GET['mt_category'] ?? '' ) );
	$category = isset( $available[ $category ] ) ? $category : '';
	$sort = sanitize_key( wp_unslash( $_GET['mt_sort'] ?? '' ) );
	$sort = in_array( $sort, array( 'price_asc', 'price_desc', 'date' ), true ) ? $sort : '';
	$search = sanitize_text_field( wp_unslash( $_GET['mt_q'] ?? '' ) );
	$minimum = isset( $_GET['mt_min'] ) && '' !== $_GET['mt_min'] ? absint( wp_unslash( $_GET['mt_min'] ) ) : 0;
	$maximum = isset( $_GET['mt_max'] ) && '' !== $_GET['mt_max'] ? absint( wp_unslash( $_GET['mt_max'] ) ) : 0;
	$shop = get_post_type_archive_link( 'product' );
	?>
	<form class="mytheme-catalog-filters" method="get" action="<?php echo esc_url( $shop ); ?>" role="search">
		<?php if ( ! get_option( 'permalink_structure' ) ) : ?><input type="hidden" name="post_type" value="product"><?php endif; ?>
		<?php if ( isset( $_GET['mytheme_preview'] ) && mytheme_preview_allowed( $demo ) ) : ?><input type="hidden" name="mytheme_preview" value="<?php echo esc_attr( $demo ); ?>"><input type="hidden" name="mytheme_preview_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mytheme_preview_' . $demo ) ); ?>"><?php endif; ?>
		<label>جست‌وجوی محصول<input type="search" name="mt_q" value="<?php echo esc_attr( $search ); ?>" placeholder="نام محصول"></label>
		<label>دسته‌بندی<select name="mt_category"><option value="">همهٔ دسته‌ها</option>
		<?php foreach ( $available as $slug => $name ) : ?><option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $category, $slug ); ?>><?php echo esc_html( $name ); ?></option><?php endforeach; ?>
		</select></label>
		<label>از قیمت<input type="number" name="mt_min" min="0" inputmode="numeric" value="<?php echo $minimum ?: ''; ?>"></label>
		<label>تا قیمت<input type="number" name="mt_max" min="0" inputmode="numeric" value="<?php echo $maximum ?: ''; ?>"></label>
		<label>مرتب‌سازی<select name="mt_sort"><option value="">پیشنهادی</option><option value="price_asc" <?php selected( $sort, 'price_asc' ); ?>>ارزان‌ترین</option><option value="price_desc" <?php selected( $sort, 'price_desc' ); ?>>گران‌ترین</option><option value="date" <?php selected( $sort, 'date' ); ?>>تازه‌ترین</option></select></label>
		<button type="submit">اعمال فیلتر</button><a href="<?php echo esc_url( $shop ); ?>">پاک‌کردن</a>
	</form>
	<?php
	$filter = static function ( $args ) use ( $category, $sort, $search, $minimum, $maximum ) {
		if ( $category ) {
			$args['tax_query'][] = array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $category );
		}
		if ( $minimum || $maximum ) {
			$args['meta_query'][] = array( 'key' => '_price', 'value' => $minimum && $maximum ? array( $minimum, $maximum ) : ( $minimum ?: $maximum ), 'type' => 'NUMERIC', 'compare' => $minimum && $maximum ? 'BETWEEN' : ( $minimum ? '>=' : '<=' ) );
		}
		if ( $search ) { $args['s'] = $search; }
		if ( 'date' === $sort ) { $args['orderby'] = 'date'; $args['order'] = 'DESC'; }
		if ( 'price_asc' === $sort || 'price_desc' === $sort ) { $args['meta_key'] = '_price'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'price_asc' === $sort ? 'ASC' : 'DESC'; }
		return $args;
	};
	$preview = isset( $_GET['mytheme_preview'] ) && mytheme_preview_allowed( $demo );
	$trashed = $preview ? array_filter( mytheme_demo_sample_products( $demo ), static function ( $id ) { return 'trash' === get_post_status( $id ); } ) : array();
	if ( $trashed ) {
		$ids = array_merge( $trashed, get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'fields' => 'ids', 'posts_per_page' => 100 ) ) );
		$products = array_filter( array_map( 'wc_get_product', $ids ), static function ( $product ) use ( $category, $search, $minimum, $maximum ) {
			if ( ! $product ) { return false; }
			if ( $category && ! has_term( $category, 'product_cat', $product->get_id() ) ) { return false; }
			if ( $search && false === stripos( $product->get_name(), $search ) ) { return false; }
			$price = (float) $product->get_price();
			return ( ! $minimum || $price >= $minimum ) && ( ! $maximum || $price <= $maximum );
		} );
		usort( $products, static function ( $a, $b ) use ( $sort ) {
			if ( 'price_asc' === $sort || 'price_desc' === $sort ) { return ( 'price_asc' === $sort ? 1 : -1 ) * ( (float) $a->get_price() <=> (float) $b->get_price() ); }
			return $b->get_date_created()->getTimestamp() <=> $a->get_date_created()->getTimestamp();
		} );
		echo '<div class="woocommerce"><ul class="products columns-3">';
		foreach ( array_slice( $products, 0, 12 ) as $product ) {
			$url = 'trash' === get_post_status( $product->get_id() ) ? add_query_arg( 'mytheme_preview_product', $product->get_id(), home_url( '/' ) ) : get_permalink( $product->get_id() );
			$url = add_query_arg( array( 'mytheme_preview' => $demo, 'mytheme_preview_nonce' => wp_create_nonce( 'mytheme_preview_' . $demo ) ), $url );
			echo '<li class="product"><a href="' . esc_url( $url ) . '">' . wp_kses_post( $product->get_image( 'woocommerce_thumbnail' ) ) . '<h2 class="woocommerce-loop-product__title">' . esc_html( $product->get_name() ) . '</h2></a><span class="price">' . wp_kses_post( $product->get_price_html() ) . '</span><a class="button" href="' . esc_url( $url ) . '">پیش‌نمایش محصول</a></li>';
		}
		echo '</ul></div>';
		if ( ! $products ) { echo '<p>محصولی با این فیلتر پیدا نشد.</p>'; }
		return;
	}
	add_filter( 'woocommerce_shortcode_products_query', $filter );
	try { echo do_shortcode( '[products paginate="true" limit="12" columns="3"]' ); }
	finally { remove_filter( 'woocommerce_shortcode_products_query', $filter ); }
}
