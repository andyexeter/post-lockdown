<div class="wrap">
	<h2><?php echo esc_html( PostLockdown::TITLE ); ?></h2>
	<form action="options.php" method="post">
		<?php settings_fields( PostLockdown::KEY ); ?>
		<table class="form-table">
			<tbody>
				<tr>
					<th>Locked Posts</th>
					<td>
						<div class="pl-posts-container">
							<div class="pl-posts pl-posts-available">
								<div class="pl-searchbox">
									<input type="text" autocomplete="off" class="pl-autocomplete" placeholder="Search..." />
								</div>
								<ul class="pl-multiselect">
								</ul>
							</div>
							<div class="pl-posts pl-posts-selected">
								<ul class="pl-multiselect" data-key="locked" data-input_name="<?php echo PostLockdown::KEY; ?>[locked_post_ids]">
									<?php /* foreach ( $posts as $selected ) { ?>
									  <?php if ( PostLockdown::is_post_locked( $selected->ID ) ) { ?>
									  <li class="post-<?php echo $selected->ID; ?>" data-post_id="<?php echo $selected->ID; ?>">
									  <span class="post-title"><?php echo $selected->post_title; ?></span>
									  <span class="dashicons dashicons-no"></span>
									  <span class="post-type"><?php echo $selected->post_type; ?></span>
									  </li>
									  <?php } ?>
									  <?php } */ ?>
								</ul>
							</div>
						</div>
						<p class="description">Locked posts cannot be edited, trashed or deleted by non-admins</p>
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
								<ul class="pl-multiselect">
								</ul>
							</div>
							<div class="pl-posts pl-posts-selected">
								<ul class="pl-multiselect" data-key="protected" data-input_name="<?php echo PostLockdown::KEY; ?>[protected_post_ids]">
									<?php /* foreach ( $posts as $selected ) { ?>
									  <?php if ( PostLockdown::is_post_protected( $selected->ID ) ) { ?>
									  <li class="post-<?php echo $selected->ID; ?>" data-post_id="<?php echo $selected->ID; ?>">
									  <span class="post-title"><?php echo $selected->post_title; ?></span>
									  <span class="dashicons dashicons-no"></span>
									  <span class="post-type"><?php echo $selected->post_type; ?></span>
									  </li>
									  <?php } ?>
									  <?php } */ ?>
								</ul>
							</div>
						</div>
						<p class="description">Protected posts cannot be trashed or deleted by non-admins</p>
					</td>
				</tr>
			</tbody>
		</table>
		<input name="submit" type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
	</form>
</div>
