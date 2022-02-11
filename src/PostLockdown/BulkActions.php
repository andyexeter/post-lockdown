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
            'postlockdown-lock'      => __('PostLockdown: Lock', 'postlockdown'),
            'postlockdown-unlock'    => __('PostLockdown: Unlock', 'postlockdown'),
            'postlockdown-protect'   => __('PostLockdown: Protect', 'postlockdown'),
            'postlockdown-unprotect' => __('PostLockdown: Unprotect', 'postlockdown'),
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
     * @return array
     */
    public function _add_bulk_actions($bulk_actions)
    {
        return array_merge($bulk_actions, $this->bulk_actions);
    }

    public function _handle_bulk_actions($redirect_to, $doaction, $post_ids)
    {
        if (!\in_array($doaction, array_keys($this->bulk_actions))) {
            return $redirect_to;
        }

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
