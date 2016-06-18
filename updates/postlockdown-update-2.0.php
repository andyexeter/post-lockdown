<?php
global $postlockdown;

// Update user meta so the new column is hidden by default.
$user_ids = get_users( array( 'fields' => 'ID' ) );

/** @var PostLockdown_StatusColumn $status_column */
$status_column = $postlockdown->registry['StatusColumn'];
foreach ( $user_ids as $user_id ) {
	$status_column->update_hidden_columns( $user_id );
}
