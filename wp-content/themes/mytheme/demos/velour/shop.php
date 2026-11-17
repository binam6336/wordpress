<!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php
wp_body_open();
echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( wc_get_page_id( 'shop' ), true );
wp_footer();
?></body></html>
