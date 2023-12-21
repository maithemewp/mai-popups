<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'acf/init', 'mai_register_popup_block' );
/**
 * Register Mai Popup block.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_register_popup_block() {
	register_block_type( __DIR__ . '/block.json',
		[
			'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!-- Font Awesome Pro 5.15.3 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) --><path d="M464 32H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h416c26.5 0 48-21.5 48-48V80c0-26.5-21.5-48-48-48zm16 400c0 8.8-7.2 16-16 16H48c-8.8 0-16-7.2-16-16V192h448v240zM32 160V80c0-8.8 7.2-16 16-16h416c8.8 0 16 7.2 16 16v80H32z"/></svg>',
		]
	);
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
function mai_do_popup_block( $attributes, $content, $is_preview, $post_id, $wp_block, $context ) {
	$args                 = [];
	$args['class']        = isset( $attributes['className'] ) ? $attributes['className']: '';
	$args['position']     = isset( $attributes['alignContent'] ) ? $attributes['alignContent']: '';
	$args['background']   = isset( $attributes['backgroundColor'] ) ? $attributes['backgroundColor'] : '';
	$args['color']        = isset( $attributes['textColor'] ) ? $attributes['textColor'] : '';
	$args['id']           = get_field( 'id' );
	$args['trigger']      = get_field( 'trigger' );
	$args['animate']      = get_field( 'animate' );
	$args['distance']     = get_field( 'distance' );
	$args['delay']        = get_field( 'delay' );
	$args['width']        = get_field( 'width' );
	$args['padding']      = get_field( 'padding' );
	$args['repeat']       = get_field( 'repeat' );
	$args['repeat_roles'] = get_field( 'repeat_roles' );
	$args['preview']      = $is_preview;
	$template             = [ [ 'core/paragraph', [], [] ] ];
	$inner                = sprintf( '<InnerBlocks template="%s" />', esc_attr( wp_json_encode( $template ) ) );
	$content              = $is_preview ? $inner : $content;
	$content              = do_shortcode( $content );

	$popup = new Mai_Popup( $args, $content );
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

	$defaults = maipopups_get_defaults();

	acf_add_local_field_group(
		[
			'key'    => 'mai_popup_field_group',
			'title'  => __( 'Mai Popup', 'mai-popups'),
			'fields' => [
				[
					'key'           => 'mai_popup_trigger',
					'label'         => __( 'Trigger', 'mai-popups' ),
					'name'          => 'trigger',
					'type'          => 'select',
					'default_value' => $defaults['trigger'],
					'choices'       => [
						'manual' => __( 'Manual (Custom Link)', 'mai-popups' ),
						'load'   => __( 'On Load', 'mai-popups' ),
						'scroll' => __( 'Scroll Distance', 'mai-popups' ),
						'time'   => __( 'Time Delay', 'mai-popups' ),
					],
				],
				[
					'key'           => 'mai_popup_animate',
					'label'         => __( 'Animation', 'mai-popups' ),
					'name'          => 'animate',
					'type'          => 'select',
					'default_value' => $defaults['animate'],
					'choices'       => [
						'fade' => __( 'Fade In', 'mai-popups' ),
						'up'   => __( 'Slide up', 'mai-popups' ),
						'down' => __( 'Slide down', 'mai-popups' ),
					],
				],
				[
					'key'               => 'mai_popup_distance',
					'label'             => __( 'Scroll distance', 'mai-popups' ),
					'name'              => 'distance',
					'type'              => 'number',
					'default_value'     => $defaults['distance'],
					'min'               => 0,
					'max'               => '',
					'step'              => 1,
					'append'            => '%',
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_popup_trigger',
								'operator' => '==',
								'value'    => 'scroll',
							],
						],
					],
				],
				[
					'key'               => 'mai_popup_delay',
					'label'             => __( 'Delay', 'mai-popups' ),
					'name'              => 'delay',
					'type'              => 'number',
					'default_value'     => $defaults['delay'],
					'min'               => 0,
					'max'               => '',
					'step'              => '.5',
					'append'            => __( 'seconds', 'mai-popups' ),
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_popup_trigger',
								'operator' => '==',
								'value'    => 'time',
							],
						],
					],
				],
				[
					'key'           => 'mai_popup_width',
					'label'         => __( 'Width', 'mai-popups' ),
					'instructions'  => __( 'Accepts any CSS value (px, em, rem, vw, ch, etc.). Using 100% removes margin around content.', 'mai-popups' ),
					'name'          => 'width',
					'type'          => 'text',
					'placeholder'   => '600px',
					'default_value' => $defaults['width'],
				],
				[
					'key'           => 'mai_popup_padding',
					'label'         => __( 'Padding', 'mai-popups' ),
					'name'          => 'padding',
					'type'          => 'select',
					'default_value' => $defaults['padding'],
					'choices'       => [
						''     => __( 'None', 'mai-popups' ),
						'sm'   => 'SM',
						'md'   => 'MD',
						'lg'   => 'LG',
						'xl'   => 'XL',
						'xxl'  => 'XXL',
						'xxxl' => 'XXXL',
					]
				],
				[
					'key'               => 'mai_popup_repeat',
					'label'             => __( 'Repeat', 'mai-popups' ),
					'instructions'      => __( 'The time it takes before this popup will be displayed again for the same user. Use 0 to always show, but beware that this may frustrate your website users.', 'mai-popups' ),
					'name'              => 'repeat',
					'type'              => 'text',
					'default_value'     => $defaults['repeat'],
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_popup_trigger',
								'operator' => '!=',
								'value'    => 'manual',
							],
						],
					],
				],
				[
					'key'               => 'mai_popup_repeat_roles',
					'label'             => __( 'Always repeat for user roles', 'mai-popups' ),
					'name'              => 'repeat_roles',
					'instructions'      => __( 'Select user roles that will always see this popup, regardless of the setting above.', 'mai-popups' ),
					'type'              => 'select',
					'default_value'     => $defaults['repeat_roles'],
					'choices'           => [], // Added later.
					'return_format'     => 'value',
					'multiple'          => 1,
					'ui'                => 1,
					'ajax'              => 1,
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_popup_trigger',
								'operator' => '!=',
								'value'    => 'manual',
							],
						],
					],
				],
				[
					'key'          => 'mai_popup_link',
					'label'        => __( 'Link', 'mai-popups' ),
					'instructions' => __( 'Launch this popup by linking any text or button link to the anchor below. The popup must be on the page for the link to work.', 'mai-popups' ),
					'name'         => 'id',
					'type'         => 'text',
				],
			],
			'location' => [
				[
					[
						'param'    => 'block',
						'operator' => '==',
						'value'    => 'acf/mai-popup',
					],
				],
			],
		]
	);
}

add_filter( 'acf/pre_render_field', 'mai_pre_render_popup_padding_field', 10, 2 );
/**
 * Unsets default value if this is an instance of the block
 * added before the padding field was available.
 *
 * @since 0.5.0
 *
 * @param array $field
 * @param mixed $post_id
 *
 * @return array
 */
function mai_pre_render_popup_padding_field( $field, $post_id ) {
	// Bail if not the field we want.
	if ( 'mai_popup_padding' !== $field['key'] ) {
		return $field;
	}

	// Get all field values.
	$fields = get_fields();

	// If no fields were saved, this is a new instance of the block.
	if ( $fields && ! isset( $fields['padding'] ) ) {
		$field['default_value'] = '';
	}

	return $field;
}

add_filter( 'acf/load_field/key=mai_popup_repeat_roles', 'mai_load_popup_repeat_roles' );
/**
 * Loads existing roles as field choices.
 *
 * @since 0.4.0
 *
 * @param array $field The existing field array.
 *
 * @return array
 */
function mai_load_popup_repeat_roles( $field ) {
	$choices  = [];
	$wp_roles = wp_roles();

	foreach ( $wp_roles->roles as $key => $role ) {
		$choices[ $key ] = $role['name'];
	}

	$field['choices'] = $choices;

	return $field;
}

add_filter( 'acf/prepare_field/key=mai_popup_link', 'mai_prepare_popup_id_field' );
/**
 * Sets popup ID and forces readonly.
 *
 * @since 0.1.0
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
