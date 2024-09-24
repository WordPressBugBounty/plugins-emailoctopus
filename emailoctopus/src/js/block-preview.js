/**
 * Inspired by https://github.com/CODESIGN2/gutenberg-shortcode-preview-block
 */

import { Spinner, SandBox } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';

class BlockPreview extends Component {
    constructor(props) {
        super(props);
        this.state = {
            shortcode: '',
            response: {},
            form_id: this.props.sharedInstanceId,
        };
    }

    updateFormData() {
        const { shortcode } = this.props;
        const myURL = new URL(window.location.href);
        const apiURL = addQueryArgs(
            wpApiSettings.root + 'emailoctopus/v1/preview-shortcode',
            {
                shortcode,
                _wpnonce: wpApiSettings.nonce,
                postId: myURL.searchParams.get('post'),
            }
        );
        window
            .fetch(apiURL, {
                credentials: 'include',
            })
            .then((response) => {
                response
                    .json()
                    .then((data) => ({
                        data,
                        status: response.status,
                    }))
                    .then((res) => {
                        if (res.status === 200) {
                            this.setState({ response: res });
                        }
                    })
                    .catch(() => {
                        const res = {
                            data: {
                                html: __('A server error occurred.', 'emailoctopus' ),
                                js: '',
                                style: '',
                            },
                        };
                        this.setState({ response: res, form_id: this.props.sharedInstanceId });
                    });
            });
    }

    componentDidMount() {
        this.updateFormData();
    }

    render() {
        if ( this.state.form_id !== this.props.sharedInstanceId ) {
            this.setState({ response: false });
            this.setState({ form_id: this.props.sharedInstanceId });
            this.updateFormData();
        }
        const { parentSelected, sharedInstanceId } = this.props;
        const response = this.state.response;
        if (response.isLoading || !response.data) {
            return (
                <div className="wp-block-embed is-loading">
                    <Spinner />
                </div>
            );
        }

        /*
         * order must match rest controller style is wp_head, html is shortcode, js is footer
         * should really be named better
         */
        const html =
            response.data.style +
            ' ' +
            response.data.html +
            ' ' +
            response.data.js;
        const output = [
            <SandBox
                html={html}
                title={__( 'EmailOctopus Form Preview', 'emailoctopus' )}
                type="embed"
                key={`cd2-shortcode-block-preview-${sharedInstanceId}`}
            />,
        ];

        if (!parentSelected) {
            /*
                An overlay is added when the block is not selected in order to register click events.
                Some browsers do not bubble up the clicks from the sandboxed iframe, which makes it
                difficult to reselect the block.
            */
            output.push(
                <div
                    className="sandbox-preview-overlay"
                    key={`cd2-shortcode-block-preview-interaction-blocker-${sharedInstanceId}`}
                ></div>
            );
        }

        return output;
    }
}

export default BlockPreview;
