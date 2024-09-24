/**
 * Settings page.
 */
(function( $, l10n ) {

    function clearNotices() {
        $( '.emailoctopus-notice' ).remove();
    }

    function addNotice( type, message ) {
        $( '.emailoctopus-api-key.wrap' )
            .after(
                '<div class="emailoctopus-notice notice notice-' + type + '"><p>' + message + '</p></div>'
            )
    }

    // Hide notices when user enters text.
    $( document.getElementById( 'emailoctopus-api-key-input' ) ).on( 'input', function() {
        clearNotices();

        $( document.getElementById( 'emailoctopus-api-key-input' ) ).removeClass( 'emailoctopus-invalid' );
    } );

    // API Key form submission: perform sanity checks and submit API key via AJAX.
    $( document.getElementById( 'api_key_form' ) ).on( 'submit', function ( e ) {
        e.preventDefault();

        clearNotices();

        const api_key = $.trim( $( document.getElementById( 'emailoctopus-api-key-input' ) ).val() );

        if ( api_key === '' ) {
            addNotice( 'error', l10n.apiKeyEmpty );

            $( document.getElementById( 'emailoctopus-api-key-input' ) ).addClass( 'emailoctopus-invalid' );

            return;
        }

        $( document.getElementById( 'emailoctopus-settings-save' ) ).prop( 'disabled', 'disabled' ).val( l10n.apiCheckSaving );

        $.ajax(
            ajaxurl,
            {
                type: 'POST',
                data: {
                    action: 'emailoctopus_update_settings',
                    _ajax_nonce: $( document.getElementById( '_eo_nonce' ) ).val(),
                    api_key: api_key,
                }
            }
        ).done( function( data ) {
            $( document.getElementById( 'emailoctopus-settings-save' ) ).prop( 'disabled', false ).val( l10n.apiCheckSave );

            if ( data.success ) {
                addNotice( 'success', data.message );

                $( '.emailoctopus-api-key-status-container-connected' ).show();
                $( '.emailoctopus-api-key-status-container-not-connected' ).hide();

                $( document.getElementById( 'emailoctopus-api-key-input' ) )
                    .val(data.api_key_masked);

                var url_params = wpAjax.unserialize( window.location.href );

                if ( url_params.page === 'emailoctopus' ) {
                    window.location.href = window.location.href;
                }
            } else {
                $( '.emailoctopus-api-key-status-container-connected' ).hide();
                $( '.emailoctopus-api-key-status-container-not-connected' ).show();

                $( document.getElementById( 'emailoctopus-api-key-input' ) ).addClass( 'emailoctopus-invalid' );

                addNotice( 'error', data.message );
            }
        } );
    } );

})( window.jQuery, window.emailOctopusL10n );
