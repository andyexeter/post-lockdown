/*!
	jquery-plmultiselect v1.0.0
	A jQuery multi select plugin for WordPress admin
	(c) 2015 Andy Palmer
	license: http://www.gnu.org/licenses/gpl-2.0.html
*/
( function( $, window ) {

	'use strict';

	var pluginName = 'plmultiselect';

	function Plugin( element, options ) {

		this.$el = $( element );
		this.options = $.extend( { }, $.fn[pluginName].defaults, options );

		this.$search = this.options.inputSearch;
		this.$left = this.options.ulAvailable;
		this.$right = this.options.ulSelected;
		this.$spinner = this.options.spinner;

		this.doingAjax = false;

		this.init( );
	}

	Plugin.prototype = {
		/**
		 * Initialises the plugin instance
		 */
		init: function( ) {

			var self = this;

			// Set the selected items menu to the height of the left side
			$( window ).load( $.proxy( function( ) {
				this.$right.height( this.$left.parent( ).height( ) );
			}, this ) );

			this.$search.autocomplete( {
				minLength: 0,
				source: function( request ) {

					self.queryItems( request.term, 0, function( json ) {
						self.renderMenu( self.$left, json.data );
					} );
				},
				create: function() {
					$( this ).autocomplete( 'search' );
				}

			} );

			// Attach the click handler for available items
			this.$left.on( 'click', '> li', function( ) {

				if ( !$( this ).is( '.selected' ) ) {
					self.selectItem.call( self, $( this ) );
				}

			} );

			// Attach the click handler for remove buttons on selected items
			this.$right.on( 'click', '> li .dashicons-no', function( ) {

				var post_id = $( this ).closest( 'li' ).data( 'post_id' );
				$( this ).closest( 'li' ).remove( );
				self.$left.find( '.post-' + post_id ).removeClass( 'selected' );
			} );

			// If we have a list of items add them to the selected menu
			if ( this.options.selected.length ) {
				this.selectItem( this.options.selected );
			}

			// Paginate scrolling of the available items menu
			this.$left.on( 'scroll', function( ) {

				if ( this.scrollHeight - $( this ).scrollTop( ) <= $( this ).height( ) ) {
					self.nextPage( );
				}
			} );
		},
		/**
		 * Destroys the plugin instance
		 */
		destroy: function( ) {

			//this.$el.off( 'click.' + pluginName, this.options.tabSelector).removeData( pluginName + '.plugin' );
		},
		/**
		 * Emits a namespaced plugin event.
		 * @param {string} event - The name of the event to emit.
		 */
		emit: function( event ) {

			this.$el.trigger( pluginName + '.' + event, [ this.options ] );
		},
		nextPage: function( ) {

			var term = this.options.inputSearch.val( ),
				offset = this.$left.children( 'li' ).length;

			this.queryItems( term, offset, $.proxy( function( json ) {
				this.renderMenu( this.$left, json.data, '', true );
			}, this ) );

		},
		/**
		 * Makes an AJAX request for a list of items.
		 * @param {string} term - The term to query items for.
		 * @param {number} offset - The number of items to pass over
		 * @param {function} success - If the request is successful this function will be invoked.
		 */
		queryItems: function( term, offset, success ) {

			// Only allow one AJAX request at a time
			if ( this.doingAjax ) {
				return false;
			}

			this.doingAjax = true;

			this.$spinner.addClass( 'is-active' );

			$.ajax( {
				url: window.ajaxurl,
				type: 'GET',
				data: {
					action: this.options.ajaxAction,
					term: term,
					offset: offset
				},
				context: this,
				success: function( json ) {

					if ( $.isFunction( success ) ) {
						success.call( this, json );
					}
				},
				complete: function() {
					this.doingAjax = false;
					this.$spinner.removeClass( 'is-active' );
				}
			} );
		},
		/**
		 * Selects an item by cloning it, or adds an array of plain objects, to the right hand box.
		 * @param {jQuery||object[]} arg - The jQuery object or an array of plain objects
		 */
		selectItem: function( arg ) {

			var input_name = this.options.inputName + '[]';

			if ( $.isArray( arg ) ) {
				this.renderMenu( this.$right, arg, input_name, true );
			} else {

				arg.clone( false )
					.data( 'post_id', arg.data( 'post_id' ) )
					.appendTo( this.$right )
					.find( 'input.post-id' ).attr( 'name', input_name );

				arg.addClass( 'selected' );
			}
		},
		/**
		 * Fills a menu with a list of items.
		 * @param {jQuery} menu - The menu to fill.
		 * @param {array} items - List of items to add to the menu.
		 * @param {string} input_name - The input name to be passed to getItemTpl().
		 * @param {bool} append - Whether to append to the menu. Setting to false clears the menu's innerHTML first.
		 */
		renderMenu: function( menu, items, input_name, append ) {

			var self = this,
				$items = [ ];

			if ( !append ) {
				menu.empty( );
			}

			$.each( items, function( i, item ) {
				$items.push( self.getItemTpl( item, input_name ) );
			} );

			menu.append( $items );
		},
		/**
		 * Returns a jQuery object of an item to be appended to a menu.
		 * @param {object} item       The item to build a jQuery object with.
		 * @param {string} input_name The hidden input field name.
		 * @returns {jquery} The item as a jQuery object.
		 */
		getItemTpl: function( item, input_name ) {

			if ( !input_name ) {
				input_name = '';
			}

			var $item = $( '<li />' )
				.addClass( 'post-' + item.ID )
				.data( 'post_id', item.ID )
				.append( '<span class="post-title">' + item.post_title + '</span>' +
					'<span class="dashicons dashicons-no" title="Remove"></span>' +
					'<span class="post-type">' + item.post_type + '</span>' +
					'<input type="hidden" class="post-id" name="' + input_name + '" value="' + item.ID + '" />'
					);
			if ( this.$right.find( '.post-' + item.ID ).length ) {
				$item.addClass( 'selected' );
			}

			return $item;
		}

	};

	$.fn[pluginName] = function( ) {

		var args = arguments;
		return this.each( function( ) {

			var plugin = $( this ).data( pluginName + '.plugin' );
			if ( !plugin ) {

				plugin = new Plugin( this, args[0] );
				$( this ).data( pluginName + '.plugin', plugin );
			}

			if ( typeof args[0] === 'string' && args[0].charAt( 0 ) !== '_' && $.isFunction( plugin[args[0]] ) ) {
				plugin[args[0]].apply( plugin, [ ].slice.call( args, 1 ) );
			}
		} );
	};

	$.fn[pluginName].defaults = {
	};

} )( jQuery, window );
