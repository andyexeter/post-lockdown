<?php

namespace PostLockdown;

class StatusColumn
{
    const COLUMN_KEY = 'postlockdown_status';

    /** @var PostLockdown */
    private $postlockdown;

    public function __construct(PostLockdown $postlockdown)
    {
        $this->postlockdown = $postlockdown;
        add_action('admin_init', [$this, '_set_post_type_hooks']);
        add_action('admin_head', [$this, '_column_output_style']);
    }

    /**
     * Callback for the 'admin_init' hook.
     */
    public function _set_post_type_hooks()
    {
        $column_hidden = apply_filters('postlockdown_column_hidden_default', true);

        foreach ($this->postlockdown->get_post_types() as $post_type) {
            add_filter("manage_{$post_type}_posts_columns", [$this, '_column_add']);
            add_action("manage_{$post_type}_posts_custom_column", [$this, '_column_output'], 10, 2);

            if ($column_hidden) {
                /*
                 * Credit: Yoast SEO
                 *
                 * Use the `get_user_option_{$option}` filter to change the output of the get_user_option
                 * function for the `manage{$screen}columnshidden` option, which is based on the current
                 * admin screen. The admin screen we want to target is the `edit-{$post_type}` screen.
                 */
                $filter = \sprintf('get_user_option_%s', \sprintf('manage%scolumnshidden', 'edit-' . $post_type));
                add_filter($filter, [$this, '_column_hidden'], 10, 3);
            }
        }
    }

    /**
     * Filter for all 'get_user_option_manageedit-{$post_type}columnshidden' hooks
     * added in the _set_post_type_hooks() method.
     *
     * @see PostLockdown_StatusColumn::_set_post_type_hooks()
     *
     * Hides the status column for the user if they haven't already hidden any columns
     * on the current screen.
     *
     * @param          $result
     * @param          $option
     * @param \WP_User $user
     *
     * @return array
     */
    public function _column_hidden($result, $option, $user)
    {
        global $wpdb;

        $prefix = $wpdb->get_blog_prefix();
        if (!$user->has_prop($prefix . $option) && !$user->has_prop($option)) {
            if (!\is_array($result)) {
                $result = [];
            }

            $result[] = self::COLUMN_KEY;
        }

        return $result;
    }

    /**
     * Filter for the manage_{$post_type}_posts_columns hook.
     *
     * Adds the plugin's status column to all post list tables.
     *
     * @param array $columns
     *
     * @return array
     */
    public function _column_add($columns)
    {
        $label = apply_filters('postlockdown_column_label', 'Post Lockdown');

        $new_columns  = [];
        $column_added = false;

        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;

            if (!$column_added && ('title' === $key || 'name' === $key)) {
                $new_columns[self::COLUMN_KEY] = $label;

                $column_added = true;
            }
        }

        if (!$column_added) {
            $new_columns[self::COLUMN_KEY] = $label;
        }

        return $new_columns;
    }

    /**
     * Callback for the manage_{$post_type}_posts_custom_column hook.
     *
     * Prints the relevant output, if any, for the status column.
     *
     * @param string $column
     * @param int    $post_id
     */
    public function _column_output($column, $post_id)
    {
        if (self::COLUMN_KEY !== $column) {
            return;
        }

        $html   = '';
        $status = false;
        if ($this->postlockdown->is_post_locked($post_id)) {
            $html   = '<span title="Locked - Cannot be edited, trashed or deleted" class="dashicons dashicons-lock"></span> Locked';
            $status = 'locked';
        } elseif ($this->postlockdown->is_post_protected($post_id)) {
            $html   = '<span title="Protected - Cannot be trashed or deleted" class="dashicons dashicons-unlock"></span> Protected';
            $status = 'protected';
        }

        if (false !== $status) {
            $html = apply_filters('postlockdown_column_html', $html, $status, $post_id);
            echo $html; // xss ok
        }
    }

    public function _column_output_style()
    {
        global $pagenow;

        if ('edit.php' !== $pagenow) {
            return;
        } ?>
		<style id="postlockdown-column-styles">
			.fixed .column-postlockdown_status {
				width: 10%;
			}

			.fixed td.column-postlockdown_status .dashicons {
				color: #444;
				font-size: 22px;
			}
		</style>
		<?php
    }
}
