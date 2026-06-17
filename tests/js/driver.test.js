/**
 * Driver tests for src/js/mai-popups.js.
 *
 * jsdom does NOT implement HTMLDialogElement.show()/showModal()/close(), so we
 * polyfill them on the prototype: show/showModal set `open`, close clears it and
 * dispatches the native 'close' event. The module auto-runs init() on import via
 * its `document.readyState` check (jsdom defaults readyState to 'complete'), so
 * each test sets up the DOM first, then requires the module fresh with
 * jest.resetModules() so init() sees that DOM.
 */

// CSS import inside the module — stub so requiring it doesn't blow up.
jest.mock( '../../src/css/mai-popups.css', () => ( {} ), { virtual: true } );

// --- dialog polyfill (jsdom lacks the dialog methods) -----------------------
// Per-element own-property spies (NOT shared prototype methods) so each popup's
// show()/showModal() calls are independently asserted.
function polyfillDialog( el ) {
	el.showModal = jest.fn( function () {
		el.open = true;
		el.setAttribute( 'open', '' );
	} );
	el.show = jest.fn( function () {
		el.open = true;
		el.setAttribute( 'open', '' );
	} );
	el.close = jest.fn( function () {
		el.open = false;
		el.removeAttribute( 'open' );
		el.dispatchEvent( new window.Event( 'close' ) );
	} );
}

/**
 * Polyfills every popup in the DOM, then loads the module fresh so its
 * on-import init() runs against the current DOM.
 */
function loadModule() {
	document.querySelectorAll( '.mai-popup' ).forEach( polyfillDialog );
	jest.resetModules();
	require( '../../src/js/mai-popups' );
}

beforeEach( () => {
	document.body.innerHTML = '';
	// Clear cookies between tests.
	document.cookie.split( ';' ).forEach( ( c ) => {
		const name = c.split( '=' )[ 0 ].trim();
		if ( name ) {
			document.cookie = `${ name }=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
		}
	} );
} );

test( 'cookie suppression: an auto-load cookie-popup with its cookie set does NOT open', () => {
	document.cookie = 'sup=1';
	document.body.innerHTML =
		'<dialog class="mai-popup" id="sup" data-type="load" data-cookie="true"></dialog>';

	loadModule();

	const popup = document.getElementById( 'sup' );
	expect( popup.open ).toBeFalsy();
	expect( popup.showModal ).not.toHaveBeenCalled();
	expect( popup.show ).not.toHaveBeenCalled();
} );

test( 'cookie suppression: with NO cookie set, the same auto-load popup opens', () => {
	document.body.innerHTML =
		'<dialog class="mai-popup" id="nocookie" data-type="load" data-cookie="true"></dialog>';

	loadModule();

	const popup = document.getElementById( 'nocookie' );
	expect( popup.open ).toBeTruthy();
} );

test( 'modal vs non-modal: modal uses showModal(); non-modal opens via the open attribute (never show())', () => {
	document.body.innerHTML =
		'<dialog class="mai-popup" id="modal-one" data-type="load" data-modal="true"></dialog>' +
		'<dialog class="mai-popup" id="bar-one" data-type="load"></dialog>';

	loadModule();

	const modal = document.getElementById( 'modal-one' );
	const bar   = document.getElementById( 'bar-one' );

	expect( modal.showModal ).toHaveBeenCalled();
	expect( modal.show ).not.toHaveBeenCalled();

	// The non-modal (positioned) popup is shown by setting the open attribute, NOT
	// via show() — show()'s focusing steps would steal focus from the page.
	expect( bar.show ).not.toHaveBeenCalled();
	expect( bar.showModal ).not.toHaveBeenCalled();
	expect( bar.open ).toBe( true );
} );

test( 'manual link opens a cookie-suppressed popup despite the cookie (bypasses suppression)', () => {
	document.cookie = 'mai-popup-x=1';
	document.body.innerHTML =
		'<a id="trigger" href="#mai-popup-x">Open</a>' +
		'<dialog class="mai-popup" id="mai-popup-x" data-type="manual" data-cookie="true" data-modal="true"></dialog>';

	loadModule();

	const popup = document.getElementById( 'mai-popup-x' );
	// Manual trigger: not auto-opened on load.
	expect( popup.open ).toBeFalsy();

	// Clicking the link opens it even though the suppression cookie is set.
	document.getElementById( 'trigger' ).click();
	expect( popup.open ).toBeTruthy();
	expect( popup.showModal ).toHaveBeenCalled();
} );

test( 'non-modal popup does NOT move focus: opened via the open attribute, the caret stays put', () => {
	document.body.innerHTML =
		'<input id="field" />' +
		'<dialog class="mai-popup" id="bar" data-type="load">' +
			'<button class="mai-popup__close"></button>' +
		'</dialog>';

	const field = document.getElementById( 'field' );
	field.focus();
	expect( document.activeElement ).toBe( field );

	loadModule();

	const bar = document.getElementById( 'bar' );
	expect( bar.open ).toBeTruthy();
	// Opened via the open attribute (not show()), so focus never entered the popup.
	expect( bar.contains( document.activeElement ) ).toBe( false );
	expect( document.activeElement ).toBe( field );
} );

test( 'non-modal popup with nothing focused leaves focus untouched (does not grab the Close button)', () => {
	document.body.innerHTML =
		'<dialog class="mai-popup" id="bar2" data-type="load">' +
			'<button class="mai-popup__close"></button>' +
		'</dialog>';

	loadModule();

	const bar = document.getElementById( 'bar2' );
	expect( bar.open ).toBeTruthy();
	// Nothing was focused and the popup didn't take focus, so it isn't on the Close button.
	expect( bar.contains( document.activeElement ) ).toBe( false );
} );

test( 'non-modal popup announces its accessible name through a polite live region', () => {
	jest.useFakeTimers();
	document.body.innerHTML =
		'<dialog class="mai-popup" id="bar3" data-type="load" aria-label="Popup">' +
			'<h2>Join the newsletter</h2>' +
			'<button class="mai-popup__close"></button>' +
		'</dialog>';

	loadModule();

	const region = document.querySelector( '[aria-live="polite"]' );
	expect( region ).toBeTruthy();
	expect( region.getAttribute( 'aria-atomic' ) ).toBe( 'true' );

	jest.runOnlyPendingTimers();   // the announcement is set on a short timer so the region re-fires
	// labelDialog() points aria-labelledby at the first heading, so that's what's announced.
	expect( region.textContent ).toBe( 'Join the newsletter' );
	jest.useRealTimers();
} );

test( 'modal popup DOES take focus on open (focus moves into the dialog, not back to the page)', () => {
	document.body.innerHTML =
		'<input id="field" />' +
		'<dialog class="mai-popup" id="m" data-type="load" data-modal="true" tabindex="-1">' +
			'<button class="mai-popup__close"></button>' +
		'</dialog>';

	const field = document.getElementById( 'field' );
	field.focus();

	loadModule();

	const m = document.getElementById( 'm' );
	expect( m.open ).toBeTruthy();
	// Modal is blocking: the driver focuses the dialog itself; focus is NOT on the page.
	expect( document.activeElement ).toBe( m );
	expect( document.activeElement ).not.toBe( field );
} );
