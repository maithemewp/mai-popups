<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_filter( 'the_content', function( $content ) {
	$element = '<span id="mai-popups-tracker"></span>';
	$content = $element . $content;

	return $content;
});

add_action( 'acf/init', 'mai_register_popup_block' );
/**
 * Register Mai Popup block.
 *
 * @since 0.1.0
 * @since TBD Converted to block.json via `register_block_type()`.
 *
 * @return void
 */
function mai_register_popup_block() {
	register_block_type( __DIR__ . '/block.json' );
}

/**
 * Callback function to render the popup block.
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
function mai_do_popup_block( $attributes, $content = '', $is_preview = false, $post_id = 0, $wp_block, $context ) {
	$args             = [];
	$args['class']    = isset( $attributes['className'] ) ? $attributes['className']: '';
	$args['position'] = isset( $attributes['alignContent'] ) ? $attributes['alignContent']: '';
	$args['id']       = get_field( 'id' );
	$args['trigger']  = get_field( 'trigger' );
	$args['animate']  = get_field( 'animate' );
	$args['text']     = get_field( 'text' );
	$args['delay']    = get_field( 'delay' );
	$args['width']    = get_field( 'width' );
	$args['overlay']  = get_field( 'overlay' );
	$args['repeat']   = get_field( 'repeat' );
	$args['preview']  = $is_preview;

	$popup = new Mai_Popup( $args );
	$popup->render();
}

add_action( 'acf/init', 'mai_register_popup_field_group' );
/**
 * Register popup field group.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_register_popup_field_group() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

}

add_filter( 'acf/prepare_field/key=field_6333560d1d02e', 'mai_prepare_popup_id_field' );
/**
 * Sets popup ID and forces readonly.
 *
 * @param array $field The field data.
 *
 * @return array
 */
function mai_prepare_popup_id_field( $field ) {
	if ( ! $field['value'] ) {
		$field['value'] = uniqid( '#mai-popup-' );
	}

	$field['readonly'] = true;

	return $field;
}

add_filter( 'render_block', 'mai_render_popup_block', 10, 2 );
/**
 * Moves all popups to footer.
 *
 * @since  0.1.0
 *
 * @param  string $block_content The existing block content.
 * @param  object $block         The button block object.
 *
 * @return string The modified block HTML.
 */
function mai_render_popup_block( $block_content, $block ) {
	if ( ! $block_content ) {
		return $block_content;
	}

	// Bail if not a popup block.
	if ( 'acf/mai-popup' !== $block['blockName'] ) {
		return $block_content;
	}

	// Move to the footer.
	add_action( 'wp_footer', function() use ( $block_content ) {
		echo $block_content;
	});

	// Return empty.
	return '';
}
