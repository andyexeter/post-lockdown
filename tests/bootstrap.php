<?php
/**
 * PHPUnit bootstrap file for the WordPress integration test suite.
 *
 * Loads the WordPress test framework and the plugin so it runs inside a real
 * WordPress instance (whichever version bin/install-wp-tests.sh installed).
 */

$_tests_dir = getenv('WP_TESTS_DIR');

if (!$_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

// Forward the phpunit-polyfills location to the WP test bootstrap (required since WP 5.9).
$_polyfills_path = getenv('WP_TESTS_PHPUNIT_POLYFILLS_PATH');
if (false !== $_polyfills_path) {
    define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_polyfills_path);
} elseif (file_exists(dirname(__DIR__) . '/vendor/yoast/phpunit-polyfills')) {
    define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname(__DIR__) . '/vendor/yoast/phpunit-polyfills');
}

if (!file_exists("{$_tests_dir}/includes/functions.php")) {
    echo "Could not find {$_tests_dir}/includes/functions.php." . PHP_EOL;
    echo 'Have you run bin/install-wp-tests.sh ?' . PHP_EOL;
    exit(1);
}

// Give access to tests_add_filter() before WordPress is loaded.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin()
{
    require dirname(__DIR__) . '/post-lockdown.php';
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";