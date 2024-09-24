<?php

namespace EmailOctopus;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lists in EmailOctopus (cannot be named List, as that's a reserved word in PHP for the `list` function).
 */
class ContactList
{
    /**
     * The EmailOctopus list ID.
     */
    private string $list_id = '';

    /**
     * List data as received from EmailOctopus.com API.
     */
    private array $list_data = [];

    /**
     * Errors.
     */
    private array $errors = [];

    /**
     * Constructor.
     *
     * @param string $list_id The list ID.
     */
    public function __construct(string $list_id = '')
    {
        if (empty($list_id)) {
            $this->errors[] = esc_html__('Invalid list ID', 'emailoctopus');

            return;
        }

        $this->list_id = $list_id;
        $this->set_list_data();
    }

    /**
     * Sets Form Data available to other class methods.
     */
    private function set_list_data(): void
    {
        $data = Utils::get_api_data(
            'https://emailoctopus.com/api/1.6/lists/' . $this->list_id,
            ['api_key' => get_option('emailoctopus_api_key', false)],
            10 * MINUTE_IN_SECONDS
        );

        $this->list_data = (array) $data; // This must be an associative array, not stdClass
    }

    /**
     * Accessor for list data.
     */
    public function get_list_data(): array
    {
        return $this->list_data ?? [];
    }

    /**
     * Get the list's Name as defined on EmailOctopus.com.
     */
    public function get_name(): string
    {
        return trim($this->get_list_data()['name'] ?? '');
    }

    /**
     * Get the form's Type as defined on EmailOctopus.com.
     */
    public function get_id(): string
    {
        return trim($this->get_list_data()['id'] ?? '');
    }
}
