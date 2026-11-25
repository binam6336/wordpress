<?php
class Mytheme_News_Feed_Widget extends \Elementor\Widget_Base {
	public function get_name() { return 'mytheme-news-feed'; }
	public function get_title() { return 'فهرست خبرها'; }
	public function get_icon() { return 'eicon-post-list'; }
	protected function register_controls() {
		$this->start_controls_section( 'source', array( 'label' => 'خبرها' ) );
		$this->add_control( 'limit', array( 'label' => 'تعداد', 'type' => 'number', 'default' => 6, 'min' => 1, 'max' => 24 ) );
		$this->add_control( 'sort', array( 'label' => 'ترتیب', 'type' => 'select', 'options' => array( 'latest' => 'جدیدترین', 'oldest' => 'قدیمی‌ترین' ), 'default' => 'latest' ) );
		$this->end_controls_section();
	}
	protected function render() {
		$settings = $this->get_settings_for_display();
		$limit = max( 1, min( 24, (int) ( $settings['limit'] ?? 6 ) ) );
		$order = 'oldest' === ( $settings['sort'] ?? '' ) ? 'ASC' : 'DESC';
		$posts = array();
		if ( 'news' === mytheme_view_demo() ) {
			foreach ( mytheme_demo_sample_news_posts() as $post_id ) {
				$post = get_post( $post_id );
				if ( $post && ( 'publish' === $post->post_status || current_user_can( 'manage_options' ) ) ) { $posts[] = $post; }
			}
		}
		usort( $posts, static function ( $a, $b ) use ( $order ) { return ( 'ASC' === $order ? 1 : -1 ) * strcmp( $a->post_date, $b->post_date ); } );
		$more = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => $limit, 'orderby' => 'date', 'order' => $order, 'post__not_in' => wp_list_pluck( $posts, 'ID' ) ) );
		$posts = array_slice( array_merge( $posts, $more ), 0, $limit );
		if ( ! $posts ) { echo '<p class="mytheme-no-products">هنوز خبری منتشر نشده است.</p>'; return; }
		echo '<div class="mtx-news-list">';
		foreach ( $posts as $index => $post ) {
			$url = 'publish' === $post->post_status ? get_permalink( $post ) : get_preview_post_link( $post );
			$term = get_the_category( $post->ID );
			echo '<article class="mtx-story"><a href="' . esc_url( $url ) . '">' . get_the_post_thumbnail( $post->ID, 'medium_large', array( 'loading' => 'lazy', 'alt' => '' ) ) . '<div class="mtx-story-content"><span class="mtx-story-meta">' . esc_html( $term ? $term[0]->name : 'خبر' ) . ' · ' . esc_html( get_the_date( 'j F Y', $post ) ) . '</span><h3>' . esc_html( get_the_title( $post ) ) . '</h3><p>' . esc_html( get_the_excerpt( $post ) ) . '</p><span class="mtx-story-more">ادامهٔ مطلب ←</span></div></a></article>';
		}
		echo '</div>';
	}
}
