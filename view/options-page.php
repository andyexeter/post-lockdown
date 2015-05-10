<div class="wrap">
	<h2><?php echo esc_html( PostLockdown::TITLE ); ?></h2>
	<form action="options.php" method="post">
		<?php settings_fields( PostLockdown::KEY ); ?>
		<p>
			<?php esc_html_e( 'Select locked and protected posts by adding them to the boxes on the right.
			Use the search field to filter the list of posts.', 'postlockdown' ); ?>
		</p>
		<table class="form-table">
			<tbody>
				<tr>
					<th><?php esc_html_e( 'Locked Posts', 'postlockdown' ); ?></th>
					<td>
						<div class="pl-posts-container">
							<div class="pl-posts pl-posts-available">
								<div class="pl-searchbox">
									<input type="text" autocomplete="off" class="pl-autocomplete" placeholder="Search..." />
								</div>
								<span class="spinner"></span>
								<ul class="pl-multiselect">
								</ul>
							</div>
							<div class="pl-posts pl-posts-selected">
								<ul class="pl-multiselect" data-key="locked" data-input_name="<?php esc_attr_e( PostLockdown::KEY ); ?>[locked_post_ids]">
								</ul>
							</div>
						</div>
						<p class="description"><?php esc_html_e( 'Locked posts cannot be edited, trashed or deleted by non-admins', 'postlockdown' ); ?></p>
					</td>
				</tr>
				<tr>
					<th>Protected Posts</th>
					<td>
						<div class="pl-posts-container">
							<div class="pl-posts pl-posts-available">
								<div class="pl-searchbox">
									<input type="text" autocomplete="off" class="pl-autocomplete" placeholder="Search..." />
								</div>
								<span class="spinner"></span>
								<ul class="pl-multiselect">
								</ul>
							</div>
							<div class="pl-posts pl-posts-selected">
								<ul class="pl-multiselect" data-key="protected" data-input_name="<?php esc_attr_e( PostLockdown::KEY ); ?>[protected_post_ids]">
								</ul>
							</div>
						</div>
						<p class="description"><?php esc_html_e( 'Protected posts cannot be trashed or deleted by non-admins', 'postlockdown' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<input name="submit" type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
	</form>
</div>
