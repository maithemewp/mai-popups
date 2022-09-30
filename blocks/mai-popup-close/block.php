<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'acf/init', 'mai_register_popup_close_block' );
/**
 * Register Mai Popup block.
 *
 * @since 0.1.0
 * @since TBD Converted to block.json via `register_block_type()`.
 *
 * @return void
 */
function mai_register_popup_close_block() {
	register_block_type( __DIR__ . '/block.json' );
}

/**
 * Callback function to render the popup close block.
 *
 * @since 0.1.0
 *
 * @param array    $attributes The block attributes.
 * @param string   $content The block content.
 * @param bool     $is_preview Whether or not the block is being rendered for editing preview.
 * @param int      $post_id The current post being edited or viewed.
 * @param WP_Block $wp_block The block instance (since WP 5.5).
 * @param array    $context The block context array.
 *
 * @return void
 */
function mai_do_popup_close_block( $attributes, $content = '', $is_preview = false, $post_id = 0, $wp_block, $context ) {
	$args               = [];
	$args['class']      = isset( $attributes['className'] ) ? $attributes['className'] : '';
	$args['background'] = isset( $attributes['backgroundColor'] ) ? $attributes['backgroundColor'] : '';
	$args['color']      = isset( $attributes['textColor'] ) ? $attributes['textColor'] : '';
	$args['icon']       = get_field( 'icon' );
	$args['text']       = get_field( 'text' );
	$args['preview']    = $is_preview;

	$popup = new Mai_Popup_Close( $args );
	$popup->render();
}
