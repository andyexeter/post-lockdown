import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { EDITOR, loginAs, setLockdown, wpCli } from './helpers.js';

/**
 * The lock/protect capability restrictions only apply to non-admins, so these
 * run as an editor against the Posts list screen (edit.php).
 */
test.describe( 'Post list restrictions (as editor)', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAs( page, EDITOR );
	} );

	test.afterEach( async ( { requestUtils } ) => {
		setLockdown();
		await requestUtils.deleteAllPosts();
	} );

	test( 'a locked post hides Edit/Trash and cannot be bulk-selected', async ( { page, requestUtils } ) => {
		const { id } = await requestUtils.createPost( {
			title: 'Locked row actions',
			status: 'publish',
		} );
		setLockdown( { locked: [ id ] } );

		await page.goto( '/wp-admin/edit.php' );

		const row = page.locator( `#post-${ id }` );
		await expect( row ).toBeVisible();
		await expect( row.locator( '.row-actions .edit' ) ).toHaveCount( 0 );
		await expect( row.locator( '.row-actions .trash' ) ).toHaveCount( 0 );
		// WP only renders the bulk-select checkbox for editable posts, so a
		// locked post cannot even be selected for a bulk trash.
		await expect( page.locator( `#cb-select-${ id }` ) ).toHaveCount( 0 );
	} );

	test( 'a protected post keeps Edit but hides its Trash row action', async ( { page, requestUtils } ) => {
		const { id } = await requestUtils.createPost( {
			title: 'Protected row actions',
			status: 'publish',
		} );
		setLockdown( { protectedPosts: [ id ] } );

		await page.goto( '/wp-admin/edit.php' );

		const row = page.locator( `#post-${ id }` );
		await expect( row ).toBeVisible();
		await expect( row.locator( '.row-actions .edit' ) ).toHaveCount( 1 );
		await expect( row.locator( '.row-actions .trash' ) ).toHaveCount( 0 );
	} );

	test( 'a protected post cannot be trashed via the bulk action', async ( { page, requestUtils } ) => {
		const { id } = await requestUtils.createPost( {
			title: 'Bulk trash me if you can',
			status: 'publish',
		} );
		setLockdown( { protectedPosts: [ id ] } );

		await page.goto( '/wp-admin/edit.php' );

		// Select the post and apply the native "Move to Trash" bulk action.
		await page.locator( `#cb-select-${ id }` ).check();
		await page.locator( '#bulk-action-selector-top' ).selectOption( 'trash' );
		await Promise.all( [
			page.waitForResponse(
				( response ) =>
					response.url().includes( 'action=trash' ) &&
					response.request().resourceType() === 'document'
			),
			page.locator( '#doaction' ).click(),
		] );

		// WordPress blocks the trash for a protected post, so it stays
		// published rather than being moved to the Trash.
		expect( wpCli( 'post', 'get', String( id ), '--field=post_status' ) ).toBe( 'publish' );
	} );
} );
