<?php

namespace PostLockdown;

class BulkActions
{
    /** @var PostLockdown */
    private $postlockdown;

    public function __construct(PostLockdown $postlockdown)
    {
        $this->postlockdown = $postlockdown;

        add_action('admin_init', [$this, '_set_post_type_hooks']);
    }

    public function _set_post_type_hooks()
    {
        if (!current_user_can($this->postlockdown->get_admin_cap())) {
            return;
        }

        if (!$this->postlockdown->is_bulk_actions_enabled()) {
            return;
        }

        foreach ($this->postlockdown->get_post_types() as $post_type) {
            add_filter("bulk_actions-edit-$post_type", [$this, '_add_bulk_actions']);
            add_filter("handle_bulk_actions-edit-$post_type", [$this, '_handle_bulk_actions'], 10, 3);
        }
    }

    /**
     * @param array $bulk_actions
     *
     * @return array
     */
    public function _add_bulk_actions($bulk_actions)
    {
        return array_merge($bulk_actions, [
            'postlockdown-lock'      => __('Post Lockdown: Lock', 'post-lockdown'),
            'postlockdown-unlock'    => __('Post Lockdown: Unlock', 'post-lockdown'),
            'postlockdown-protect'   => __('Post Lockdown: Protect', 'post-lockdown'),
            'postlockdown-unprotect' => __('Post Lockdown: Unprotect', 'post-lockdown'),
        ]);
    }

    /**
     * @param string $redirect_to
     * @param string $doaction
     * @param array  $post_ids
     *
     * @return string
     */
    public function _handle_bulk_actions($redirect_to, $doaction, $post_ids)
    {
        switch ($doaction) {
            case 'postlockdown-lock':
                $this->postlockdown->add_locked_post(...$post_ids);
                break;
            case 'postlockdown-unlock':
                $this->postlockdown->remove_locked_post(...$post_ids);
                break;
            case 'postlockdown-protect':
                $this->postlockdown->add_protected_post(...$post_ids);
                break;
            case 'postlockdown-unprotect':
                $this->postlockdown->remove_protected_post(...$post_ids);
                break;
            default:
                break;
        }

        return $redirect_to;
    }
}
