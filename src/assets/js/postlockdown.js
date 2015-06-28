jQuery( function ( $ ) {
	'use strict';

	$( '.pl-posts-container' ).each( function () {

		var selectedKey = $( this ).find( '.pl-posts-selected .pl-multiselect' ).data( 'key' ),
			inputName = $( this ).find( '.pl-posts-selected .pl-multiselect' ).data( 'input_name' );

		$( this ).plmultiselect( {
			ajaxAction: 'pl_autocomplete',
			inputName: inputName,
			inputSearch: $( this ).find( '.pl-autocomplete' ),
			ulAvailable: $( this ).find( '.pl-posts-available .pl-multiselect' ),
			ulSelected: $( this ).find( '.pl-posts-selected .pl-multiselect' ),
			selected: window.postlockdown[ selectedKey ] || [ ],
			spinner: $( this ).find( '.spinner' )
		} );
	} );

} );
