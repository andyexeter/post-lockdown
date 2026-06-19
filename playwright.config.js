import { fileURLToPath } from 'node:url';
import { defineConfig, devices } from '@playwright/test';

const STORAGE_STATE_PATH = fileURLToPath(
	new URL( './artifacts/storage-states/admin.json', import.meta.url )
);

process.env.WP_BASE_URL ??= 'http://localhost:8888';
process.env.WP_USERNAME ??= 'admin';
process.env.WP_PASSWORD ??= 'password';
process.env.STORAGE_STATE_PATH = STORAGE_STATE_PATH;

export default defineConfig( {
	testDir: './tests/e2e',
	globalSetup: fileURLToPath(
		new URL( './tests/e2e/global-setup.js', import.meta.url )
	),
	reporter: process.env.CI ? [ [ 'github' ], [ 'list' ] ] : 'list',
	forbidOnly: !! process.env.CI,
	retries: process.env.CI ? 2 : 0,
	workers: 1,
	timeout: 90_000,
	use: {
		baseURL: process.env.WP_BASE_URL,
		storageState: STORAGE_STATE_PATH,
		actionTimeout: 15_000,
		trace: 'retain-on-failure',
		video: 'on-first-retry',
		screenshot: 'only-on-failure',
	},
	projects: [
		{ name: 'chromium', use: { ...devices[ 'Desktop Chrome' ] } },
	],
} );