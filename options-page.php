<div class="wrap">
	<h2>Post Lockdown</h2>
	<p>Choose which posts to disable deletion/trashing</p>
	<form action="options.php" method="post">
		<?php settings_fields( 'post-lockdown' ); ?>
		<table class="form-table">
			<tbody>
				<?php foreach($post_types as $post_type) { ?>
				<tr>
					<th><?php echo $post_type['label']; ?></th>
					<td>
						<select name="postlockdown_locked_posts[]" multiple="multiple" style="min-width:450px;height:150px">
							<?php foreach( $post_type['posts'] as $the_post ) { ?>
							<option value="<?php echo $the_post['ID']; ?>"<?php selected( $the_post['selected'], true ); ?>><?php echo $the_post['post_title']; ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		<input name="submit" type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
	</form>
</div>
