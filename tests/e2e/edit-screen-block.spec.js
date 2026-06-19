import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
	EDITOR,
	dismissWelcomeGuide,
	forceClassicEditor,
	loginAs,
	setLockdown,
} from './helpers.js';

/**
 * The same delete-button restriction, verified against the block editor (which
 * is the default — the classic-editor override is turned off here). In the
 * block editor "Move to trash" lives in the Settings sidebar's Post panel and
 * is gated on the delete capability.
 */
test.describe( 'Edit screen restrictions in the block editor (as editor)', () => {
	test.beforeEach( async ( { page } ) => {
		forceClassicEditor( false );
		await loginAs( page, EDITOR );
	} );

	test.afterEach( async ( { requestUtils } ) => {
		setLockdown();
		await requestUtils.deleteAllPosts();
	} );

	test( 'a protected post hides the Move to trash action', async ( { page, editor, requestUtils } ) => {
		const { id } = await requestUtils.createPost( {
			title: 'Block protected',
			status: 'publish',
		} );

		const moveToTrash = page.getByRole( 'button', { name: 'Move to trash' } );

		// Positive control: the editor can trash a normal post, so the action
		// is present in the block editor.
		await page.goto( `/wp-admin/post.php?post=${ id }&action=edit` );
		await dismissWelcomeGuide( page );
		await editor.openDocumentSettingsSidebar();
		await expect( moveToTrash ).toBeVisible();

		setLockdown( { protectedPosts: [ id ] } );

		await page.goto( `/wp-admin/post.php?post=${ id }&action=edit` );
		await dismissWelcomeGuide( page );
		await editor.openDocumentSettingsSidebar();
		await expect( moveToTrash ).toHaveCount( 0 );
	} );

	test( 'reverting a protected post to draft surfaces a notice', async ( { page, editor, requestUtils } ) => {
		const { id } = await requestUtils.createPost( {
			title: 'Block notice',
			status: 'publish',
		} );
		setLockdown( { protectedPosts: [ id ] } );

		await page.goto( `/wp-admin/post.php?post=${ id }&action=edit` );
		await dismissWelcomeGuide( page );
		await editor.openDocumentSettingsSidebar();

		// Change the status to Draft via the summary panel, then close the popover.
		await page.getByRole( 'button', { name: /Change status/ } ).click();
		await page.getByRole( 'radio', { name: 'Draft' } ).click();
		await page.keyboard.press( 'Escape' );

		await page
			.locator( '.editor-header__settings' )
			.getByRole( 'button', { name: 'Save', exact: true } )
			.click();

		// The plugin reverts the status, and the block editor shows the notice.
		await expect(
			page.getByText( 'This post is protected by Post Lockdown and must stay published.' )
		).toBeVisible();

		// The status is restored to Published.
		await expect(
			page.getByRole( 'button', { name: /Change status: Published/ } )
		).toBeVisible();
	} );
} );
