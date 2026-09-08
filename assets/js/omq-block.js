/*!
 * Ofnoa Marquee — Gutenberg block (no build step, plain wp.element).
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = ( wp.blockEditor || wp.editor ).InspectorControls;
	var useBlockProps = ( wp.blockEditor || wp.editor ).useBlockProps;
	var components = wp.components;
	var ServerSideRender = wp.serverSideRender;

	registerBlockType( 'ofnoa/marquee', {
		title: __( 'Ofnoa Marquee', 'ofnoa-marquee' ),
		description: __( 'Logo & text ticker with full styling control.', 'ofnoa-marquee' ),
		icon: 'controls-repeat',
		category: 'widgets',
		keywords: [ 'marquee', 'ticker', 'logos', 'carousel', 'slider' ],
		supports: { align: [ 'wide', 'full' ], anchor: true },

		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps ? useBlockProps() : {};

			var inspector = el(
				InspectorControls,
				{},
				el(
					components.PanelBody,
					{ title: __( 'Marquee', 'ofnoa-marquee' ), initialOpen: true },
					el( components.SelectControl, {
						label: __( 'Select marquee', 'ofnoa-marquee' ),
						value: attributes.marqueeId,
						options: window.omqBlock ? window.omqBlock.options : [],
						onChange: function ( value ) {
							setAttributes( { marqueeId: parseInt( value, 10 ) || 0 } );
						}
					} ),
					el(
						components.ExternalLink,
						{ href: window.omqBlock ? window.omqBlock.newUrl : '#' },
						__( 'Create a new marquee', 'ofnoa-marquee' )
					)
				),
				el(
					components.PanelBody,
					{ title: __( 'Overrides (optional)', 'ofnoa-marquee' ), initialOpen: false },
					el( components.RangeControl, {
						label: __( 'Speed (px/s) — 0 keeps the saved value', 'ofnoa-marquee' ),
						value: attributes.speed,
						min: 0,
						max: 400,
						onChange: function ( value ) {
							setAttributes( { speed: value || 0 } );
						}
					} ),
					el( components.SelectControl, {
						label: __( 'Direction', 'ofnoa-marquee' ),
						value: attributes.direction,
						options: [
							{ label: __( 'Use saved value', 'ofnoa-marquee' ), value: '' },
							{ label: __( 'Right to left', 'ofnoa-marquee' ), value: 'left' },
							{ label: __( 'Left to right', 'ofnoa-marquee' ), value: 'right' },
							{ label: __( 'Bottom to top', 'ofnoa-marquee' ), value: 'up' },
							{ label: __( 'Top to bottom', 'ofnoa-marquee' ), value: 'down' }
						],
						onChange: function ( value ) {
							setAttributes( { direction: value } );
						}
					} )
				)
			);

			var body;

			if ( ! attributes.marqueeId ) {
				body = el(
					components.Placeholder,
					{
						icon: 'controls-repeat',
						label: __( 'Ofnoa Marquee', 'ofnoa-marquee' ),
						instructions: __( 'Choose which marquee to display.', 'ofnoa-marquee' )
					},
					el( components.SelectControl, {
						value: attributes.marqueeId,
						options: window.omqBlock ? window.omqBlock.options : [],
						onChange: function ( value ) {
							setAttributes( { marqueeId: parseInt( value, 10 ) || 0 } );
						}
					} )
				);
			} else {
				body = el( ServerSideRender, {
					block: 'ofnoa/marquee',
					attributes: attributes
				} );
			}

			return el( 'div', blockProps, inspector, body );
		},

		save: function () {
			return null;
		}
	} );
} )( window.wp );
