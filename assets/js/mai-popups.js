( function() {
	/**
	 * Wait until page is loaded.
	 */
	window.addEventListener( 'load', function( event ) {
		const timed    = document.querySelectorAll( '.mai-popup[data-type="time"]' );
		const scrolls  = document.querySelectorAll( '.mai-popup[data-type="scroll"]' );
		const loads    = document.querySelectorAll( '.mai-popup[data-type="load"]' );
		const triggers = document.querySelectorAll( '[href^="#mai-popup-"]' );

		// Bail if no popups.
		if ( ! ( timed.length || scrolls.length || loads.length || triggers.length ) ) {
			return;
		}

		let open = [];

		const openPopup = function( popup, event = false ) {
			if ( 'string' === typeof popup ) {
				popup = document.querySelector( popup );
			}

			// Bail if no popup.
			if ( ! popup ) {
				return;
			}

<<<<<<< HEAD
			const current    = document.activeElement;
			const style      = popup.getAttribute( 'style' );
			const animate    = popup.getAttribute( 'data-animate' );
			const horizontal = popup.getAttribute( 'data-horizontal' );
			const vertical   = popup.getAttribute( 'data-vertical' );
			const width      = popup.getAttribute( 'data-width' );
			const instance   = maiPopups.create( popup,
				{
					onShow: (instance) => {
						// Set as open.
						open.push( instance );

						// Set vars.
						var el     = instance.element();
						var closes = el.querySelectorAll( '.mai-popup__close', '.mai-popup-close' ); // Use mai-popup-close class on any link or button in your popup to add another close trigger.

						// Set attributes from our wrapper.
						el.setAttribute( 'style', style );
						el.setAttribute( 'data-animate', animate );
						el.setAttribute( 'data-horizontal', horizontal );
						el.setAttribute( 'data-vertical', vertical );

						if ( width ) {
							el.setAttribute( 'data-width', width );
						}

						// Close when hitting close icon or opening another popup.
						closes.forEach( function( close ) {
							close.addEventListener( 'click', function( event ) {
								instance.close();
							});
						});
					},
					onClose: (instance) => {
						// Remove from open popups.
						open = open.splice( open.indexOf( instance ), 1 );

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

						// Moves popup content back to the original element and returns focus.
						var el = instance.element();
						popup.innerHTML = el.querySelector( '.mai-popup__placeholder' ).innerHTML;

						// Focus on original element. Scroll popups may not have active element yet.
						if ( current ) {
							current.focus();
						}
					}
				}
			);
=======
			// Set as open.
			open.push( popup );
>>>>>>> dialog

			// Check if centered modal.
			const modal = 'center' === popup.getAttribute( 'data-vertical' ) && 'center' === popup.getAttribute( 'data-horizontal' );

			// Maybe add overlay.
			if ( modal ) {
				var overlay = document.createElement( 'div' );
				overlay.setAttribute( 'class', 'mai-popup-overlay' );
				popup.before( overlay );
			}

			// Show popup.
			popup.show();

			// Close when hitting close icon.
			popup.querySelectorAll( '.mai-popup__close', '.mai-popup-close' ).forEach( ( close ) => {
				close.addEventListener( 'click', ( event ) => {
					closePopup( popup );
				});
			});

			// Adds event listener for close event.
			popup.addEventListener( 'close', ( event ) => {
				closePopup( popup );
			}, { once: true } );

			// Adds event listener to close modal when clicking outside.
			if ( modal ) {
				overlay.addEventListener( 'click', ( event ) => {
					closePopup( popup );
				}, { once: true } );
			}
		}

		const closePopup = function( popup ) {
			/**
			 * Prevent infinite loops.
			 * This function is called via clicks when it's modal().
			 */
			if ( ! popup.open ) {
				return;
			}

			// Remove from open popups.
			open = open.splice( open.indexOf( popup ), 1 );

			// Get data.
			const seconds  = popup.getAttribute( 'data-expire' );
			const previous = popup.previousElementSibling;
			const overlay  = previous && previous.classList.contains( 'mai-popup-overlay' ) ? previous : false;

			// If expiring.
			if ( seconds ) {
				// Build cookie.
				const expire = new Date();
				expire.setSeconds( expire.getSeconds() + parseInt(seconds) );
				const name   = popup.getAttribute( 'id' );
				const utc    = expire.toUTCString();
				const cookie = name + '=1;expires=' + utc + ';path=/;SameSite=Strict;';

				// Set cookie.
				document.cookie = cookie;
			}

			// Add hidden class, for CSS animation.
			popup.setAttribute( 'closing', '' );

			if ( overlay ) {
				overlay.setAttribute( 'closing', '' );
			}

			// Close popup after animation is done.
			popup.addEventListener( 'animationend', () => {
				popup.removeAttribute( 'closing' );
				popup.close();

				// Remove overlay.
				if ( overlay ) {
					overlay.remove();
				}
			}, { once: true } );
		}

		// Close last open popup with escape key.
		document.addEventListener( 'keyup', ( event ) => {
			// Bail if none open.
			if ( ! open.length ) {
				return;
			}

			if ( event.key === "Escape" || event.key === "Esc" ) {
				closePopup( open.pop() );
			}
		});

		/*************************
		 * Sets up timed popups. *
		 *************************/
		if ( timed.length ) {
			timed.forEach( ( popup ) => {
				setTimeout( () => {
					openPopup( popup );
				}, parseInt( popup.getAttribute( 'data-delay' ) ) );
			});
		}

		/*************************
		 * Sets up on load popups. *
		 *************************/
		 if ( loads.length ) {
			loads.forEach( ( popup ) => {
				openPopup( popup );
			});
		}

		/*****************************
		 * Sets up triggered popups. *
		 *****************************/
		 if ( triggers.length ) {
			triggers.forEach( ( trigger ) => {
				trigger.addEventListener( 'click', ( event ) => {
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
			const debounce = ( fn ) => {
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
					timeout = window.requestAnimationFrame( () => {
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
			const getScrollPercentage = ( element ) => {
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
			scrolls.forEach( ( popup ) => {
				scrollData.push(
					{
						distance: parseInt( popup.getAttribute( 'data-distance' ) ),
						element: popup,
					}
				);
			});

			/**
			 * Adds scroll listener to trigger based on scroll percentage.
			 */
			window.addEventListener( 'scroll', debounce( () => {
				// Bail if scroll popups are empty.
				if ( ! scrollData.length ) {
					return false;
				}

				// Get scroll element.
				var scrolled = getScrollPercentage( tracker );

				// Check scroll distance of each popup.
				scrollData.forEach( (data, index) => {
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
