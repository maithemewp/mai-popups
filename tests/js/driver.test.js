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

test( 'modal vs non-modal: data-modal=true uses showModal(), positioned uses show()', () => {
	document.body.innerHTML =
		'<dialog class="mai-popup" id="modal-one" data-type="load" data-modal="true"></dialog>' +
		'<dialog class="mai-popup" id="bar-one" data-type="load"></dialog>';

	loadModule();

	const modal = document.getElementById( 'modal-one' );
	const bar   = document.getElementById( 'bar-one' );

	expect( modal.showModal ).toHaveBeenCalled();
	expect( modal.show ).not.toHaveBeenCalled();

	// The non-modal (positioned) popup chose show(), never showModal().
	expect( bar.show ).toHaveBeenCalled();
	expect( bar.showModal ).not.toHaveBeenCalled();
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
