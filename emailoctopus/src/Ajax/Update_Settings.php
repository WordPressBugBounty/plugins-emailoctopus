<?php

namespace EmailOctopus\Ajax;

use EmailOctopus\Utils;

class Update_Settings extends Ajax_Handler
{
    public string $nonce_name = 'emailoctopus_save_api_key';

    public string $action_name = 'emailoctopus_update_settings';

    public function process_request(): void
    {
        parent::validate_nonce();

        $current_api_key = get_option('emailoctopus_api_key');
        $new_api_key = $_REQUEST['api_key'];
        $is_valid_api_key = Utils::is_valid_api_key($new_api_key, true);

        if (
            $new_api_key === Utils::mask_api_key($current_api_key) &&
            Utils::is_valid_api_key($current_api_key, true)
        ) {
            // No change
            $data = [
                'errors' => false,
                'success' => true,
                'message' => 'Settings saved.',
                'api_key_masked' => Utils::mask_api_key($new_api_key),
            ];
        } else {
            $is_valid_api_key = Utils::is_valid_api_key($new_api_key, true);
            if ($is_valid_api_key === true) {
                update_option('emailoctopus_api_key', $new_api_key);
                Utils::clear_transients();

                if ($current_api_key) {
                    $message = __(
                        sprintf(
                            'API key updated. You can now configure your <a href="%s">forms</a>.',
                            admin_url('admin.php?page=emailoctopus-forms')
                        ),
                        'emailoctopus'
                    );
                } else {
                    $message = __(
                        sprintf(
                            'API key connected. You can now configure your <a href="%s">forms</a>.',
                            admin_url('admin.php?page=emailoctopus-forms')
                        ),
                        'emailoctopus'
                    );
                }

                $data = [
                    'success' => true,
                    'message' => $message,
                    'api_key_masked' => Utils::mask_api_key($new_api_key),
                ];
            } elseif ($is_valid_api_key === false) {
                $data = [
                    'success' => false,
                    'message' => __('Your API key is invalid.', 'emailoctopus'),
                ];
            } else {
                $data = [
                    'success' => false,
                    'message' => __('Could not establish a connection with the EmailOctopus API.', 'emailoctopus'),
                ];
            }
        }

        wp_send_json($data);
    }
}
