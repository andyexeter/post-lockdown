<?php

use PostLockdown\PostLockdown;
use PostLockdown\WpCli;

/**
 * Integration tests for the WP-CLI command class.
 *
 * These exercise the mutating commands (lock/unlock/protect/unprotect), which
 * cover the argument parsing, slug resolution and delegation to the model
 * without needing the full WP-CLI runtime. The read/display commands
 * (status, locked, protected) rely on the WP_CLI runtime and formatter, so
 * they are out of scope here.
 */
class WpCliTest extends WP_UnitTestCase
{
    /** @var PostLockdown */
    private $postlockdown;

    /** @var WpCli */
    private $cli;

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

        $this->cli = new WpCli($this->postlockdown);
    }

    public function test_lock_and_unlock_by_id()
    {
        $post_id = self::factory()->post->create();

        $this->cli->lock([(string) $post_id]);
        $this->assertTrue($this->postlockdown->is_post_locked($post_id));

        $this->cli->unlock([(string) $post_id]);
        $this->assertFalse($this->postlockdown->is_post_locked($post_id));
    }

    public function test_protect_and_unprotect_by_id()
    {
        $post_id = self::factory()->post->create();

        $this->cli->protect([(string) $post_id]);
        $this->assertTrue($this->postlockdown->is_post_protected($post_id));

        $this->cli->unprotect([(string) $post_id]);
        $this->assertFalse($this->postlockdown->is_post_protected($post_id));
    }

    public function test_lock_accepts_a_comma_separated_list()
    {
        $first  = self::factory()->post->create();
        $second = self::factory()->post->create();

        $this->cli->lock(["$first,$second"]);

        $this->assertTrue($this->postlockdown->is_post_locked($first));
        $this->assertTrue($this->postlockdown->is_post_locked($second));
    }

    public function test_lock_resolves_a_post_by_slug()
    {
        $post_id = self::factory()->post->create(['post_name' => 'cli-target']);

        $this->cli->lock(['cli-target']);

        $this->assertTrue($this->postlockdown->is_post_locked($post_id));
    }

    public function test_protect_resolves_a_post_by_slug()
    {
        $post_id = self::factory()->post->create(['post_name' => 'protect-target']);

        $this->cli->protect(['protect-target']);

        $this->assertTrue($this->postlockdown->is_post_protected($post_id));
    }
}
