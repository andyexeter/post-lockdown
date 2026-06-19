/**
 * Surfaces Post Lockdown's "status reverted" message in the block editor.
 *
 * The classic editor shows this via a redirect admin notice, but the block
 * editor saves over the REST API with no redirect. Instead, the server flags
 * the revert on the saved post (the `postlockdown_status_reverted` REST field),
 * and this script raises an editor notice when a save reports it.
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.data ) {
		return;
	}

	var data = wp.data;
	var __ = wp.i18n.__;
	var NOTICE_ID = 'postlockdown-status-reverted';
	var wasSaving = false;

	data.subscribe( function () {
		var editor = data.select( 'core/editor' );

		if ( ! editor ) {
			return;
		}

		var isSaving = editor.isSavingPost() && ! editor.isAutosavingPost();
		var justSaved = wasSaving && ! isSaving;

		// Update the flag *before* dispatching: createErrorNotice() notifies
		// subscribers synchronously, so this callback re-enters and would
		// otherwise recurse infinitely on the same transition.
		wasSaving = isSaving;

		if ( justSaved ) {
			var post = editor.getCurrentPost();

			if ( post && post[ 'postlockdown_status_reverted' ] ) {
				data.dispatch( 'core/notices' ).createErrorNotice(
					__(
						'This post is protected by Post Lockdown and must stay published.',
						'post-lockdown'
					),
					{ id: NOTICE_ID, isDismissible: true }
				);
			}
		}
	} );
} )( window.wp );
