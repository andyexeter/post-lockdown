<?php
foreach ( $notices as $notice ) {
	?>
	<div class="notice is-dismissible <?php echo esc_attr( implode( ' ', (array) $notice['class'] ) ); ?>">
		<p><?php echo esc_html( $notice['message'] ); ?></p>
	</div>
	<?php
}
