<?php foreach ( $notices as $notice ) { ?>
	<div class="notice is-dismissible <?php echo esc_attr( implode( ' ', (array) $notice['class'] ) ); ?>">
		<p><?php echo $notice['message']; // xss ok ?></p>
	</div>
<?php
}
