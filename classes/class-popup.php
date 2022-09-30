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
	function __construct( $args ) {
		$this->defaults = mai_get_popup_defaults();
		$this->args     = array_map( 'esc_html', shortcode_atts( $this->defaults, $args, $this->key ) );
		$this->args     = array_map( 'trim', $this->args );
		$this->hooks();
	}

	/**
	 * Runs hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function hooks() {}

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


		if ( $first ) {
			add_action( 'wp_footer', [ $this, 'enqueue' ] );
		}

		$html = '';
		$id   = ltrim( $this->args['id'], '#' );
		$atts = '';
		$args = [
			'id'    => $id,
			'class' => sprintf( 'mai-popup-%s', $this->args['trigger'] ),
			'style' => sprintf( '--mai-popup-max-width:%s;', $this->args['width'] ),
		];

		$now    = strtotime( 'now' );
		$future = strtotime( ' + 1 days' );
		$expire = $future - $now;

		// Sets trigger attributes.
		switch ( $this->args['trigger'] ) {
			case 'time':
				// Set delay in milliseconds.
				$args['data-delay'] = absint( $this->args['delay'] ) * 1000;
				// Set expiration.
				$args['data-expire'] = $expire;
			break;
			case 'scroll':
				// Set scroll distance.
				$args['data-distance'] = absint( $this->args['distance'] );
				// Set expiration.
				$args['data-expire'] = $expire;
			break;
		}

		// Bail if already viewd.
		if ( in_array( $this->args['trigger'], [ 'time', 'scroll' ] ) && isset( $_COOKIE[ $id ] ) ) {
			return $html;
		}

		// Build args.
		foreach ( $args as $att => $value ) {
			$atts .= sprintf( ' %s="%s"', $att, $value );
		}

		// Build HTML.
		$html .= sprintf( '<div%s>', $atts );
			$html .= '<div class="mai-popup__content">';
				$html .= $this->get_inner_blocks();
			$html .= '</div>';
			$html .= ! $this->args['preview'] ? sprintf( '<button class="mai-popup__close" aria-label="%s"></button>', __( 'Close', 'mai-popups' ) ) : '';
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
