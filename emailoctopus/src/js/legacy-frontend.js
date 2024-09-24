jQuery( document ).ready( function ( $ ) {

    $( '.emailoctopus-form' ).on( 'submit', function ( event ) {
        event.preventDefault();

        var $form   = $( this );
        var form_id = $form.find( 'input[name="emailoctopus_form_id"]' ).val();

        // Check consent box
        if ( $form.find( '.emailoctopus-consent' ).length > 0 ) {
            var consent_checked = $form.find( '.emailoctopus-consent' ).is( ':checked' );

            if ( ! consent_checked ) {
                var consent_content = $form.find( 'textarea[name="message_consent_required"]' ).val();
                $form.find( '.emailoctopus__error-message' ).html( consent_content ).removeClass( 'emailoctopus-fade-out emailoctopus-fade-in' ).addClass( 'emailoctopus-fade-in' );
                return;
            }
        }

        // Check email box
        var email = $.trim( $form.find( 'input[name="EmailAddress"]' ).val() );

        if ( email == '' ) {
            var email_content = $form.find( 'textarea[name="message_missing_email"]' ).val();
            $form.find( '.emailoctopus__error-message' ).html( email_content ).removeClass( 'emailoctopus-fade-out emailoctopus-fade-in' ).addClass( 'emailoctopus-fade-in' );
            return;
        }

        // Check bot box
        var bot = $form.find( '.emailoctopus-form-row-hp input' ).val();

        if ( bot ) {
            var bot_content = $form.find( 'textarea[name="message_bot"]' ).val();
            $form.find( '.emailoctopus__error-message' ).html( bot_content ).removeClass( 'emailoctopus-fade-out emailoctopus-fade-in' ).addClass( 'emailoctopus-fade-in' );
            return;
        }

        var $button     = $form.find( 'button' );
        var button_text = $button.html();

        var list_id = $form.find( 'input[name="emailoctopus_list_id"]' ).val();

        // No more errors, let's submit
        $button.html( emailoctopus.sending ).prop( 'disabled', 'disabled' );

        var form_options = $form.find( '.emailoctopus-custom-fields' ).serializeArray();

        $.ajax( {
            type: "POST",
            url: emailoctopus.ajaxurl,
            data: {
                action: 'submit_frontend_form',
                form_data: form_options,
                list_id: list_id
            },
            success: function ( response ) {
                if ( response.errors ) {
                    var message = response.message;

                    if ( $form.find( 'textarea[name="' + message + '"]' ).length > 0 ) {
                        var content = $form.find( 'textarea[name="' + message + '"]' ).val();
                        $form.find( '.emailoctopus__error-message' ).html( content ).removeClass( 'emailoctopus-fade-out emailoctopus-fade-in' ).addClass( 'emailoctopus-fade-in' );
                    } else {
                        $form.find( '.emailoctopus__error-message' ).html( message ).removeClass( 'emailoctopus-fade-out emailoctopus-fade-in' ).addClass( 'emailoctopus-fade-in' );
                    }

                    $button.html( button_text ).prop( 'disabled', false );
                } else {
                    var content = $form.find( 'textarea[name="' + response.message + '"]' ).val();

                    $form.find( '.emailoctopus__success-message' ).html( content ).removeClass( 'emailoctopus-fade-out emailoctopus-fade-in' ).addClass( 'emailoctopus-fade-in' );
                    $form.find( '.emailoctopus-form-copy-wrapper' ).addClass( 'emailoctopus-fade-out' );
                    $form.find( '.emailoctopus__error-message' ).addClass( 'emailoctopus-fade-out' );

                    // Find redirect
                    if ( $form.find( 'input:hidden[name=successRedirectUrl]' ).length > 0 ) {
                        window.location.href = $form.find( 'input:hidden[name=successRedirectUrl]' ).val();
                    }
                }
            },
            dataType: 'json'
        } );

    } );

} );
