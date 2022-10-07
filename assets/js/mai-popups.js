( function() {
	/**
	 * Wait until page is loaded.
	 */
	window.addEventListener( 'load', function( event ) {
		const timed    = document.querySelectorAll( '.mai-popup-time' );
		const scrolls  = document.querySelectorAll( '.mai-popup-scroll' );
		const loads    = document.querySelectorAll( '.mai-popup-load' );
		const triggers = document.querySelectorAll( '[href^="#mai-popup-"]' );

		// Bail if no popups.
		if ( ! ( timed.length || scrolls.length || loads.length || triggers.length ) ) {
			return;
		}

		// let open = [];

		const openPopup = function( popup, event = false ) {
			if ( 'string' === typeof popup ) {
				popup = document.querySelector( popup );
			}

			// Bail if no popup.
			if ( ! popup ) {
				return;
			}

			popup.showModal();

			const closeBtn = popup.querySelector( '.mai-popup__close' );

			closeBtn.addEventListener( 'click', function( event ) {
				popup.close();
			});

			// const style      = popup.getAttribute( 'style' );
			// const animate    = popup.getAttribute( 'data-animate' );
			// const horizontal = popup.getAttribute( 'data-horizontal' );
			// const vertical   = popup.getAttribute( 'data-vertical' );
			// const width      = popup.getAttribute( 'data-width' );

			// Set vars.
			// var el     = instance.element();
			// var closes = el.querySelectorAll( '.mai-popup__close' );

			// Set attributes from our wrapper.
			// el.setAttribute( 'style', style );
			// el.setAttribute( 'data-animate', animate );
			// el.setAttribute( 'data-horizontal', horizontal );
			// el.setAttribute( 'data-vertical', vertical );

			// if ( width ) {
			// 	el.setAttribute( 'data-width', width );
			// }

			// Close when hitting close icon or opening another popup.
			// closes.forEach( function( close ) {
			// 	close.addEventListener( 'click', function( event ) {
			// 		instance.close();
			// 	});
			// });
		}

		const closePopup = function( popup ) {
			// Remove from open popups.
			// open = open.splice( open.indexOf( instance ), 1 );

			// Get element.
			var seconds = popup.getAttribute( 'data-expire' );

			// If expiring.
			if ( seconds ) {
				// Build cookie.
				var expire = new Date();
				expire.setSeconds( expire.getSeconds() + parseInt(seconds) );
				var name   = popup.getAttribute( 'id' );
				var utc    = expire.toUTCString();
				var cookie = name + '=1;expires=' + utc + ';path=/;SameSite=Strict;';

				// Set cookie.
				document.cookie = cookie;
			}

			popup.close();
		}

		// // Show and set focus.
		// instance.show(() => {
		// 	var element = instance.element();
		// 	var toFocus = element.querySelector( '.mai-popup__content' );

		// 	if ( toFocus ) {
		// 		toFocus.setAttribute( 'tabindex', 0 );
		// 		toFocus.focus();
		// 	}
		// });

		/*************************
		 * Sets up timed popups. *
		 *************************/
		if ( timed.length ) {
			timed.forEach( function( popup ) {
				setTimeout( function() {
					openPopup( popup );
				}, parseInt( popup.getAttribute( 'data-delay' ) ) );
			});
		}

		/*************************
		 * Sets up on load popups. *
		 *************************/
		 if ( loads.length ) {
			loads.forEach( function( popup ) {
				openPopup( popup );
			});
		}

		/*****************************
		 * Sets up triggered popups. *
		 *****************************/
		 if ( triggers.length ) {
			triggers.forEach( function( trigger ) {
				trigger.addEventListener( 'click', function( event ) {
					event.preventDefault();
					openPopup( event.target.getAttribute( 'href' ) );
				}, false );
			});
		}

		/***********************************
		 * Sets up scroll distance popups. *
		 ***********************************/
		if ( scrolls.length ) {
			/**
			 * Debounce functions for better performance.
			 * (c) 2021 Chris Ferdinandi, MIT License, https://gomakethings.com
			 * @link https://vanillajstoolkit.com/helpers/debounce/
			 * @param {Function} fn The function to debounce.
			 */
			const debounce = function( fn ) {
				// Setup a timer.
				let timeout;

				// Return a function to run debounced.
				return function() {
					// Setup the arguments.
					let context = this;
					let args    = arguments;

					// If there's a timer, cancel it.
					if ( timeout ) {
						window.cancelAnimationFrame(timeout);
					}

					// Setup the new requestAnimationFrame().
					timeout = window.requestAnimationFrame( function () {
						fn.apply(context, args);
					});
				};
			}

			/**
			 * Gets scroll percentage when based on the middle of the viewport.
			 *
			 * @param {Element} element
			 *
			 * @returns int
			 */
			const getScrollPercentage = function( element ) {
				if ( ! element ) {
					return;
				}

				var windowHeight = window.innerHeight;
				var scrollTop    = window.scrollY;
				var elOffsetTop  = element.offsetTop;
				var elHeight     = element.offsetHeight;
				var distance     = scrollTop + windowHeight - elOffsetTop;
				var percentage   = Math.round( distance / ((windowHeight + elHeight) / 100) );

				// Restrict the range to between 0 and 100.
				return Math.min(100, Math.max(0, percentage));
			};

			// Set up scroll data.
			var tracker    = document.querySelector( 'main' );
			var scrollData = [];

			// Add the data.
			scrolls.forEach( function( popup ) {
				scrollData.push(
					{
						element: popup,
						distance: parseInt( popup.getAttribute( 'data-distance' ) ),
					}
				);
			});

			/**
			 * Adds scroll listener to trigger based on scroll percentage.
			 */
			window.addEventListener( 'scroll', debounce(() => {
				// Bail if scroll popups are empty.
				if ( ! scrollData.length ) {
					return false;
				}

				// Get scroll element.
				var scrolled = getScrollPercentage( tracker );

				//
				scrollData.forEach((data, index) => {
					if ( scrolled < data.distance ) {
						return;
					}

					// Remove popup from data so it doesn't fire again.
					scrollData.splice(index, 1);

					// Launch popup.
					openPopup( data.element );
				});
			}));
		}
	});
} )();
