<?php

// Check for existing class.
if ( ! class_exists( 'Mai_Popup_Close' ) ):
/**
 * Mai Popup class.
 */
class Mai_Popup_Close {
	protected $key = 'mai_popup';
	protected $defaults;
	protected $args;

	/**
	 * Class constructor.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function __construct( $args = [] ) {
		$this->defaults = [
			'icon'       => true,
			'icon_size'  => '1em',
			'text'       => '',
			'color'      => '',
			'background' => 'currentColor',
		];
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
		$styles = '';



		if ( $this->args['color'] || $this->args['background'] ) {
			$color      = $this->args['color'];
			$background = $this->args['background'];

			if ( function_exists( 'mai_get_color_css' ) ) {
				$color      = mai_get_color_css( $this->args['color'] );
				$background = mai_get_color_css( $this->args['background'] );
			}

			if ( $this->args['color'] ) {
				$styles .= sprintf( '--mai-popup-close-color:%s;', $color );
			}

			if ( $this->args['background'] ) {
				$styles .= sprintf( '--mai-popup-close-background:%s;', $background );
			}

			$styles = $styles ? sprintf( ' style="%s"', $styles ) : '';
		}

		$html = sprintf( '<button class="mai-popup-close" aria-label="%s"%s>%s%s</button>', __( 'Close', 'mai-popups' ), $styles, $this->get_icon(), $this->args['text'] );

		return $html;
	}

	function get_icon() {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" width="24" height="24"><!-- Font Awesome Pro 5.15.3 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) --><path d="M193.94 256L296.5 153.44l21.15-21.15c3.12-3.12 3.12-8.19 0-11.31l-22.63-22.63c-3.12-3.12-8.19-3.12-11.31 0L160 222.06 36.29 98.34c-3.12-3.12-8.19-3.12-11.31 0L2.34 120.97c-3.12 3.12-3.12 8.19 0 11.31L126.06 256 2.34 379.71c-3.12 3.12-3.12 8.19 0 11.31l22.63 22.63c3.12 3.12 8.19 3.12 11.31 0L160 289.94 262.56 392.5l21.15 21.15c3.12 3.12 8.19 3.12 11.31 0l22.63-22.63c3.12-3.12 3.12-8.19 0-11.31L193.94 256z"/></svg>';
	}
}
// End ! class_exists check.
endif;
