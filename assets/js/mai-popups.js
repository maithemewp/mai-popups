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

			// Check cookie.
			if ( 'true' === popup.dataset.cookie ) {
				var cookie  = popup.getAttribute( 'id' ) + '=1';
				var cookies = document.cookie.split( '; ' );

				if ( cookie && cookies && cookies.includes( cookie ) ) {
					return;
				}
			}

			// Set as open.
			open.push( popup );

			// Check if centered modal.
			const modal = 'center' === popup.dataset.vertical && 'center' === popup.dataset.horizontal;

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
			const seconds  = popup.dataset.expire;
			const previous = popup.previousElementSibling;
			const overlay  = previous && previous.classList.contains( 'mai-popup-overlay' ) ? previous : false;

			// If expiring.
			if ( seconds ) {
				// Build cookie.
				const expire = new Date();
				expire.setTime( parseInt( expire ) );
				const cookie = popup.id + '=1; expires=' + expire.toUTCString() + '; path=/; SameSite=Lax;';

				// Set cookie.
				document.cookie = cookie.trim();
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
				}, parseInt( popup.dataset.delay ) );
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
					openPopup( event.currentTarget.getAttribute( 'href' ) );
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
						distance: parseInt( popup.dataset.distance ),
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
