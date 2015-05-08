jQuery( function( $ ) {

	$( '.pl-posts-container' ).each( function() {

		$( this ).plmultiselect( {
			inputSearch: $( this ).find( '.pl-autocomplete' ),
			ulAvailable: $( this ).find( '.pl-posts-available .pl-multiselect' ),
			ulSelected: $( this ).find( '.pl-posts-selected .pl-multiselect' )
		} );
	} );

} );