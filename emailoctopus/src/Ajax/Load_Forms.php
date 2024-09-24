<?php

namespace EmailOctopus\Ajax;

use EmailOctopus\Utils;

class Load_Forms extends Ajax_Handler
{
    public string $nonce_name = 'emailoctopus_load_forms';

    public string $action_name = 'emailoctopus_load_forms';

    /**
     * Gets forms from EmailOctopus.
     */
    public function process_request(): void
    {
        parent::validate_nonce();

        $data = Utils::get_forms();

        if ($data === null || isset($data->error)) {
            wp_send_json_error($data);
        } else {
            wp_send_json_success($data);
        }
    }
}
