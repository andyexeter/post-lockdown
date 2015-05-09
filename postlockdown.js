jQuery( function( $ ) {

	$( '.pl-posts-container' ).each( function() {

		var selectedKey = $( this ).find( '.pl-posts-selected .pl-multiselect' ).data( 'key' );

		$( this ).plmultiselect( {
			inputSearch: $( this ).find( '.pl-autocomplete' ),
			ulAvailable: $( this ).find( '.pl-posts-available .pl-multiselect' ),
			ulSelected: $( this ).find( '.pl-posts-selected .pl-multiselect' ),
			selected: window.postlockdown[ selectedKey ] || []
		} );
	} );

} );