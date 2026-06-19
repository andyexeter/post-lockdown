import { execFileSync } from 'node:child_process';

export const EDITOR = { username: 'pleditor', password: 'editor-password-123' };

/**
 * Runs a WP-CLI command inside the wp-env "cli" container and returns trimmed
 * stdout. wp-env prints its own progress lines to stderr, so stdout is the raw
 * command output.
 */
export function wpCli( ...args ) {
	return execFileSync(
		'node_modules/.bin/wp-env',
		[ 'run', 'cli', 'wp', ...args ],
		{ encoding: 'utf8' }
	).trim();
}

/**
 * Replaces the Post Lockdown option with an exact known state. Passing nothing
 * clears all locked/protected posts and disables bulk actions.
 *
 * The option stores IDs keyed by their own value (id => id) because the plugin
 * looks them up with isset( $ids[ $post_id ] ).
 */
export function setLockdown( { locked = [], protectedPosts = [], bulkActions = false } = {} ) {
	const toMap = ( ids ) =>
		Object.fromEntries( ids.map( ( id ) => [ id, id ] ) );

	const option = {
		locked_post_ids: toMap( locked ),
		protected_post_ids: toMap( protectedPosts ),
		bulk_actions_enabled: bulkActions,
	};

	wpCli( 'option', 'update', 'postlockdown', JSON.stringify( option ), '--format=json' );
}

/** Reports the plugin's view of a post: "locked", "protected" or "" (neither). */
export function lockdownStatus( postId ) {
	return wpCli( 'postlockdown', 'status', String( postId ) );
}

/**
 * Toggles the test mu-plugin's classic-editor override. The classic editor is
 * where the "Move to Trash" link and the status-revert admin notice operate;
 * with it off, posts open in the block editor.
 */
export function forceClassicEditor( enabled = true ) {
	wpCli( 'option', 'update', 'pl_test_force_classic', enabled ? '1' : '0' );
}

/** Ensures the shared non-admin editor account exists (idempotent). */
export function ensureEditor() {
	const logins = wpCli( 'user', 'list', '--field=user_login' )
		.split( '\n' )
		.map( ( line ) => line.trim() );

	if ( ! logins.includes( EDITOR.username ) ) {
		wpCli(
			'user',
			'create',
			EDITOR.username,
			`${ EDITOR.username }@example.com`,
			'--role=editor',
			`--user_pass=${ EDITOR.password }`
		);
	}
}

/**
 * Closes the block editor's "Welcome to the editor" guide if it is showing, so
 * it doesn't intercept interactions. The choice is remembered per user.
 */
export async function dismissWelcomeGuide( page ) {
	const guide = page.getByRole( 'dialog' ).filter( { hasText: 'Welcome to the' } );

	if ( await guide.isVisible().catch( () => false ) ) {
		await guide.getByRole( 'button', { name: 'Close' } ).click();
		await guide.waitFor( { state: 'hidden' } );
	}
}

/**
 * Logs the browser session in as the given user via wp-login.php, replacing the
 * default administrator session loaded from storage state.
 */
export async function loginAs( page, user ) {
	await page.context().clearCookies();
	await page.goto( '/wp-login.php' );
	await page.locator( '#user_login' ).fill( user.username );
	await page.locator( '#user_pass' ).fill( user.password );
	await Promise.all( [
		page.waitForURL( '**/wp-admin/**' ),
		page.locator( '#wp-submit' ).click(),
	] );
}
