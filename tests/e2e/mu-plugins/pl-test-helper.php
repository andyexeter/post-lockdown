<?php
/**
 * Plugin Name: Post Lockdown E2E Test Helper
 * Description: Test-only mu-plugin (mounted by wp-env). Forces the classic
 *              editor when the `pl_test_force_classic` option is truthy, so the
 *              E2E suite can deterministically cover both the classic and block
 *              editors. Not shipped with the plugin.
 */

add_filter('use_block_editor_for_post', static function ($use_block_editor) {
    if (get_option('pl_test_force_classic')) {
        return false;
    }

    return $use_block_editor;
}, 100);
