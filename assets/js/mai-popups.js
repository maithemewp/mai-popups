/**
 * basicScroll 5.0.4.
 *
 * Changed the model from basiScroll to maiPopups.
 * Changed class names from basicLightbox to mai-popup.
 */
!function(e){if("object"==typeof exports&&"undefined"!=typeof module)module.exports=e();else if("function"==typeof define&&define.amd)define([],e);else{("undefined"!=typeof window?window:"undefined"!=typeof global?global:"undefined"!=typeof self?self:this).maiPopups=e()}}((function(){return function e(n,t,o){function r(c,u){if(!t[c]){if(!n[c]){var s="function"==typeof require&&require;if(!u&&s)return s(c,!0);if(i)return i(c,!0);var a=new Error("Cannot find module '"+c+"'");throw a.code="MODULE_NOT_FOUND",a}var l=t[c]={exports:{}};n[c][0].call(l.exports,(function(e){return r(n[c][1][e]||e)}),l,l.exports,e,n,t,o)}return t[c].exports}for(var i="function"==typeof require&&require,c=0;c<o.length;c++)r(o[c]);return r}({1:[function(e,n,t){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.create=t.visible=void 0;var o=function(e){var n=arguments.length>1&&void 0!==arguments[1]&&arguments[1],t=document.createElement("div");return t.innerHTML=e.trim(),!0===n?t.children:t.firstChild},r=function(e,n){var t=e.children;return 1===t.length&&t[0].tagName===n},i=function(e){return null!=(e=e||document.querySelector(".mai-popup"))&&!0===e.ownerDocument.body.contains(e)};t.visible=i;t.create=function(e,n){var t=function(e,n){var t=o('\n\t\t<div class="mai-popup '.concat(n.className,'">\n\t\t\t<div class="mai-popup__placeholder" role="dialog"></div>\n\t\t</div>\n\t')),i=t.querySelector(".mai-popup__placeholder");e.forEach((function(e){return i.appendChild(e)}));var c=r(i,"IMG"),u=r(i,"VIDEO"),s=r(i,"IFRAME");return!0===c&&t.classList.add("mai-popup--img"),!0===u&&t.classList.add("mai-popup--video"),!0===s&&t.classList.add("mai-popup--iframe"),t}(e=function(e){var n="string"==typeof e,t=e instanceof HTMLElement==1;if(!1===n&&!1===t)throw new Error("Content must be a DOM element/node or string");return!0===n?Array.from(o(e,!0)):"TEMPLATE"===e.tagName?[e.content.cloneNode(!0)]:Array.from(e.children)}(e),n=function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};if(null==(e=Object.assign({},e)).closable&&(e.closable=!0),null==e.className&&(e.className=""),null==e.onShow&&(e.onShow=function(){}),null==e.onClose&&(e.onClose=function(){}),"boolean"!=typeof e.closable)throw new Error("Property `closable` must be a boolean");if("string"!=typeof e.className)throw new Error("Property `className` must be a string");if("function"!=typeof e.onShow)throw new Error("Property `onShow` must be a function");if("function"!=typeof e.onClose)throw new Error("Property `onClose` must be a function");return e}(n)),c=function(e){return!1!==n.onClose(u)&&function(e,n){return e.classList.remove("mai-popup--visible"),setTimeout((function(){return!1===i(e)||e.parentElement.removeChild(e),n()}),410),!0}(t,(function(){if("function"==typeof e)return e(u)}))};!0===n.closable&&t.addEventListener("click",(function(e){e.target===t&&c()}));var u={element:function(){return t},visible:function(){return i(t)},show:function(e){return!1!==n.onShow(u)&&function(e,n){return document.body.appendChild(e),setTimeout((function(){requestAnimationFrame((function(){return e.classList.add("mai-popup--visible"),n()}))}),10),!0}(t,(function(){if("function"==typeof e)return e(u)}))},close:c};return u}},{}]},{},[1])(1)}));

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

		let open = [];

		const doPopUp = function( popup, event = false ) {
			if ( 'string' === typeof popup ) {
				popup = document.querySelector( popup );
			}

			// Bail if popup is empty.
			if ( ! ( popup && popup.innerHTML ) ) {
				return;
			}

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
						var closes = el.querySelectorAll( '.mai-popup__close' );

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

			// Show and set focus.
			instance.show(() => {
				var element = instance.element();
				var toFocus = element.querySelector( '.mai-popup__content' );

				if ( toFocus ) {
					toFocus.setAttribute( 'tabindex', 0 );
					toFocus.focus();
				}
			});
		}

		// Close last open popup with escape key.
		document.addEventListener( 'keyup', function( event ) {
			// Bail if none open.
			if ( ! open.length ) {
				return;
			}

			if ( event.key === "Escape" || event.key === "Esc" ) {
				var last = open.pop();
				last.close();
			}
		});

		/*************************
		 * Sets up timed popups. *
		 *************************/
		if ( timed.length ) {
			timed.forEach( function( popup ) {
				setTimeout( function() {
					doPopUp( popup );
				}, parseInt( popup.getAttribute( 'data-delay' ) ) );
			});
		}

		/*************************
		 * Sets up on load popups. *
		 *************************/
		 if ( loads.length ) {
			loads.forEach( function( popup ) {
				doPopUp( popup );
			});
		}

		/*****************************
		 * Sets up triggered popups. *
		 *****************************/
		 if ( triggers.length ) {
			triggers.forEach( function( trigger ) {
				trigger.addEventListener( 'click', function( event ) {
					event.preventDefault();
					doPopUp( event.target.getAttribute( 'href' ) );
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
					doPopUp( data.element );
				});
			}));
		}
	});
} )();
