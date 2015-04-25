<div class="wrap">
	<h2><?php echo esc_html( $title ); ?></h2>
	<form action="options.php" method="post">
		<?php settings_fields( $slug ); ?>
		<h3>Protected Posts</h3>
		<table class="form-table">
			<tbody>
				<tr><td colspan="2"><p>Protected posts cannot be trashed or deleted by non-admins</p></td></tr>
				<?php foreach($post_types as $post_type) { ?>
				<tr>
					<th><?php echo esc_html( $post_type['label'] ); ?></th>
					<td>
						<select name="<?php echo $key; ?>[protected_post_ids][]" multiple="multiple" style="min-width:450px;height:150px">
							<?php foreach( $post_type['posts'] as $the_post ) { ?>
							<option value="<?php echo esc_attr( $the_post['ID'] ); ?>"<?php selected( $the_post['protected'], true ); ?>><?php echo esc_html( $the_post['post_title'] ); ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		<h3>Locked Posts</h3>
		<table class="form-table">
			<tbody>
				<tr><td colspan="2"><p>As well as preventing trashing/deletion, locked posts are unable to be edited by non-admins</p></td></tr>
				<?php foreach($post_types as $post_type) { ?>
				<tr>
					<th><?php echo esc_html( $post_type['label'] ); ?></th>
					<td>
						<select name="<?php echo $key; ?>[locked_post_ids][]" multiple="multiple" style="min-width:450px;height:150px">
							<?php foreach( $post_type['posts'] as $the_post ) { ?>
							<option value="<?php echo esc_attr( $the_post['ID'] ); ?>"<?php selected( $the_post['locked'], true ); ?>><?php echo esc_html( $the_post['post_title'] ); ?></option>
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
