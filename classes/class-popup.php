<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

// Check for existing class.
if ( ! class_exists( 'Mai_Popup' ) ):
/**
 * Mai Popup class.
 */
class Mai_Popup {
	protected $key = 'mai_popup';
	protected $args;
	protected $content;
	protected static $index = 1;

	/**
	 * Class constructor.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function __construct( $args = [], $content = '' ) {
		// Get args.
		$args = shortcode_atts( maipopups_get_defaults(), $args, $this->key );

		// Sanitize args.
		$args['id']            = sanitize_key( $args['id'] );
		$args['class']         = esc_attr( $args['class'] );
		$args['trigger']       = sanitize_key( $args['trigger'] );
		$args['animate']       = sanitize_key( $args['animate'] );
		$args['distance']      = $this->sanitize_float( $args['distance'] );
		$args['delay']         = $this->sanitize_float( $args['delay'] );
		$args['position']      = esc_html( $args['position'] );
		$args['width']         = trim( esc_html( $args['width'] ) );
		$args['padding']       = sanitize_key( $args['padding'] );
		$args['repeat']        = trim( esc_html( $args['repeat'] ) );
		$args['repeat_roles']  = array_map( 'sanitize_key', (array) $args['repeat_roles'] );
		$args['disable_close'] = rest_sanitize_boolean( $args['disable_close'] );
		$args['background']    = sanitize_key( $args['background'] );
		$args['color']         = sanitize_key( $args['color'] );
		$args['condition']     = rest_sanitize_boolean( is_callable( $args['condition'] ) ? $args['condition']() : $args['condition'] );
		$args['preview']       = rest_sanitize_boolean( $args['preview'] );

		// Set props.
		$this->args    = $args;
		$this->content = $content;
	}

	/**
	 * Sanitize a format a float value.
	 *
	 * @since 0.5.3
	 *
	 * @return string
	 */
	function sanitize_float( $value ) {
		$value = sanitize_text_field( (string) $value );

		// Remove trailing `.0`.
		if ( '.0' === substr( $value, -2 ) ) {
			$value = substr( $value, 0, -2 );
		}

		return $value;
	}

	/**
	 * Display the popup.
	 * Hooks popup content into footer.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function render() {
		static $loaded = [];

		// Bail if this popup was already loaded.
		// Not sure why, but we were getting duplicate popup markup in the footer.
		// Possibly because the intial parse and the content parse were both hooking into the footer.
		if ( isset( $loaded[ $this->args['id'] ] ) ) {
			return;
		}

		// If preview or in footer, display now.
		if ( $this->args['preview'] || $this->is_footer() ) {
			// Display where we are and hope for the best.
			echo $this->get();
		} else {
			// Display in footer.
			add_action( 'wp_footer', function() {
				echo $this->get();
			});
		}

		$loaded[ $this->args['id'] ] = true;
	}

	/**
	 * Gets popup.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	function get() {
		// Check custom condition.
		if ( $this->args['condition'] ) {
			if ( is_callable( $this->args['condition'] ) ) {
				$condition = $this->args['condition']();
			} else {
				$condition = $this->args['condition'];
			}

			if ( ! $condition ) {
				return;
			}
		}

		// Set vars.
		$id           = ltrim( $this->args['id'], '#' );
		$check_cookie = $this->use_cookie();

		// No longer using this because it may be manually linked.
		// See issue #6.
		// Bail if already cookied. Caching may get passed this.
		// if ( $check_cookie && $_COOKIE && isset( $_COOKIE[ $id ] ) ) {
		// 	return;
		// }

		$html  = '';
		$width = sprintf( '%s%s', $this->args['width'], is_numeric( $this->args['width'] ) ? 'px' : '' );
		$atts  = '';
		$args  = [
			'id'           => $id,
			'class'        => 'mai-popup',
			'style'        => '',
			'data-type'    => $this->args['trigger'],
			'data-animate' => $this->args['animate'],
			'data-close'   => $this->args['disable_close'] ? 'false' : 'true',
		];

		// Adds custom classes.
		if ( $this->args['class'] ) {
			$args['class'] .= ' ' . $this->args['class'];
		}

		// Add padding class.
		if ( $this->args['padding'] ) {
			$args['class'] .= sprintf( ' has-%s-padding', $this->args['padding'] );
		}

		// Add background color.
		if ( $this->args['background'] ) {
			$name = 'link' === $this->args['background'] ? 'links' : $this->args['background'];
			$args['class'] .= sprintf( ' has-%s-background-color', $name );
			$args['style'] .= sprintf( '--mai-popup-close-background:var(--color-%s);', $this->args['background'] );
		}

		// Add text color.
		if ( $this->args['color'] ) {
			$name = 'link' === $this->args['color'] ? 'links' : $this->args['color'];
			$args['class'] .= sprintf( ' has-%s-color', $name );
			$args['style'] .= sprintf( '--mai-popup-close-color:var(--color-%s);', $this->args['color'] );
		}

		// Adds width attributes.
		if ( $width ) {
			$args['style'] .= sprintf( '--mai-popup-max-width:%s;', $width );

			if ( in_array( $width, [ '100%', '100vw' ] ) ) {
				$args['data-width'] = 'full';
			}
		}

		// Adds position custom properties.
		if ( $this->args['position'] ) {
			$positions  = array_map( 'trim', explode( ' ', $this->args['position'] ) );
			$vertical   = reset( $positions ) ?: 'center';
			$vertical   = 'top' === $vertical ? 'start' : $vertical;
			$vertical   = 'bottom' === $vertical ? 'end' : $vertical;
			$horizontal = end( $positions ) ?: 'center';
			$horizontal = 'left' === $horizontal ? 'start' : $horizontal;
			$horizontal = 'right' === $horizontal ? 'end' : $horizontal;

			// Set args.
			$args['data-horizontal'] = $horizontal;
			$args['data-vertical']   = $vertical;
		}

		// Sets trigger attributes.
		switch ( $this->args['trigger'] ) {
			case 'time':
				// Set delay in milliseconds.
				$args['data-delay'] = $this->args['delay'] * 1000;
			break;
			case 'scroll':
				// Set scroll distance.
				$args['data-distance'] = $this->args['distance'];
			break;
		}

		// If a cookie popup.
		if ( $check_cookie ) {
			// Add cookie attribute, for JS cookie check since caching sometimes gets through PHP.
			$args['data-cookie'] = 'true';

			// Set expiration.
			$args['data-expire'] = strtotime( '+' . $this->args['repeat'] );
		}

		// Build args.
		foreach ( $args as $att => $value ) {
			if ( ! $value ) {
				continue;
			}

			$atts .= sprintf( ' %s="%s"', $att, trim( $value ) );
		}

		// Set tag.
		$tag = $this->args['preview'] ? 'div' : 'dialog';

		// Build HTML.
		$html .= sprintf( '<%s%s>', $tag, $atts );
			$html .= $this->get_scripts_styles();
			$html .= $this->content;
			$html .= $this->get_close_button();
		$html .= sprintf( '</%s>', $tag );

		return $html;
	}

	/**
	 * Get scripts and styles on-demand.
	 *
	 * @since 0.6.0
	 *
	 * @return string
	 */
	function get_scripts_styles() {
		static $first = true;

		// Start HTML.
		$html = '';

		// If first instance and not in preview. Preview has its own styles and doesn't need scripts.
		if ( $first && ! $this->args['preview'] ) {
			$suffix   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			$version  = MAI_POPUPS_VERSION;
			$css_path = MAI_POPUPS_PLUGIN_DIR . "assets/css/mai-popups{$suffix}.css";
			$css_url  = MAI_POPUPS_PLUGIN_URL . "assets/css/mai-popups{$suffix}.css";
			$js_path  = MAI_POPUPS_PLUGIN_DIR . "assets/js/mai-popups{$suffix}.js";
			$js_url   = MAI_POPUPS_PLUGIN_URL . "assets/js/mai-popups{$suffix}.js";
			$html    .= sprintf( '<link id="mai-popups-css" rel="stylesheet" href="%s?ver=%s">', $css_url, $version . '.' . date( 'njYHi', filemtime( $css_path ) ) );
			$html    .= sprintf( '<script id="mai-popups-js" src="%s?ver=%s"></script>', $js_url, $version . '.' . date( 'njYHi', filemtime( $js_path ) ) );

			$first = false;
		}

		return $html;
	}

	/**
	 * Gets close button markup.
	 *
	 * @since 0.5.0
	 *
	 * @return string
	 */
	function get_close_button() {
		if ( $this->args['disable_close'] ) {
			return;
		}

		$text  = __( 'Close', 'mai-popups' );
		$class = 'mai-popup__close';

		return sprintf( '<button class="%s" aria-label="%s"></button>', $class, $text );
	}

	/**
	 * Checks if popup uses cookie functionality.
	 *
	 * @since 0.4.1
	 *
	 * @return boolean
	 */
	function use_cookie() {
		$use_cookie = ! $this->args['preview'] && in_array( $this->args['trigger'], [ 'time', 'scroll' ] ) && $this->args['repeat'];

		// If settings enable cookies.
		if ( $use_cookie ) {
			// Check if user is in role.
			if ( $this->args['repeat_roles'] && is_user_logged_in() ) {
				foreach ( $this->args['repeat_roles'] as $role ) {
					if ( current_user_can( $role ) ) {
						$use_cookie = false;
						break;
					}
				}
			}
		}

		return $use_cookie;
	}

	/**
	 * If in or after the footer.
	 * This should match `mai_render_popup_block()` in `blocks/mai-popup/block.php`.
	 *
	 * @since 0.2.0
	 *
	 * @return bool
	 */
	function is_footer() {
		return doing_action( 'wp_footer' ) || did_action( 'wp_footer' );
	}

	/**
	 * Enqueues JS and CSS.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function enqueue() {

	}
}
// End ! class_exists check.
endif;
