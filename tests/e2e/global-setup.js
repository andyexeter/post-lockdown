import { request } from '@playwright/test';
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { ensureEditor } from './helpers.js';

/**
 * Logs in once via the REST API and saves the authenticated storage state so
 * every test starts as an administrator.
 */
async function globalSetup() {
	const requestContext = await request.newContext( {
		baseURL: process.env.WP_BASE_URL,
	} );

	const requestUtils = new RequestUtils( requestContext, {
		storageStatePath: process.env.STORAGE_STATE_PATH,
		user: {
			username: process.env.WP_USERNAME,
			password: process.env.WP_PASSWORD,
		},
	} );

	await requestUtils.setupRest();

	await requestContext.dispose();

	// A non-admin account used to verify the lock/protect restrictions, which
	// only apply to users without the admin capability.
	ensureEditor();
}

export default globalSetup;