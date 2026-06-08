/**
 * Mai Popups — editor: give a duplicated popup block a unique anchor id (#5).
 *
 * The anchor id (ACF "Link" field, key mai_popup_link / name "id") is the trigger
 * target (#mai-popup-xxxx). When a popup block is duplicated, ACF copies the saved
 * value, so the duplicate collides with its source. The server can't tell a fresh
 * duplicate from a re-render, so we resolve collisions here, in the editor.
 *
 * Fail-safe by design: if the ACF JS API isn't present or anything throws, this
 * does nothing. It must never break the editor.
 */
import { select } from '@wordpress/data';
import domReady from '@wordpress/dom-ready';

const FIELD_KEY = 'mai_popup_link';
const PREFIX = '#mai-popup-';

function newId() {
	return PREFIX + Date.now().toString( 36 ) + Math.random().toString( 36 ).slice( 2, 8 );
}

// Map anchor-id value -> [clientId, ...] in document order, across all popup blocks.
function popupIdMap() {
	const map = {};
	try {
		const walk = ( list ) => {
			list.forEach( ( b ) => {
				if ( b.name === 'acf/mai-popup' ) {
					const id = b.attributes && b.attributes.data ? b.attributes.data.id : undefined;
					if ( id ) {
						( map[ id ] = map[ id ] || [] ).push( b.clientId );
					}
				}
				if ( b.innerBlocks && b.innerBlocks.length ) {
					walk( b.innerBlocks );
				}
			} );
		};
		walk( select( 'core/block-editor' ).getBlocks() );
	} catch ( e ) {}
	return map;
}

function blockClientIdOf( field ) {
	try {
		const el = field && field.$el && field.$el[ 0 ] ? field.$el[ 0 ] : null;
		const wrap = el ? el.closest( '[data-block]' ) : null;
		return wrap ? wrap.getAttribute( 'data-block' ) : null;
	} catch ( e ) {
		return null;
	}
}

function maybeFixField( field ) {
	try {
		const value = field.val();
		if ( ! value ) { return; }            // empty -> server prepare filter generates it
		const map = popupIdMap();
		const owners = map[ value ] || [];
		if ( owners.length < 2 ) { return; }  // no collision
		const myClientId = blockClientIdOf( field );
		// Keep the first owner in document order; only the duplicates regenerate.
		// If we can't identify this block, do nothing (never risk changing the original).
		if ( ! myClientId || owners[ 0 ] === myClientId ) { return; }
		let fresh = newId();
		while ( map[ fresh ] ) { fresh = newId(); }
		field.val( fresh );
	} catch ( e ) {}
}

domReady( () => {
	if ( ! window.acf || typeof window.acf.addAction !== 'function' ) { return; }
	window.acf.addAction( 'ready_field/key=' + FIELD_KEY, maybeFixField );
	window.acf.addAction( 'append_field/key=' + FIELD_KEY, maybeFixField );
} );
