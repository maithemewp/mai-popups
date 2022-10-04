<?php

// Check for existing class.
if ( ! class_exists( 'Mai_Popup' ) ):
/**
 * Mai Popup class.
 */
class Mai_Popup {
	protected $version = '0.1.0';
	protected $key = 'mai_popup';
	protected $defaults;
	protected $args;
	protected static $index = 1;

	/**
	 * Class constructor.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function __construct( $args = [] ) {
		$this->defaults = maipopups_get_defaults();
		$this->args     = array_map( 'esc_html', shortcode_atts( $this->defaults, $args, $this->key ) );
		$this->args     = array_map( 'trim', $this->args );
	}

	/**
	 * Display the popup.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function render() {
		echo $this->get();
	}

	/**
	 * Gets popup.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	function get() {
		static $first = true;

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

		if ( $first ) {
			add_action( 'wp_footer', [ $this, 'enqueue' ] );
		}

		$html  = '';
		$id    = ltrim( $this->args['id'], '#' );
		$width = sprintf( '%s%s', $this->args['width'], is_numeric( $this->args['width'] ) ? 'px' : '' );
		$atts  = '';
		$args  = [
			'id'           => $id,
			'class'        => sprintf( 'mai-popup-%s', $this->args['trigger'] ),
			'style'        => '',
			'data-animate' => $this->args['animate'],
			'data-overlay' => rest_sanitize_boolean( $this->args['overlay'] ) ? "true" : "false",
		];

		// Adds editor class.
		if ( $this->args['preview'] ) {
			$args['class'] .= ' mai-popup';
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

			// $args['style'] .= sprintf( '--mai-popup-justify-content:%s;--mai-popup-align-items:%s;', $vertical, $horizontal );

			$args['data-horizontal'] = $horizontal;
			$args['data-vertical']   = $vertical;
		}

		// Sets trigger attributes.
		switch ( $this->args['trigger'] ) {
			case 'time':
				// Set delay in milliseconds.
				$args['data-delay'] = absint( $this->args['delay'] ) * 1000;
			break;
			case 'scroll':
				// Set scroll distance.
				$args['data-distance'] = absint( $this->args['distance'] );
			break;
		}

		// If a cookied popup.
		if ( ! current_user_can( 'edit_posts' ) && in_array( $this->args['trigger'], [ 'time', 'scroll' ] ) ) {
			// Bail if already viewed.
			if ( isset( $_COOKIE[ $id ] ) ) {
				return $html;
			}

			// Get expiration.
			$now    = strtotime( 'now' );
			$future = strtotime( '+ ' . $this->args['repeat'] );
			$expire = $future - $now;

			// Set expiration.
			$args['data-expire'] = $expire;
		}

		// Build args.
		foreach ( $args as $att => $value ) {
			$atts .= sprintf( ' %s="%s"', $att, $value );
		}

		// Build HTML.
		$html .= sprintf( '<div%s>', $atts );
			$html .= $this->get_inner_blocks();
			$html .= sprintf( '<button class="mai-popup__close" aria-label="%s"></button>', __( 'Close', 'mai-popups' ) );
		$html .= '</div>';

		$first = false;

		return $html;
	}

	/**
	 * Enqueues JS and CSS.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function enqueue() {
		$suffix  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'mai-popups', MAI_POPUPS_PLUGIN_URL . "/assets/css/mai-popups{$suffix}.css", [], MAI_POPUPS_VERSION );
		wp_enqueue_script( 'mai-popups', MAI_POPUPS_PLUGIN_URL . "/assets/js/mai-popups{$suffix}.js", [], MAI_POPUPS_VERSION, true );
	}

	/**
	 * Gets inner blocks element.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	function get_inner_blocks() {
		return '<InnerBlocks />';
	}
}
// End ! class_exists check.
endif;
