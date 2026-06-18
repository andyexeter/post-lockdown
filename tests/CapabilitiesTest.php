<?php

use PostLockdown\PostLockdown;

/**
 * Tests the capability restrictions that are the core of the plugin.
 *
 * These exercise the WordPress-version-sensitive parts of the plugin (the
 * user_has_cap / wp_insert_post_data hooks and meta-capability mapping), which
 * is exactly what tends to break when a new version of WordPress is released.
 */
class CapabilitiesTest extends WP_UnitTestCase
{
    /** @var PostLockdown */
    private $postlockdown;

    private $editor_id;
    private $admin_id;
    private $post_id;

    public function set_up()
    {
        parent::set_up();

        global $postlockdown;
        $this->postlockdown = $postlockdown;
        $this->reset_lockdown();

        $this->editor_id = self::factory()->user->create(['role' => 'editor']);
        $this->admin_id  = self::factory()->user->create(['role' => 'administrator']);
        $this->post_id   = self::factory()->post->create([
            'post_status' => 'publish',
            'post_author' => $this->editor_id,
        ]);
    }

    public function tear_down()
    {
        $this->reset_lockdown();
        parent::tear_down();
    }

    /**
     * Clears any locked/protected posts so each test starts from a clean state,
     * since the plugin keeps its state on a long-lived global instance.
     */
    private function reset_lockdown()
    {
        $ids = $this->postlockdown->get_locked_post_ids(true) + $this->postlockdown->get_protected_post_ids(true);

        if ($ids) {
            $this->postlockdown->remove_locked_post(...$ids);
            $this->postlockdown->remove_protected_post(...$ids);
        }
    }

    public function test_plugin_is_loaded_and_hooks_registered()
    {
        $this->assertInstanceOf(PostLockdown::class, $this->postlockdown);
        $this->assertSame(10, has_filter('user_has_cap', [$this->postlockdown, '_filter_cap']));
        $this->assertSame(10, has_filter('wp_insert_post_data', [$this->postlockdown, '_prevent_status_change']));
        $this->assertSame(10, has_action('delete_post', [$this->postlockdown, '_remove_deleted_post']));
    }

    public function test_editor_cannot_edit_or_delete_a_locked_post()
    {
        $this->postlockdown->add_locked_post($this->post_id);

        $this->assertFalse(user_can($this->editor_id, 'edit_post', $this->post_id), 'Editor should not be able to edit a locked post.');
        $this->assertFalse(user_can($this->editor_id, 'delete_post', $this->post_id), 'Editor should not be able to delete a locked post.');
    }

    public function test_editor_cannot_delete_but_can_edit_a_protected_post()
    {
        $this->postlockdown->add_protected_post($this->post_id);

        $this->assertTrue(user_can($this->editor_id, 'edit_post', $this->post_id), 'Editor should still be able to edit a protected (not locked) post.');
        $this->assertFalse(user_can($this->editor_id, 'delete_post', $this->post_id), 'Editor should not be able to delete a protected post.');
    }

    public function test_admin_can_always_edit_and_delete()
    {
        $this->postlockdown->add_locked_post($this->post_id);

        $this->assertTrue(user_can($this->admin_id, 'edit_post', $this->post_id), 'Admin should always be able to edit a locked post.');
        $this->assertTrue(user_can($this->admin_id, 'delete_post', $this->post_id), 'Admin should always be able to delete a locked post.');
    }

    public function test_unrestricted_post_is_unaffected()
    {
        $this->assertTrue(user_can($this->editor_id, 'edit_post', $this->post_id));
        $this->assertTrue(user_can($this->editor_id, 'delete_post', $this->post_id));
    }

    public function test_deleting_a_post_removes_it_from_the_lists()
    {
        $this->postlockdown->add_locked_post($this->post_id);
        $this->assertTrue($this->postlockdown->is_post_locked($this->post_id));

        wp_delete_post($this->post_id, true);

        $this->assertFalse($this->postlockdown->is_post_locked($this->post_id));
    }
}
