/**
 * Forms list page.
 */
(function ( $, l10n, navigator ) {
    function escapeHtml(possibleHtml) {
        // Uses the browser's built-in ability to escape HTML by
        // putting the text inside an <option> element.
        return new Option(possibleHtml).innerHTML;
    }

    function translateType(type) {
        switch (type) {
            case 'bar':
                return l10n.formsTypeBar;
            case 'inline':
                return l10n.formsTypeInline;
            case 'modal':
                return l10n.formsTypeModal;
            case 'slide-in':
                return l10n.formsTypeSlideIn;
            case null:
                return l10n.formsTypeUnknown;
        }

        return escapeHtml(type);
    }

    function translateAutomaticDisplay(automaticDisplay) {
        switch (automaticDisplay) {
            case 'top':
                return l10n.formsAutomaticDisplayTop;
            case 'bottom':
                return l10n.formsAutomaticDisplayBottom
            case 'non_inline':
                return l10n.formsAutomaticDisplayNonInline;
            case 'none':
            default:
                return l10n.formsAutomaticDisplayNone;
        }
    }

    /**
     * Render the general forms list (a WP List Table).
     */
    $( window ).on( 'emailoctopus-load-forms', function () {
        const $table     = $( document.getElementById( 'emailoctopus-forms' ) );
        const $formsList = $table.find( 'tbody' );

        if ( !$formsList.length ) {
            return;
        }

        const $loadForms = $.when( $.ajax(
          ajaxurl,
          {
              type: 'POST',
              data: {
                  action: 'emailoctopus_load_forms',
                  _ajax_nonce: $( document.getElementById( '_eo_nonce' ) ).val()
              }
          }
        ) );

        $loadForms.then( function ( response ) {
            if ( typeof response.success === 'undefined' || !response.success ) {
                console.error( response );
                $formsList.empty();
                $formsList.append( $( `<tr><td class="emailoctopus-forms-table-message" colspan="4"><div><p>${l10n.formsError}</p></div></td></tr>` ) );

                return;
            }

            if (response.data.length) {
                $formsList.empty();

                $.each( response.data, function ( i, form ) {
                    const formUrl = `${l10n.formUrlBase}&form-id=${form.id}`
                    let $form = $( `
                        <tr data-form-id="${form.id}" class="emailoctopus-form-row">
                            <td class="emailoctopus-form-row-overview">
                                <img src="${form.screenshot_url}"
                                    alt="Screenshot of ${escapeHtml(form.name)} form"
                                    tabindex="-1"
                                    width="90"
                                    height="90"
                                >
                                <div>
                                    <strong>${form.name ? escapeHtml(form.name) : l10n.formsUntitled } (${translateType(form.type).toLowerCase()})</strong>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="${formUrl}" aria-label="Edit ${escapeHtml(form.name)} form">Display settings</a> |
                                            <a href="https://emailoctopus.com/forms/embedded/${form.id}/design" target="_blank" rel="noopener" aria-label="View on EmailOctopus">View on EmailOctopus</a>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <a href="https://emailoctopus.com/lists/${form.list_id}" target="_blank" rel="noopener">
                                        ${form.list_name ? escapeHtml(form.list_name) : l10n.formsUntitled }
                                    </a>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <a href="${formUrl}">${translateAutomaticDisplay(form.automatic_display)}</a>
                                </div>
                            </td>
                        </tr>
                    ` );

                    $form.appendTo( $formsList );
                } );
            } else {
                $('.emailoctopus-forms-loading').hide();
                $('.emailoctopus-forms-none').show();
            }
        } );
    } );

})( window.jQuery, window.emailOctopusL10n, window.navigator );
