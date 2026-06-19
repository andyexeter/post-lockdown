import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { EDITOR, forceClassicEditor, loginAs, setLockdown } from './helpers.js';

/**
 * Edit-screen behaviour for a non-admin in the classic editor (forced via the
 * test mu-plugin), which is where the "Move to Trash" link and the
 * status-revert admin notice operate.
 */
test.describe( 'Edit screen restrictions (classic editor, as editor)', () => {
	test.beforeEach( async ( { page } ) => {
		forceClassicEditor( true );
		await loginAs( page, EDITOR );
	} );

	test.afterEach( async ( { requestUtils } ) => {
		setLockdown();
		forceClassicEditor( false );
		await requestUtils.deleteAllPosts();
	} );

	test( 'a protected post hides the Move to Trash link', async ( { page, requestUtils } ) => {
		const { id } = await requestUtils.createPost( {
			title: 'Protected edit screen',
			status: 'publish',
		} );

		// Sanity check: before protecting, the editor sees the trash link.
		await page.goto( `/wp-admin/post.php?post=${ id }&action=edit` );
		await expect( page.locator( '#delete-action .submitdelete' ) ).toHaveCount( 1 );

		setLockdown( { protectedPosts: [ id ] } );

		await page.goto( `/wp-admin/post.php?post=${ id }&action=edit` );
		await expect( page.locator( '#delete-action .submitdelete' ) ).toHaveCount( 0 );
	} );

	test( 'reverting a protected post to draft shows the admin notice', async ( { page, requestUtils } ) => {
		const { id } = await requestUtils.createPost( {
			title: 'Stay published please',
			status: 'publish',
		} );
		setLockdown( { protectedPosts: [ id ] } );

		await page.goto( `/wp-admin/post.php?post=${ id }&action=edit` );

		// Change the status to Draft via the classic publish box.
		await page.locator( '.edit-post-status' ).click();
		await page.locator( '#post_status' ).selectOption( 'draft' );
		await page.locator( '.save-post-status' ).click();

		await Promise.all( [
			page.waitForResponse(
				( response ) =>
					response.url().includes( 'post.php' ) &&
					response.request().method() === 'GET' &&
					response.status() === 200
			),
			page.locator( '#publish' ).click(),
		] );

		// The plugin reverts the status and surfaces its notice.
		await expect(
			page.getByText( 'This post is protected by Post Lockdown and must stay published.' )
		).toBeVisible();
		await expect( page.locator( '#post-status-display' ) ).toContainText( 'Published' );
	} );
} );
