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
								<div class="pl-posts-search-box">
									<input type="text" autocomplete="off" class="pl-posts-autocomplete" placeholder="Search..." />
								</div>
								<ul class="pl-multiselect" data-input_name="">
								</ul>
							</div>
							<div class="pl-posts pl-posts-selected">
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
