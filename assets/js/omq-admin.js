/*!
 * Ofnoa Marquee — admin builder.
 */
( function ( $ ) {
	'use strict';

	var $doc = $( document );

	/* ---------------------------------------------------------------
	 * Clipboard (works on the list table too)
	 * ------------------------------------------------------------- */
	$doc.on( 'click', '.omq-copy', function () {
		var text = $( this ).data( 'clipboard' ) || $( this ).text();
		var $el = $( this );

		var done = function () {
			var original = $el.text();
			$el.addClass( 'is-copied' ).text( ( window.omqAdmin && omqAdmin.i18n.copied ) || 'Copied!' );
			window.setTimeout( function () {
				$el.removeClass( 'is-copied' ).text( original );
			}, 1200 );
		};

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then( done );
		} else {
			var $tmp = $( '<textarea>' ).val( text ).appendTo( 'body' ).select();
			document.execCommand( 'copy' );
			$tmp.remove();
			done();
		}
	} );

	if ( ! $( '.omq-builder' ).length ) {
		return;
	}

	/* ---------------------------------------------------------------
	 * Tabs
	 * ------------------------------------------------------------- */
	$doc.on( 'click', '.omq-tab', function () {
		var tab = $( this ).data( 'tab' );
		$( '.omq-tab' ).removeClass( 'is-active' );
		$( this ).addClass( 'is-active' );
		$( '.omq-panel' ).removeClass( 'is-active' );
		$( '.omq-panel[data-panel="' + tab + '"]' ).addClass( 'is-active' );
	} );

	/* ---------------------------------------------------------------
	 * Conditional fields
	 * ------------------------------------------------------------- */
	function fieldValue( key ) {
		var $input = $( '[name="omq_settings[' + key + ']"]' );
		if ( ! $input.length ) {
			return null;
		}
		var $check = $input.filter( ':checkbox' );
		if ( $check.length ) {
			return $check.is( ':checked' ) ? 1 : 0;
		}
		return $input.last().val();
	}

	function applyConditions() {
		$( '.omq-field[data-condition]' ).each( function () {
			var cond = $( this ).data( 'condition' );
			var show = true;
			$.each( cond, function ( key, allowed ) {
				var value = fieldValue( key );
				var ok = false;
				$.each( allowed, function ( i, candidate ) {
					if ( String( candidate ) === String( value ) ) {
						ok = true;
					}
				} );
				if ( ! ok ) {
					show = false;
				}
			} );
			$( this ).toggleClass( 'is-hidden', ! show );
		} );
	}

	/* ---------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------- */
	function initColors( $scope ) {
		( $scope || $( document ) ).find( '.omq-color' ).each( function () {
			if ( $( this ).hasClass( 'wp-color-picker' ) ) {
				return;
			}
			$( this ).wpColorPicker( {
				change: function () {
					window.setTimeout( schedulePreview, 60 );
				},
				clear: schedulePreview
			} );
		} );
	}

	$doc.on( 'input change', '.omq-range__input', function () {
		var $out = $( this ).siblings( '.omq-range__out' );
		var text = $out.text().replace( /^[-\d.]+/, '' );
		$out.text( $( this ).val() + text );
	} );

	/* ---------------------------------------------------------------
	 * Items repeater
	 * ------------------------------------------------------------- */
	var $items = $( '#omq-items' );

	function reindex() {
		$items.find( '.omq-item-row' ).each( function ( index ) {
			$( this ).attr( 'data-index', index );
			$( this ).find( 'input, select, textarea' ).each( function () {
				var name = $( this ).attr( 'name' );
				if ( ! name ) {
					return;
				}
				$( this ).attr( 'name', name.replace( /omq_items\[[^\]]*\]/, 'omq_items[' + index + ']' ) );
			} );
		} );
		$items.find( '.omq-items-empty' ).remove();
	}

	function itemTemplate( index ) {
		var html = $( '#tmpl-omq-item' ).html() || '';
		return html.replace( /\{\{INDEX\}\}/g, index );
	}

	function addRow( data ) {
		var index = $items.find( '.omq-item-row' ).length;
		var $row = $( itemTemplate( index ) );
		$items.append( $row );

		if ( data ) {
			if ( data.image_id ) {
				$row.find( '.omq-item-image-id' ).val( data.image_id );
				$row.find( '.omq-item-thumb' ).attr( 'src', data.thumb );
				$row.find( '.omq-item-media' ).addClass( 'has-image' );
			}
			if ( data.text ) {
				$row.find( 'input[name$="[text]"]' ).val( data.text );
			}
			if ( data.alt ) {
				$row.find( 'input[name$="[alt]"]' ).val( data.alt );
			}
			if ( data.kind ) {
				$row.find( '.omq-item-kind' ).val( data.kind );
			}
		}

		initColors( $row );
		reindex();
		schedulePreview();
		return $row;
	}

	$doc.on( 'click', '#omq-add-item', function ( e ) {
		e.preventDefault();
		addRow();
	} );

	$doc.on( 'click', '#omq-add-text', function ( e ) {
		e.preventDefault();
		var $row = addRow( { kind: 'text' } );
		$row.find( 'input[name$="[text]"]' ).trigger( 'focus' );
	} );

	$doc.on( 'click', '.omq-item-remove', function ( e ) {
		e.preventDefault();
		$( this ).closest( '.omq-item-row' ).slideUp( 120, function () {
			$( this ).remove();
			reindex();
			schedulePreview();
		} );
	} );

	var mediaFrame = null;

	$doc.on( 'click', '.omq-pick-image', function ( e ) {
		e.preventDefault();
		var $row = $( this ).closest( '.omq-item-row' );

		var frame = wp.media( {
			title: omqAdmin.i18n.chooseImage,
			button: { text: omqAdmin.i18n.useImage },
			multiple: false,
			library: { type: 'image' }
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			var thumb = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
			$row.find( '.omq-item-image-id' ).val( attachment.id );
			$row.find( '.omq-item-thumb' ).attr( 'src', thumb );
			$row.find( '.omq-item-media' ).addClass( 'has-image' );
			$row.find( '.omq-item-kind' ).val( 'image' );
			if ( ! $row.find( 'input[name$="[alt]"]' ).val() ) {
				$row.find( 'input[name$="[alt]"]' ).val( attachment.alt || attachment.title || '' );
			}
			schedulePreview();
		} );

		frame.open();
	} );

	$doc.on( 'click', '.omq-clear-image', function ( e ) {
		e.preventDefault();
		var $row = $( this ).closest( '.omq-item-row' );
		$row.find( '.omq-item-image-id' ).val( '' );
		$row.find( '.omq-item-media' ).removeClass( 'has-image' );
		$row.find( '.omq-item-thumb' ).attr( 'src', $row.find( '.omq-item-thumb' ).data( 'placeholder' ) || '' );
		schedulePreview();
	} );

	$doc.on( 'click', '#omq-add-images', function ( e ) {
		e.preventDefault();

		if ( mediaFrame ) {
			mediaFrame.open();
			return;
		}

		mediaFrame = wp.media( {
			title: omqAdmin.i18n.chooseImage,
			button: { text: omqAdmin.i18n.useImage },
			multiple: 'add',
			library: { type: 'image' }
		} );

		mediaFrame.on( 'select', function () {
			mediaFrame.state().get( 'selection' ).map( function ( item ) {
				var attachment = item.toJSON();
				addRow( {
					kind: 'image',
					image_id: attachment.id,
					thumb: attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url,
					alt: attachment.alt || attachment.title || '',
					text: attachment.title || ''
				} );
			} );
		} );

		mediaFrame.open();
	} );

	if ( $items.length && $.fn.sortable ) {
		$items.sortable( {
			handle: '.omq-item-handle',
			axis: 'y',
			placeholder: 'omq-item-placeholder',
			forcePlaceholderSize: true,
			update: function () {
				reindex();
				schedulePreview();
			}
		} );
	}

	/* ---------------------------------------------------------------
	 * Live preview
	 * ------------------------------------------------------------- */
	var previewTimer = null;
	var previewXhr = null;

	function collectData() {
		var settings = {};
		var items = {};

		$( '.omq-builder' ).find( 'input, select, textarea' ).each( function () {
			var $el = $( this );
			var name = $el.attr( 'name' );
			if ( ! name ) {
				return;
			}
			if ( $el.is( ':checkbox' ) && ! $el.is( ':checked' ) ) {
				return;
			}
			if ( $el.is( ':radio' ) && ! $el.is( ':checked' ) ) {
				return;
			}

			var settingMatch = name.match( /^omq_settings\[([^\]]+)\]$/ );
			if ( settingMatch ) {
				settings[ settingMatch[ 1 ] ] = $el.val();
				return;
			}

			var itemMatch = name.match( /^omq_items\[([^\]]+)\]\[([^\]]+)\]$/ );
			if ( itemMatch ) {
				var index = itemMatch[ 1 ];
				items[ index ] = items[ index ] || {};
				items[ index ][ itemMatch[ 2 ] ] = $el.val();
			}
		} );

		var list = [];
		Object.keys( items )
			.sort( function ( a, b ) {
				return parseInt( a, 10 ) - parseInt( b, 10 );
			} )
			.forEach( function ( key ) {
				list.push( items[ key ] );
			} );

		return { settings: settings, items: list };
	}

	function renderPreview() {
		var $stage = $( '#omq-preview' );
		if ( ! $stage.length ) {
			return;
		}

		var data = collectData();
		$( '#omq-preview-spinner' ).addClass( 'is-active' );

		if ( previewXhr ) {
			previewXhr.abort();
		}

		previewXhr = $.post(
			omqAdmin.ajaxUrl,
			{
				action: 'omq_preview',
				nonce: omqAdmin.nonce,
				settings: data.settings,
				items: data.items
			},
			function ( response ) {
				$( '#omq-preview-spinner' ).removeClass( 'is-active' );
				if ( ! response || ! response.success ) {
					return;
				}
				$stage.find( '.omq' ).each( function () {
					if ( window.OfnoaMarquee ) {
						window.OfnoaMarquee.destroy( this );
					}
				} );
				$stage.html( response.data.html );
				if ( window.OfnoaMarquee ) {
					window.OfnoaMarquee.initAll( $stage.get( 0 ) );
				}
			}
		).fail( function () {
			$( '#omq-preview-spinner' ).removeClass( 'is-active' );
		} );
	}

	function schedulePreview() {
		if ( ! $( '#omq-auto-preview' ).is( ':checked' ) ) {
			return;
		}
		window.clearTimeout( previewTimer );
		previewTimer = window.setTimeout( renderPreview, 450 );
	}

	$doc.on( 'click', '#omq-refresh-preview', function ( e ) {
		e.preventDefault();
		renderPreview();
	} );

	$doc.on( 'change input', '.omq-builder input, .omq-builder select, .omq-builder textarea', function () {
		applyConditions();
		schedulePreview();
	} );

	$doc.on( 'click', '.omq-device', function () {
		$( '.omq-device' ).removeClass( 'is-active' );
		$( this ).addClass( 'is-active' );
		$( '#omq-preview' ).css( 'width', $( this ).data( 'width' ) );
		window.setTimeout( function () {
			if ( window.OfnoaMarquee ) {
				window.OfnoaMarquee.refreshAll();
			}
		}, 220 );
	} );

	/* ---------------------------------------------------------------
	 * Boot
	 * ------------------------------------------------------------- */
	$( function () {
		initColors();
		applyConditions();
		reindex();
		renderPreview();
	} );
} )( jQuery );
