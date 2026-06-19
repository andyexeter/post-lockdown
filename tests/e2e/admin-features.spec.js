import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { lockdownStatus, setLockdown } from './helpers.js';

/**
 * Features that run for administrators: the plugin's own bulk actions and the
 * status column. These use the default admin session from storage state.
 */
test.describe( 'Admin features', () => {
	test.afterEach( async ( { requestUtils } ) => {
		setLockdown();
		await requestUtils.deleteAllPosts();
	} );

	test( 'the Lock bulk action locks the selected post', async ( { page, requestUtils } ) => {
		const { id } = await requestUtils.createPost( {
			title: 'Lock me in bulk',
			status: 'publish',
		} );
		setLockdown( { bulkActions: true } );

		await page.goto( '/wp-admin/edit.php' );
		await page.locator( `#cb-select-${ id }` ).check();
		await page.locator( '#bulk-action-selector-top' ).selectOption( 'postlockdown-lock' );
		await Promise.all( [
			page.waitForResponse(
				( response ) =>
					response.url().includes( 'edit.php' ) &&
					response.request().method() === 'GET' &&
					response.status() === 200
			),
			page.locator( '#doaction' ).click(),
		] );

		expect( lockdownStatus( id ) ).toBe( 'locked' );
	} );

	test( 'the Protect bulk action protects the selected post', async ( { page, requestUtils } ) => {
		const { id } = await requestUtils.createPost( {
			title: 'Protect me in bulk',
			status: 'publish',
		} );
		setLockdown( { bulkActions: true } );

		await page.goto( '/wp-admin/edit.php' );
		await page.locator( `#cb-select-${ id }` ).check();
		await page.locator( '#bulk-action-selector-top' ).selectOption( 'postlockdown-protect' );
		await Promise.all( [
			page.waitForResponse(
				( response ) =>
					response.url().includes( 'edit.php' ) &&
					response.request().method() === 'GET' &&
					response.status() === 200
			),
			page.locator( '#doaction' ).click(),
		] );

		expect( lockdownStatus( id ) ).toBe( 'protected' );
	} );

	test( 'enabling the status column shows the lock status', async ( { page, requestUtils } ) => {
		const { id } = await requestUtils.createPost( {
			title: 'Show my status',
			status: 'publish',
		} );
		setLockdown( { locked: [ id ] } );

		await page.goto( '/wp-admin/edit.php' );

		// Reveal the column through Screen Options. check() is idempotent, which
		// matters because WordPress persists this choice in user meta across runs.
		await page.locator( '#show-settings-link' ).click();
		await page.locator( '#postlockdown_status-hide' ).check();

		const cell = page.locator( `#post-${ id } .column-postlockdown_status` );
		await expect( cell ).toBeVisible();
		await expect( cell ).toContainText( 'Locked' );
		await expect( cell.locator( '.dashicons-lock' ) ).toBeVisible();
	} );
} );
