( function( blocks, element, blockEditor, components, i18n, serverSideRender ) {
	var el = element.createElement;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var SelectControl = components.SelectControl;
	var __ = i18n.__;

	blocks.registerBlockType( 'wbccp/community-calendar', {
		edit: function( props ) {
			var attrs = props.attributes;
			var setAttributes = props.setAttributes;

			var preview = serverSideRender
				? el( serverSideRender, {
					block: 'wbccp/community-calendar',
					attributes: attrs
				} )
				: el( 'div', { className: 'wbccp-block-preview' }, __( 'Calendar preview will appear on the frontend.', 'wb-community-calendar-pro' ) );

			return [
				el( InspectorControls, { key: 'inspector' },
					el( PanelBody, { title: __( 'Calendar Settings', 'wb-community-calendar-pro' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Group ID (optional)', 'wb-community-calendar-pro' ),
							value: attrs.groupId || '',
							onChange: function( value ) {
								setAttributes( { groupId: value ? parseInt( value, 10 ) : 0 } );
							}
						} ),
						el( TextControl, {
							label: __( 'List Limit', 'wb-community-calendar-pro' ),
							value: attrs.limit || 10,
							onChange: function( value ) {
								setAttributes( { limit: value ? parseInt( value, 10 ) : 10 } );
							}
						} ),
						el( SelectControl, {
							label: __( 'Default View', 'wb-community-calendar-pro' ),
							value: attrs.view || 'list',
							options: [
								{ label: __( 'Agenda (List)', 'wb-community-calendar-pro' ), value: 'list' },
								{ label: __( 'Calendar (Month)', 'wb-community-calendar-pro' ), value: 'month' }
							],
							onChange: function( value ) {
								setAttributes( { view: value } );
							}
						} )
					)
				),
				el( 'div', { className: 'wbccp-block-preview-wrap' },
					preview
				)
			];
		},
		save: function() {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n, window.wp.serverSideRender );
