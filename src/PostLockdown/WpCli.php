<?php

namespace PostLockdown;

use WP_CLI\Formatter;

class WpCli
{
    /** @var PostLockdown */
    private $postlockdown;

    public function __construct(PostLockdown $postlockdown)
    {
        $this->postlockdown = $postlockdown;
    }

    /**
     * Returns whether a given post is locked or protected.
     *
     * ## OPTIONS
     *
     * <post>
     * : Post ID or slug to check
     *
     *
     * ## EXAMPLES
     *
     *     wp postlockdown status 42
     *     wp postlockdown status hello-world
     *
     * @when after_wp_load
     */
    public function status(array $args)
    {
        list($post) = $args;

        if (!is_numeric($post)) {
            $posts = $this->postlockdown->get_posts([
                'posts_per_page' => 1,
                'name'           => $post,
            ]);

            if (empty($posts)) {
                \WP_CLI::error("Could not find post '$post'", true);
            }

            $post = reset($posts)->ID;
        }

        if ($this->postlockdown->is_post_locked($post)) {
            \WP_CLI::log('locked');
        } elseif ($this->postlockdown->is_post_protected($post)) {
            \WP_CLI::log('protected');
        }
    }

    /**
     * Returns a list of locked posts.
     *
     *     wp postlockdown locked
     *
     * ## OPTIONS
     *
     * [--field=<field>]
     * : Prints the value of a single field for each post.
     *
     * [--fields=<fields>]
     * : Limit the output to specific object fields.
     *
     * [--format=<format>]
     * : Render output in a particular format.
     * ---
     * default: table
     * options:
     *   - table
     *   - csv
     *   - ids
     *   - json
     *   - count
     *   - yaml
     * ---
     *
     * @when after_wp_load
     */
    public function locked(array $args, array $assoc_args)
    {
        $posts = $this->postlockdown->get_posts([
            'nopaging' => true,
            'post__in' => $this->postlockdown->get_locked_post_ids(),
        ]);

        $formatter = $this->createFormatter($assoc_args);

        $formatter->display_items($posts);
    }

    /**
     * Returns a list of protected posts.
     *
     *     wp postlockdown protected
     *
     * ## OPTIONS
     *
     * [--field=<field>]
     * : Prints the value of a single field for each post.
     *
     * [--fields=<fields>]
     * : Limit the output to specific object fields.
     *
     * [--format=<format>]
     * : Render output in a particular format.
     * ---
     * default: table
     * options:
     *   - table
     *   - csv
     *   - ids
     *   - json
     *   - count
     *   - yaml
     * ---
     *
     * @when       after_wp_load
     * @subcommand protected
     */
    public function _protected(array $args, array $assoc_args)
    {
        $posts = $this->postlockdown->get_posts([
            'nopaging' => true,
            'post__in' => $this->postlockdown->get_protected_post_ids(),
        ]);

        $formatter = $this->createFormatter($assoc_args);

        $formatter->display_items($posts);
    }

    /**
     * Adds one or more posts to the list of locked posts.
     *
     * ## OPTIONS
     *
     * <posts>...
     * : Comma separated list of post IDs or slugs to lock
     *
     * @when after_wp_load
     */
    public function lock(array $args)
    {
        $posts = $this->getPostIds($args[0]);

        $this->postlockdown->add_locked_post(...$posts);
    }

    /**
     * Removes one or more posts from the list of locked posts.
     *
     * ## OPTIONS
     *
     * <posts>...
     * : Comma separated list of post IDs or slugs to unlock
     *
     * @when after_wp_load
     */
    public function unlock(array $args)
    {
        $this->postlockdown->remove_locked_post(...$this->getPostIds($args[0]));
    }

    /**
     * Adds one or more posts to the list of protected posts.
     *
     * ## OPTIONS
     *
     * <posts>...
     * : Comma separated list of post IDs or slugs to protect
     *
     * @when after_wp_load
     */
    public function protect(array $args)
    {
        $this->postlockdown->add_protected_post(...$this->getPostIds($args[0]));
    }

    /**
     * Removes one or more posts from the list of protected posts.
     *
     * ## OPTIONS
     *
     * <posts>...
     * : Comma separated list of post IDs or slugs to unprotect
     *
     * @when after_wp_load
     */
    public function unprotect(array $args)
    {
        $this->postlockdown->remove_protected_post(...$this->getPostIds($args[0]));
    }

    private function getPostIds($arg)
    {
        return array_map(function ($post) {
            if (is_numeric($post)) {
                return (int)$post;
            }

            $posts = $this->postlockdown->get_posts([
                'posts_per_page' => 1,
                'name'           => $post,
            ]);

            if (empty($posts)) {
                \WP_CLI::error("Could not find post '$post'", true);
            }

            return (int)reset($posts)->ID;
        }, explode(',', $arg));
    }

    /**
     * @return Formatter
     */
    private function createFormatter(array $assoc_args)
    {
        if (!isset($assoc_args['format'])) {
            $assoc_args['format'] = 'table';
        }

        if (!isset($assoc_args['fields'])) {
            $assoc_args['fields'] = [
                'ID',
                'post_title',
                'post_name',
                'post_date',
                'post_status',
            ];
        }

        return new Formatter($assoc_args);
    }
}
