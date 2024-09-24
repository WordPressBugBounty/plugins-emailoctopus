/**
 * Admin Page: Single Form Display Settings.
 */
(function ( $, l10n ) {

    const $automaticDisplay   = $( document.getElementById( 'emailoctopus-form-automatic-display' ) );
    const $postTypesContainer = $( document.getElementById( 'emailoctopus-form-post-types-container' ) );

    $automaticDisplay.on( 'change', function() {
        if ( $automaticDisplay.val() !== 'none' ) {
            $postTypesContainer.show();
        } else {
            $postTypesContainer.hide();
        }
    });

    $automaticDisplay.trigger( 'change' );

    // Expand sidebar menu – this doesn't happen automatically as we're on a
    // child page not listed there
    // TODO: Could this be done in PHP using a WordPress hook?
    $('li#toplevel_page_emailoctopus-forms')
        .removeClass('wp-not-current-submenu')
        .addClass('wp-has-current-submenu wp-menu-open');
    $('li#toplevel_page_emailoctopus-forms .wp-first-item')
        .addClass('current');
    $('li#toplevel_page_emailoctopus-forms .wp-first-item a')
        .addClass('current')
        .attr('aria-current', 'page');

})( window.jQuery, window.emailOctopusL10n );
