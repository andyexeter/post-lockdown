import { test, expect } from '@wordpress/e2e-test-utils-playwright';

const OPTIONS_PAGE = [ 'options-general.php', 'page=postlockdown' ];

/**
 * Clicks "Save Changes" and waits for the options page to actually reload.
 *
 * The form posts to options.php which 302-redirects back to the options page,
 * so we wait on that document response rather than the URL (which already
 * contains "page=postlockdown" and would resolve immediately, racing the save).
 */
async function saveChanges( page ) {
	await Promise.all( [
		page.waitForResponse(
			( response ) =>
				response.url().includes( 'options-general.php' ) &&
				response.request().method() === 'GET' &&
				response.status() === 200
		),
		page.getByRole( 'button', { name: 'Save Changes' } ).click(),
	] );
}

test.describe( 'Post Lockdown options page', () => {
	test.afterEach( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
	} );

	test( 'is registered under Settings and renders', async ( { admin, page } ) => {
		await admin.visitAdminPage( ...OPTIONS_PAGE );

		await expect(
			page.getByRole( 'heading', { name: 'Post Lockdown' } )
		).toBeVisible();

		// The two dual-list boxes (Locked + Protected) and the bulk-actions toggle.
		await expect( page.locator( '.pl-posts-container' ) ).toHaveCount( 2 );
		await expect( page.locator( '#bulk_actions_enabled' ) ).toBeVisible();
	} );

	test( 'locks a post via search and persists it after saving', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		const title = `E2E Lock Me ${ Date.now() }`;
		await requestUtils.createPost( { title, status: 'publish' } );

		await admin.visitAdminPage( ...OPTIONS_PAGE );

		const lockedBox = page.locator( '.pl-posts-container' ).first();

		// Type into the autocomplete search to query matching posts over AJAX.
		const search = lockedBox.locator( '.pl-autocomplete' );
		await search.click();
		await search.pressSequentially( title, { delay: 30 } );

		// The match shows up in the "available" column; click it to select.
		const availableItem = lockedBox
			.locator( '.pl-posts-available .pl-multiselect > li' )
			.filter( { hasText: title } );
		await availableItem.click();

		// It moves into the "selected" column.
		await expect(
			lockedBox
				.locator( '.pl-posts-selected .pl-multiselect > li' )
				.filter( { hasText: title } )
		).toBeVisible();

		await saveChanges( page );

		// After the reload the selection is rebuilt from the saved option,
		// proving it persisted to the database.
		await expect(
			page
				.locator( '.pl-posts-container' )
				.first()
				.locator( '.pl-posts-selected .pl-multiselect > li' )
				.filter( { hasText: title } )
		).toBeVisible();
	} );

	test( 'saving the bulk actions toggle persists', async ( { admin, page } ) => {
		await admin.visitAdminPage( ...OPTIONS_PAGE );

		const toggle = page.locator( '#bulk_actions_enabled' );

		// Flip whatever the current state is and confirm it survives the save.
		// Doing a single round-trip that flips the value keeps the test
		// self-resetting and order-independent, and exercises both directions
		// across runs. The checkbox is forced because the plugin's autocomplete
		// JS keeps repainting the page, defeating Playwright's stability check.
		const target = ! ( await toggle.isChecked() );
		await toggle.setChecked( target, { force: true } );

		await saveChanges( page );

		await expect( page.locator( '#bulk_actions_enabled' ) ).toBeChecked( {
			checked: target,
		} );
	} );
} );
