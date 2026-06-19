#!/usr/bin/env node
/**
 * Writes a .wp-env.override.json that pins the WordPress core version wp-env
 * boots, so the E2E suite can run against a specific version / nightly.
 *
 *   node bin/set-wp-env-core.mjs            # latest stable (default)
 *   node bin/set-wp-env-core.mjs nightly    # current trunk
 *   node bin/set-wp-env-core.mjs 6.7        # a specific release
 */
import { writeFileSync } from 'node:fs';

const version = ( process.argv[ 2 ] || 'latest' ).trim();

let core = null; // null => latest stable

if ( version === 'nightly' || version === 'trunk' ) {
	core = 'https://wordpress.org/nightly-builds/wordpress-latest.zip';
} else if ( version !== 'latest' && version !== '' ) {
	core = `https://wordpress.org/wordpress-${ version }.zip`;
}

writeFileSync( '.wp-env.override.json', JSON.stringify( { core }, null, 2 ) + '\n' );

console.log( `wp-env core set to: ${ core ?? 'latest stable' }` );