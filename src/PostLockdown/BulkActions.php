<?php

namespace PostLockdown;

class BulkActions
{
    /** @var PostLockdown */
    private $postlockdown;
    /** @var array */
    private $bulk_actions;

    public function __construct(PostLockdown $postlockdown)
    {
        $this->postlockdown = $postlockdown;
        $this->bulk_actions = [
            'postlockdown-lock'      => __('Post Lockdown: Lock', 'postlockdown'),
            'postlockdown-unlock'    => __('Post Lockdown: Unlock', 'postlockdown'),
            'postlockdown-protect'   => __('Post Lockdown: Protect', 'postlockdown'),
            'postlockdown-unprotect' => __('Post Lockdown: Unprotect', 'postlockdown'),
        ];

        add_action('admin_init', [$this, '_set_post_type_hooks']);
    }

    public function _set_post_type_hooks()
    {
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
        return array_merge($bulk_actions, $this->bulk_actions);
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
