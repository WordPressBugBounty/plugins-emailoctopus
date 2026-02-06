<?php

/**
 * Utility class.
 */

namespace EmailOctopus;

// Exit if accessed directly.
use WP_Post_Type;
use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

class Utils
{
    /**
     * Checks for a valid API key.
     *
     * @param string $api_key       The API key to check.
     * @param bool   $force_refresh Whether to force a refresh of the cache.
     *
     * @return bool Whether the API key is valid or not. NULL indicates we could
     *              not establish the result, e.g. could not connect to the API.
     */
    public static function is_valid_api_key($api_key, bool $force_refresh = false): ?bool
    {
        if (!$api_key) {
            return false;
        }

        $data = self::get_api_data(
            'https://emailoctopus.com/api/1.6/account',
            [
                'api_key' => $api_key,
            ],
            HOUR_IN_SECONDS,
            $force_refresh
        );

        if (is_wp_error($data)) {
            return null;
        }

        return !is_null($data) && !isset($data->error);
    }

    public static function mask_api_key(string $api_key): string
    {
        return '********-****-****-****-********' . substr($api_key, -4);
    }

    public static function get_forms(): ?array
    {
        $api_key = get_option('emailoctopus_api_key', false);

        if (empty($api_key)) {
            return [];
        }

        $data = self::get_api_data(
            'https://emailoctopus.com/api/1.6/forms',
            ['api_key' => get_option('emailoctopus_api_key', false)],
            MINUTE_IN_SECONDS
        );

        if (is_wp_error($data) || isset($data->error)) {
            return null;
        }

        if ($data) {
            return self::supplement_forms_api_data($data);
        }

        return [];
    }

    /**
     * Add extra data to the API response, such as the list name and the display
     * rule metadata.
     *
     * @param array $forms The forms to supplement.
     *
     * @return mixed
     */
    public static function supplement_forms_api_data(array $forms): array
    {
        foreach ($forms as $i => $apiData) {
            $formObj = new Form($apiData->id, false);
            $formObj->set_form_data(
                (array) $apiData // Cast from stdClass to array
            );

            $forms[$i]->list_name = $formObj->get_list_name();
            $forms[$i]->automatic_display = $formObj->get_form_post()->_emailoctopus_form_automatic_display;
        }

        return $forms;
    }

    /**
     * Returns a URL for the plugin icon.
     *
     * @return string URL
     */
    public static function get_icon_url(): string
    {
        return self::get_plugin_url('public/images/icon.svg');
    }

    /**
     * Returns a URL for the monochrome plugin icon.
     *
     * @return string URL
     */
    public static function get_icon_monochrome_url(): string
    {
        return self::get_plugin_url('public/images/icon-monochrome.svg');
    }

    /**
     * Returns the SVG code for the plugin icon.
     *
     * @return string SVG Code
     */
    public static function get_icon_data_uri(): string
    {
        return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQ2IiBoZWlnaHQ9IjMxMyIgdmlld0JveD0iMCAwIDI0NiAzMTMiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxwYXRoIGQ9Ik0yMzkuNzUgMTg5LjI1MUMyMzMuNzQ3IDE4NC45MzQgMjI1LjM3NyAxODYuMjk0IDIyMS4wNTUgMTkyLjI5MUMyMTcuNzYgMTk2Ljg2MSAyMTEuODgzIDIwMi4yMzggMjAyLjYgMjAxLjkwN0MxOTcuNTIgMjAxLjcyNSAxOTEuNjM3IDE5OS4yNTUgMTg3LjMzMyAxOTQuMjIxQzE4Ni4zODQgMTkzLjExMSAxODYuNDQ4IDE5MS40NTkgMTg3LjUwMSAxOTAuNDQ3QzIxMS4wNTQgMTY3LjgxNyAyMjQuNDA0IDEzNS4wNDQgMjIxLjAyMiAxMDAuMDI3QzIxNS4xNTMgMzkuMjY1IDE2MS4xMzUgLTUuMjU2MiAxMDAuMzIzIDAuNTAxMDFDMzkuOTY1OCA2LjIxNTEzIC00LjgxODQzIDU5Ljg0NTYgMC40MTU4OTMgMTIwLjE4MUMzLjkwMjUgMTYwLjM3MiAyOC40MzIxIDE5My41ODQgNjIuMTQ0NSAyMTAuMDc2QzYzLjI2MTEgMjEwLjYyMiA2My44Njc3IDIxMS44NjUgNjMuNjEwOCAyMTMuMDhDNjMuMDkyNSAyMTUuNTMyIDYyLjE4MSAyMTcuOTE0IDYwLjg2NzQgMjIwLjExN0M1OC40MzM5IDIyNC4xOTggNTIuNDg4MiAyMzEuMDM5IDQwLjAyIDIzMS4wMjFDMzIuNjE3MyAyMzEuMDA4IDI2LjYxNzEgMjM2Ljk4OSAyNi42MDUgMjQ0LjM3OEMyNi41OTI1IDI1MS43NjcgMzIuNTc4OSAyNTcuNzY2IDM5Ljk3NTggMjU3Ljc3OEM0MS45MTQgMjU3Ljc4MiA0My44MjA5IDI1Ny42OTIgNDUuNjk1MiAyNTcuNTExQzYyLjAwMjIgMjU1LjkzOSA3NS43MTg2IDI0Ny40OTkgODMuODgwOSAyMzMuODA5Qzg0Ljg1NzggMjMyLjE3MSA4NS43MzE0IDIzMC40ODYgODYuNTA0NCAyMjguNzY1Qzg2Ljc0OTEgMjI4LjIyMSA4Ny4zMzYyIDIyNy45MTYgODcuOTE5OCAyMjguMDQ1Qzg4LjU5MjUgMjI4LjE5MyA4OS4wMjUgMjI4Ljg1NyA4OC44Nzc1IDIyOS41MjlDODUuMTc4NiAyNDYuMzkgNzYuNjAwOSAyNjIuODk2IDY0LjU3ODcgMjc1LjIxNEM1OS41MTE0IDI4MC40MDcgNTkuMzA4NCAyODguNzY5IDY0LjM0MTUgMjkzLjk5NEM2Ny4zMDkxIDI5Ny4wNzUgNzEuMzY5OCAyOTguNDIgNzUuMjkzMSAyOTguMDQyQzc4LjMyMjIgMjk3Ljc1IDgxLjI2OTcgMjk2LjQzIDgzLjU2NjEgMjk0LjA4OUM5NS4wODA4IDI4Mi4zNSAxMDQuMzIzIDI2Ny41NTggMTEwLjI5MyAyNTEuMzExQzExMy4xMDIgMjQzLjY2NyAxMTUuMTI4IDIzNS44ODEgMTE2LjM0MyAyMjguMTUyQzExNi40MjYgMjI3LjYyIDExNi44NjYgMjI3LjIxNyAxMTcuNDA1IDIyNy4xODRDMTE3Ljk5NyAyMjcuMTQ5IDExOC41MjMgMjI3LjU2OSAxMTguNjEzIDIyOC4xNTRDMTIyLjA4NSAyNTAuNjk2IDExNy40OTkgMjcxLjQyOSAxMDQuNDg5IDI5Mi42MjdDMTAwLjYyMiAyOTguOTI2IDEwMi42MDEgMzA3LjE2MyAxMDguOTA2IDMxMS4wMjVDMTExLjQ4NSAzMTIuNjA0IDExNC4zODggMzEzLjIwNyAxMTcuMTgzIDMxMi45MzhDMTIxLjIyNCAzMTIuNTQ5IDEyNS4wNCAzMTAuMzM2IDEyNy4zMjUgMzA2LjYxMkMxNDMuMTAxIDI4MC45MDkgMTQ5LjEwMSAyNTQuNzM3IDE0NS41MDQgMjI3LjA0NkMxNDUuNDE1IDIyNi4zNjMgMTQ1Ljg2OCAyMjUuNzI4IDE0Ni41NDQgMjI1LjU5NkMxNDcuMTY0IDIyNS40NzUgMTQ3Ljc4NSAyMjUuODEyIDE0OC4wMSAyMjYuNDAxQzE1My43NjkgMjQxLjQ1MiAxNTYuNDgxIDI2Mi4xNDcgMTUxLjI4IDI4Mi45NDRDMTQ5LjQ4NiAyOTAuMTEyIDE1My44NTEgMjk3LjM3NiAxNjEuMDI3IDI5OS4xNjZDMTYyLjU1MiAyOTkuNTQ3IDE2NC4wOCAyOTkuNjUgMTY1LjU2IDI5OS41MDdDMTcxLjA0NSAyOTguOTc4IDE3NS44NTUgMjk1LjA3NSAxNzcuMjY3IDI4OS40MjlDMTgzLjM4OSAyNjQuOTUyIDE4MS4wMDYgMjQwLjY3OSAxNzQuNjMzIDIyMS40NzFDMTc0LjI3MSAyMjAuMzgxIDE3NS41MDUgMjE5LjQ2NiAxNzYuNDQ4IDIyMC4xMjJDMTgzLjgxNyAyMjUuMjQ5IDE5Mi41MDggMjI4LjMxOSAyMDEuNjM3IDIyOC42NDdDMjAzLjc3OCAyMjguNzI0IDIwNS44OTcgMjI4LjY1OSAyMDcuOTg2IDIyOC40NThDMjIxLjc0NSAyMjcuMTMyIDIzNC4xODcgMjE5Ljg2NSAyNDIuNzkzIDIwNy45MjVDMjQ3LjExNSAyMDEuOTI5IDI0NS43NTMgMTkzLjU2OCAyMzkuNzUgMTg5LjI1MVpNNzIuMzI4NCAxMzIuMDg3QzY0LjcxOTYgMTMyLjgyMSA1Ny45NTMzIDEyNy4yMjIgNTcuMjE1MyAxMTkuNTgyQzU2LjQ3NzQgMTExLjk0MyA2Mi4wNDcyIDEwNS4xNTUgNjkuNjU2IDEwNC40MjFDNzcuMjY0OCAxMDMuNjg4IDg0LjAzMTEgMTA5LjI4NyA4NC43NjkgMTE2LjkyNkM4NS41MDcgMTI0LjU2NiA3OS45MzcxIDEzMS4zNTQgNzIuMzI4NCAxMzIuMDg3Wk0xNjguNjEyIDEwOC44NDVDMTYxLjAwMyAxMDkuNTc5IDE1NC4yMzcgMTAzLjk4IDE1My40OTkgOTYuMzRDMTUyLjc2MSA4OC43MDAzIDE1OC4zMzEgODEuOTEyNiAxNjUuOTQgODEuMTc5MkMxNzMuNTQ4IDgwLjQ0NTggMTgwLjMxNSA4Ni4wNDQ1IDE4MS4wNTMgOTMuNjg0MkMxODEuNzkxIDEwMS4zMjQgMTc2LjIyMSAxMDguMTEyIDE2OC42MTIgMTA4Ljg0NVoiIC8+Cjwvc3ZnPgo=';
    }

    /**
     * Returns a URL for the EmailOctopus logo.
     *
     * @return string SVG Code
     */
    public static function get_logo_url(): string
    {
        return self::get_plugin_url('public/images/logo.svg');
    }

    /**
     * Look for existence of pre-3.0 DB tables.
     */
    public static function site_has_deprecated_forms(): bool
    {
        global $wpdb;

        foreach ($wpdb->get_col('SHOW TABLES', 0) as $table_name) {
            if (strpos($table_name, 'emailoctopus') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the web path to an asset based on a relative argument.
     *
     * @param string $path Relative path to the asset.
     *
     * @return string Web path to the relative asset.
     */
    public static function get_plugin_url(string $path = ''): string
    {
        $dir = rtrim(plugin_dir_url(EMAILOCTOPUS_FILE), '/');

        if (!empty($path) && is_string($path)) {
            $dir .= '/' . ltrim($path, '/');
        }

        return $dir;
    }

    /**
     * These taxonomies indicate which emailoctopus_form posts have "At top of {post type}" and "At bottom of {post type}" display rules.
     *
     * @return string[]|WP_Post_Type[]
     */
    public static function get_displayable_post_types(): array
    {
        $post_types = get_post_types(['public' => true], 'objects');

        // The `_eo_show_x_` prefix is 11 chars; given WP's 32-char limit for tax names, we can only proceed with taxes whose names <= 21 chars.
        $post_types = array_filter($post_types, function ($slug) {
            return mb_strlen($slug) <= 21;
        }, ARRAY_FILTER_USE_KEY);

        /*
         * Allows filtering what post types EmailOctopus forms can be display at top or bottom of. Defaults to all public post types.
         *
         * @param string[]|WP_Post_Type[] $post_types
         */
        return apply_filters('emailoctopus_get_displayable_post_types', $post_types);
    }

    /**
     * Gets all forms with saved display rules, and deletes those rules; useful for e.g. when the API key is changed.
     *
     * @return bool True if successful
     */
    public static function delete_automatic_displays(): bool
    {
        $query = new WP_Query(
            [
                'post_type' => 'emailoctopus_form',
                'post_status' => 'any',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'meta_query' => [
                    'relation' => 'OR',
                    [
                        'key' => '_emailoctopus_form_automatic_display',
                        'compare' => 'EXISTS',
                    ],
                    [
                        'key' => '_emailoctopus_form_post_types',
                        'compare' => 'EXISTS',
                    ],
                ],
                'no_found_rows' => true,
                'update_post_term_cache' => false,
                'cache_results' => false,
            ]
        );

        if ($query->posts) {
            foreach ($query->posts as $form_id) {
                delete_post_meta($form_id, '_emailoctopus_form_post_types');
                delete_post_meta($form_id, '_emailoctopus_form_automatic_display');
            }
        }

        return true;
    }

    public static function get_api_data(
        string $endpoint,
        array $args = [],
        int $cache_lifetime_seconds = 300,
        bool $force_refresh = false
    ) {
        $api_url = add_query_arg($args, $endpoint);

        $cache_key = 'emailoctopus_api_responses_' . md5($api_url . '#' . serialize(ksort($args)));
        $cached_response = get_transient($cache_key);

        if ($cached_response !== false && !$force_refresh) {
            return $cached_response;
        }

        $api_response = wp_remote_get(
            $api_url,
            [
                'headers' => [
                    'EmailOctopus-WordPress-Plugin-Version' => EMAILOCTOPUS_VERSION,
                ],
            ]
        );

        if (is_wp_error($api_response)) {
            return $api_response;
        } else {
            $body = json_decode(wp_remote_retrieve_body($api_response));
            if (isset($body->data)) {
                // Paginated response
                $data = $body->data;
            } else {
                // Non-paginated response
                $data = $body;
            }

            if ($data !== null) {
                set_transient($cache_key, $data, $cache_lifetime_seconds);

                return $data;
            }
        }

        return null;
    }

    /**
     * Delete all transients. We do a `SELECT...` then a `delete_transient` for
     * each, rather than a straight `DELETE...` as WordPress does extra cleanup
     * when using `delete_transient`.
     *
     * @return bool True if successful
     */
    public static function clear_transients(): bool
    {
        global $wpdb;

        // Locate our transients (we don't know all of their names in advance as
        // some feature an md5 suffix)
        $transient_options = $wpdb->get_results(
            "SELECT option_name FROM $wpdb->options WHERE option_name LIKE '_transient_emailoctopus_%'"
        );

        $deletes_succeeded = true;

        foreach ($transient_options as $option) {
            // Remove `_transient_` from the beginning of the string to
            // determine the transient name
            $transient_name = preg_replace('/^_transient_/', '', $option->option_name);

            $delete_succeeded = delete_transient($transient_name);
            if ($deletes_succeeded && !$delete_succeeded) {
                $deletes_succeeded = false;
            }
        }

        return $deletes_succeeded;
    }
}
