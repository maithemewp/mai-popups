import '../css/mai-popups.css';

const isModal   = ( p ) => 'true' === p.dataset.modal;
const hasCookie = ( name ) => document.cookie.split( '; ' ).some( ( c ) => c.startsWith( `${ name }=` ) );

function labelDialog( popup ) {
	if ( popup.getAttribute( 'aria-labelledby' ) ) { return; }
	const heading = popup.querySelector( 'h1, h2, h3, h4, h5, h6' );
	if ( ! heading ) { return; }
	if ( ! heading.id ) { heading.id = `${ popup.id }-title`; }
	popup.setAttribute( 'aria-labelledby', heading.id );
}

export function cookieExpiry( unixSeconds ) {
	return new Date( parseInt( unixSeconds, 10 ) * 1000 );
}

function setCookie( popup ) {
	const expire = popup.dataset.expire;
	if ( ! expire ) { return; }
	const date = cookieExpiry( expire ); // server sends a unix timestamp (bug #2 fix)
	const secure = 'https:' === window.location.protocol ? '; Secure' : '';
	document.cookie = `${ popup.id }=1; expires=${ date.toUTCString() }; path=/; SameSite=Lax${ secure }`;
}

function stopMedia( popup ) {
	popup.querySelectorAll( 'iframe' ).forEach( ( v ) => {
		// eslint-disable-next-line no-self-assign -- reassigning src reloads the iframe to stop playback
		v.src = v.src;
	} );
	popup.querySelectorAll( 'video' ).forEach( ( v ) => v.pause() );
}

// Non-modal popups deliberately don't take focus, so assistive tech wouldn't
// otherwise know one appeared. Announce the popup's accessible name through a
// shared polite live region — created once, up front, so it is already being
// monitored before its text changes (a region added and filled in the same tick
// is unreliably announced).
let srAnnouncer = null;
function ensureAnnouncer() {
	if ( srAnnouncer ) { return srAnnouncer; }
	srAnnouncer = document.createElement( 'div' );
	srAnnouncer.setAttribute( 'aria-live', 'polite' );
	srAnnouncer.setAttribute( 'aria-atomic', 'true' );
	Object.assign( srAnnouncer.style, {
		position: 'absolute', width: '1px', height: '1px',
		margin: '-1px', padding: '0', border: '0',
		overflow: 'hidden', clip: 'rect(0 0 0 0)', whiteSpace: 'nowrap',
	} );
	document.body.appendChild( srAnnouncer );
	return srAnnouncer;
}

function announce( popup ) {
	const region  = ensureAnnouncer();
	const labelId = popup.getAttribute( 'aria-labelledby' );
	const labelEl = labelId ? document.getElementById( labelId ) : null;
	const text    = ( ( labelEl ? labelEl.textContent : popup.getAttribute( 'aria-label' ) ) || '' ).trim();
	if ( ! text ) { return; }
	region.textContent = '';                  // clear first so reopening the same popup re-announces
	setTimeout( () => { region.textContent = text; }, 50 );
}

function init() {
	const popups   = document.querySelectorAll( '.mai-popup' );
	const triggers = document.querySelectorAll( '[href^="#mai-popup-"]' );
	if ( ! popups.length && ! triggers.length ) { return; }

	// Stand up the live region now (not on first open) so screen readers are
	// already watching it when a non-modal popup announces itself.
	if ( [ ...popups ].some( ( p ) => ! isModal( p ) ) ) { ensureAnnouncer(); }

	const openStack  = new Set();                 // bug #1 fix: real stack, not splice()
	const triggerFor = new WeakMap();             // popup -> element to refocus on close

	function open( popup, trigger = null ) {
		if ( typeof popup === 'string' ) { popup = document.querySelector( popup ); }
		if ( ! popup || popup.open ) { return; }
		if ( ! trigger && 'true' === popup.dataset.cookie && hasCookie( popup.id ) ) { return; }

		if ( trigger ) { triggerFor.set( popup, trigger ); }

		if ( isModal( popup ) ) {
			popup.showModal();                    // native focus trap + inert + ::backdrop + Esc
			labelDialog( popup );
			popup.focus();                        // a11y: focus the titled dialog, not the Close button
		} else {
			// A non-modal popup (bar / slide-in / corner) must NOT pull focus from the
			// page. dialog.show() runs the focusing steps and lands focus on the first
			// focusable descendant (the Close button) — and there is no flag to suppress
			// it. Setting the `open` attribute shows the dialog non-modally with NO focus
			// movement and no focus events at all; close()/Esc/cleanup are unaffected.
			// Focus stays exactly where the reader had it, the popup is reachable by Tab,
			// and assistive tech is told it appeared via the live region instead (#9).
			popup.open = true;                    // non-modal: no trap, no focus steal
			labelDialog( popup );
			announce( popup );
		}
		openStack.add( popup );
	}

	// Animated close REQUEST. Sets [closing], waits for whichever the CSS uses —
	// a CSS transition OR a keyframe animation — then calls native close(). A
	// timeout fallback guarantees the popup closes even if no animation/event
	// fires (bug #3: never hang). Actual cleanup is in onClose() on 'close'.
	function requestClose( popup ) {
		if ( ! popup.open ) { return; }
		popup.setAttribute( 'closing', '' );
		const cs  = getComputedStyle( popup );
		const dur = Math.max(
			parseFloat( cs.transitionDuration ) || 0,
			parseFloat( cs.animationDuration ) || 0
		);
		if ( ! dur ) {
			popup.removeAttribute( 'closing' );
			popup.close();
			return;
		}
		let timer;
		const done = ( e ) => {
			if ( e && e.target !== popup ) { return; } // ignore bubbling child media/anim events
			popup.removeEventListener( 'transitionend', done );
			popup.removeEventListener( 'animationend', done );
			clearTimeout( timer );
			popup.close();
		};
		popup.addEventListener( 'transitionend', done );
		popup.addEventListener( 'animationend', done );
		timer = setTimeout( done, ( dur * 1000 ) + 100 );
	}

	// Cleanup that must run HOWEVER the dialog closed — programmatic close() OR
	// native modal Esc (which fires 'close' with popup.open already false).
	function onClose( popup ) {
		const trigger = triggerFor.get( popup );
		// Only auto-triggered opens (no trigger element) write the suppression cookie;
		// a manual link open shouldn't disable the later auto-show.
		if ( 'true' === popup.dataset.cookie && ! trigger ) { setCookie( popup ); }
		stopMedia( popup );
		popup.removeAttribute( 'closing' );
		openStack.delete( popup );
		if ( trigger && typeof trigger.focus === 'function' ) { trigger.focus(); } // bug #4 fix: restore focus
		triggerFor.delete( popup );
	}

	// Bind affordances ONCE per popup (survives reopen).
	popups.forEach( ( popup ) => {
		// Native 'close' (modal Esc, programmatic close, dialog form submit) → single cleanup path.
		popup.addEventListener( 'close', () => onClose( popup ) );

		// Close buttons (animated request).
		popup.querySelectorAll( '.mai-popup__close, .mai-popup-close, .mai-popup-close a' )
			.forEach( ( el ) => el.addEventListener( 'click', ( e ) => { e.preventDefault(); requestClose( popup ); } ) );

		// Light-dismiss: click on the ::backdrop (the dialog element itself) for modals, unless disabled.
		if ( isModal( popup ) && 'false' !== popup.dataset.close ) {
			popup.addEventListener( 'click', ( e ) => { if ( e.target === popup ) { requestClose( popup ); } } );
		}
	} );

	// Esc closes only the topmost NON-modal popup (modal Esc fires native 'close' above).
	document.addEventListener( 'keydown', ( e ) => {
		if ( 'Escape' !== e.key ) { return; }
		const stack = [ ...openStack ].filter( ( p ) => p.open && ! isModal( p ) );
		const top = stack[ stack.length - 1 ];
		if ( top ) { requestClose( top ); }
	} );

	// Manual-link triggers.
	triggers.forEach( ( t ) => t.addEventListener( 'click', ( e ) => {
		e.preventDefault();
		open( t.getAttribute( 'href' ), t );
	} ) );

	// Auto triggers.
	popups.forEach( ( popup ) => {
		switch ( popup.dataset.type ) {
			case 'load': open( popup ); break;
			case 'time': setTimeout( () => open( popup ), parseInt( popup.dataset.delay, 10 ) || 0 ); break;
		}
	} );

	// Scroll-distance triggers.
	const scrollers = [ ...popups ].filter( ( p ) => 'scroll' === p.dataset.type );
	if ( scrollers.length ) { initScroll( scrollers, open ); }
}

// IntersectionObserver scroll trigger. A 1px sentinel is placed at the document
// position that enters the viewport once the visitor has scrolled `distance`% through
// the scrollable page. Measuring against documentElement.scrollHeight (not a
// <main>-relative offset) is correct without a <main> and on positioned layouts; a
// short/unscrollable page fires immediately (treated as already past the threshold).
function initScroll( popups, open ) {
	popups.forEach( ( popup ) => {
		const distance = Math.min( 100, Math.max( 0, parseInt( popup.dataset.distance, 10 ) || 0 ) );

		const sentinel = document.createElement( 'div' );
		sentinel.setAttribute( 'aria-hidden', 'true' );
		Object.assign( sentinel.style, { position: 'absolute', left: '0', width: '1px', height: '1px', pointerEvents: 'none' } );

		const place = () => {
			const scrollable = Math.max( 0, document.documentElement.scrollHeight - window.innerHeight );
			const top = Math.round( ( distance / 100 ) * scrollable + window.innerHeight );
			sentinel.style.top = `${ top }px`;
		};
		place();
		document.body.append( sentinel );

		let io, ro;
		io = new IntersectionObserver( ( entries ) => {
			if ( entries.some( ( e ) => e.isIntersecting ) ) {
				io.disconnect();
				ro.disconnect();
				sentinel.remove();
				open( popup );
			}
		} );
		// Recompute on layout/content/viewport changes that move the threshold.
		ro = new ResizeObserver( place );
		io.observe( sentinel );
		ro.observe( document.body );
	} );
}

if ( document.readyState !== 'loading' ) { init(); }
else { document.addEventListener( 'DOMContentLoaded', init ); }
