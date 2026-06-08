import { cookieExpiry } from '../../src/js/mai-popups';

test( 'cookieExpiry converts a unix timestamp to a valid Date', () => {
	const d = cookieExpiry( '1893456000' );
	expect( d.getTime() ).toBe( 1893456000 * 1000 );
	expect( d.toUTCString() ).not.toBe( 'Invalid Date' );
} );
