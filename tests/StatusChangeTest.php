<?php

use PostLockdown\PostLockdown;

/**
 * Tests _prevent_status_change(): a non-admin must not be able to unpublish,
 * password-protect or future-date a published protected post.
 */
class StatusChangeTest extends WP_UnitTestCase
{
    /** @var PostLockdown */
    private $postlockdown;

    private $editor_id;
    private $post_id;

    public function set_up()
    {
        parent::set_up();

        global $postlockdown;
        $this->postlockdown = $postlockdown;

        $ids = $this->postlockdown->get_locked_post_ids(true) + $this->postlockdown->get_protected_post_ids(true);
        if ($ids) {
            $this->postlockdown->remove_locked_post(...$ids);
            $this->postlockdown->remove_protected_post(...$ids);
        }

        $this->editor_id = self::factory()->user->create(['role' => 'editor']);
        $this->post_id   = self::factory()->post->create(['post_status' => 'publish']);

        wp_set_current_user($this->editor_id);
    }

    public function test_non_admin_cannot_unpublish_a_protected_post()
    {
        $this->postlockdown->add_protected_post($this->post_id);

        wp_update_post([
            'ID'          => $this->post_id,
            'post_status' => 'draft',
        ]);

        $this->assertSame('publish', get_post_status($this->post_id), 'Protected post status should have been reverted to publish.');
    }

    public function test_non_admin_can_change_an_unprotected_post()
    {
        wp_update_post([
            'ID'          => $this->post_id,
            'post_status' => 'draft',
        ]);

        $this->assertSame('draft', get_post_status($this->post_id), 'An unprotected post should change status normally.');
    }
}
