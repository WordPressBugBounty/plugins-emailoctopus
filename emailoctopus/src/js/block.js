import { PanelBody, PanelRow, SelectControl, Icon, Placeholder } from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { ReactComponent as BlockIconComponent } from '../../public/images/icon-monochrome.svg';
import '../scss/block.scss';
import metadata from '../json/block.json';

import BlockPreview from './block-preview';

const blockIcon = () => <Icon icon={ BlockIconComponent } className="emailoctopus-block-icon" />;

wp.blocks.registerBlockType(
    'emailoctopus/form-block',
    {
        icon: blockIcon,
        edit: (props) => {
            const setForm = ( form_id ) => {
                props.setAttributes( { form_id: form_id } );
            }
            const escapeHtml = ( possibleHtml ) => {
                // Uses the browser's built-in ability to escape HTML by
                // putting the text inside an <option> element.
                return new Option( possibleHtml ).innerHTML;
            }
            let blockBody = '';
            let helperLinks = '';
            let formName = '';
            let formType = '';
            let formTypeFriendly = '';
            let placeholderProps = {
                icon: blockIcon,
                label: metadata.title,
            };
            // Not connected to API.
            if ( ! emailoctopus_form.is_connected ) {
                blockBody = (
                    <div dangerouslySetInnerHTML={{ __html: emailoctopus_form.labels.api_connection_required }}/>
                );
            } else { // Connected to API.
                if ( ! props.attributes.form_id ) {
                    blockBody = (
                        <div>{ emailoctopus_form.labels.add_form }</div>
                    );
                } else {
                    if ( emailoctopus_form.forms.length ) {
                        for ( let form of emailoctopus_form.forms ) {
                            if ( props.attributes.form_id === form.value ) {
                                formName = form.name;
                                formType = form.type;
                                formTypeFriendly = form.type_friendly;
                            }
                        }
                    }
                    if ( [ 'bar', 'slide-in', 'modal' ].includes( formType ) ) {
                        blockBody = (
                            <div>
                                <strong>{ emailoctopus_form.labels.preview_form_selection.replace( '%1$s', escapeHtml(formName) ).replace( '%2$s', escapeHtml(formTypeFriendly) ) }</strong><br/>
                                { emailoctopus_form.labels.preview_form_view }
                            </div>
                        );
                    } else if ( !formType ) {
                        blockBody = (
                            <div>{ emailoctopus_form.labels.error_form_not_found.replace( '%s', escapeHtml( props.attributes.form_id )) }</div>
                        );
                    } else {
                        // Reset `placeholderProps` as we don't need the logo and the label in this case.
                        placeholderProps = {};
                        blockBody = (
                            <BlockPreview
                                shortcode={ '[emailoctopus form_id="' + escapeHtml( props.attributes.form_id ) + '"]' }
                                parentSelected={ props.isSelected }
                                sharedInstanceId={ escapeHtml( props.attributes.form_id ) }
                            />
                        );
                    }
                }
                // Add "no forms yet" link if there are no forms.
                if ( emailoctopus_form.forms.length <= 1 ) {
                    helperLinks = (
                        <div dangerouslySetInnerHTML={{ __html: emailoctopus_form.labels.no_forms }}/>
                    );
                } else {
                    if ( formType !== 'not-found' && props.attributes.form_id ) {
                        helperLinks = (
                            <div>
                                <a target="_blank" href={ emailoctopus_form.form_url_base + '&form-id=' + props.attributes.form_id }>{ emailoctopus_form.labels.display_settings }</a>
                                <br/>
                                <a target="_blank" rel="noopener" href={ 'https://emailoctopus.com/forms/embedded/' + props.attributes.form_id + '/design' }>{ emailoctopus_form.labels.view_on }</a>
                            </div>
                        );
                    }
                }
            }
            return (
                <div { ...useBlockProps() } data-form-type={ formType }>
                    <Placeholder { ...placeholderProps }>
                        { blockBody }
                    </Placeholder>
                    { emailoctopus_form.is_connected && <InspectorControls>
                        <PanelBody title={ emailoctopus_form.labels.tab_label } initialOpen={ true }>
                            { emailoctopus_form.forms.length > 0 &&
                                <PanelRow>
                                    <SelectControl
                                        label={ emailoctopus_form.labels.select_label }
                                        value={ escapeHtml( props.attributes.form_id ) }
                                        options={ emailoctopus_form.forms }
                                        onChange={ ( form_id ) => setForm( form_id ) }
                                        __nextHasNoMarginBottom
                                    />
                                </PanelRow>
                            }
                            <PanelRow>
                                { helperLinks }
                            </PanelRow>
                        </PanelBody>
                    </InspectorControls> }
                </div>
            );
        },
        save: () => {
            return null;
        },
    }
);
