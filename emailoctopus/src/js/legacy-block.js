(function ( blocks, blockEditor, i18n, element ) {
    var __ = i18n.__;

    window.wp.blocks.registerBlockType(
        'emailoctopus/form',
        {
            icon: element.createElement( 'img', { src: window.emailoctopus_block.svg, class: 'emailoctopus-block-icon' } ),
            edit: function () {
                var blockProps = blockEditor.useBlockProps();
                return element.createElement(
                    'div',
                    blockProps,
                    __( 'This is a legacy EmailOctopus block. Use the new "EmailOctopus Form" block instead.' )
                );
            },
            save: () => {
                return null;
            },
        }
    );

}( window.wp.blocks, window.wp.blockEditor, window.wp.i18n, window.wp.element ));
