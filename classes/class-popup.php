<?php

// Check for existing class.
if ( ! class_exists( 'Mai_Popup' ) ):
/**
 * Mai Popup class.
 */
class Mai_Popup {
	protected $version = '0.1.0';
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
		$args = array_map( 'trim', $args );

		// Sanitize args.
		$args['id']        = sanitize_key( $args['id'] );
		$args['trigger']   = sanitize_key( $args['trigger'] );
		$args['animate']   = sanitize_key( $args['animate'] );
		$args['distance']  = preg_replace( '/[0-9]+/', '', $args['distance'] );
		$args['delay']     = preg_replace( '/[0-9]+/', '', $args['delay'] );
		$args['position']  = esc_html( $args['position'] );
		$args['width']     = esc_html( $args['width'] );
		$args['repeat']    = esc_html( $args['repeat'] );
		$args['condition'] = rest_sanitize_boolean( is_callable( $args['condition'] ) ? $args['condition']() : $args['condition'] );
		$args['preview']   = rest_sanitize_boolean( $args['preview'] );

		// Set props.
		$this->args    = $args;
		$this->content = wp_kses_post( $content );
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
		if ( $this->is_footer() ) {
			// Display where we are and hope for the best.
			echo $this->get();
		} else {
			// Display in footer.
			add_action( 'wp_footer', function() {
				echo $this->get();
			});
		}
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

		// If first, enqueue scripts and styles.
		if ( $first ) {
			if ( $this->is_footer() ) {
				$this->enqueue();
			} else {
				add_action( 'wp_footer', [ $this, 'enqueue' ] );
			}
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
			$html .= $this->content;
			$html .= sprintf( '<button class="mai-popup__close" aria-label="%s"></button>', __( 'Close', 'mai-popups' ) );
		$html .= '</div>';

		$first = false;

		return $html;
	}

	/**
	 * If in or after the footer.
	 *
	 * @since 0.2.0
	 *
	 * @return bool
	 */
	function is_footer() {
		return 'wp_footer' === current_action() || did_action( 'wp_footer' );
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
}
// End ! class_exists check.
endif;
