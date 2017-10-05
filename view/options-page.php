<div class="wrap">
	<h2><?php echo esc_html( PostLockdown_OptionsPage::PAGE_TITLE ); ?></h2>
	<form action="options.php" method="post">
		<?php settings_fields( PostLockdown::KEY ); ?>
		<p>
			<?php
			esc_html_e(
				'Select locked and protected posts by adding them to the boxes on the right. Use the search field to filter the list of posts.',
				'postlockdown'
			);
			?>
		</p>
		<table class="form-table">
			<tbody>
			<?php foreach ( $blocks as $block ) { ?>
				<tr>
					<th><?php echo esc_html( $block['heading'] ); ?></th>
					<td>
						<div class="pl-posts-container">
							<div class="pl-posts pl-posts-available">
								<div class="pl-searchbox">
									<input type="text" autocomplete="off" class="pl-autocomplete"
										   placeholder="<?php esc_attr_e( 'Search...', 'postlockdown' ); ?>"/>
								</div>
								<span class="spinner"></span>
								<ul class="pl-multiselect">
								</ul>
							</div>
							<div class="pl-posts pl-posts-selected">
								<ul class="pl-multiselect"
									data-key="<?php echo esc_attr( $block['key'] ); ?>"
									data-input_name="<?php echo esc_attr( PostLockdown::KEY ); ?>[<?php echo esc_attr( $block['input_name'] ); ?>]"
								>
								</ul>
							</div>
						</div>
						<p class="description"><?php echo esc_html( $block['description'] ); ?></p>
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<input name="submit" type="submit" class="button button-primary"
			   value="<?php esc_attr_e( 'Save Changes' ); ?>"/>
	</form>
</div>
