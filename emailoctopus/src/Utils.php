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
        return 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiB2aWV3Qm94PSIwIDAgODkgMTE3IiB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiPgogICAgPHRpdGxlPkp1c3QgT3R0bzwvdGl0bGU+CiAgICA8ZyBpZD0iUGFnZS0xIiBzdHJva2U9Im5vbmUiIHN0cm9rZS13aWR0aD0iMSIgZmlsbD0ibm9uZSIgZmlsbC1ydWxlPSJldmVub2RkIj4KICAgICAgICA8ZyBpZD0icHVycGxlIiBmaWxsPSIjNkU1NEQ3IiBmaWxsLXJ1bGU9Im5vbnplcm8iPgogICAgICAgICAgICA8cGF0aCBkPSJNODYuNzIzLDc0LjU3ODUgQzgzLjczNCw3My4wMjc2IDgwLjQ0NSw3NC44Mjk0IDc5LjEzNCw3Ni4xNTIyIEM3OC4zNTIsNzYuOTUwNCA3Ny4zODYzLDc3LjU2NjIgNzYuMzI4NCw3Ny45NTM5IEM3NS4yNzA1LDc4LjM0MiA3NC4xNDM3LDc4LjUwMSA3My4wMTY4LDc4LjQxIEM3MS44ODk5LDc4LjMxOSA3MC44MDkxLDc3Ljk3NjggNjkuODIwMiw3Ny40Mjk0IEM2OC44MzEzLDc2Ljg4MiA2OC4wMDM0LDc2LjEwNjYgNjcuMzM2NCw3NS4xOTQzIEM2Ny4xNzU1LDc0Ljk2NjIgNjcuMDM3NSw3NC43MzgyIDY2Ljg5OTUsNzQuNDg3MyBDNzcuMDE4NCw2Ni45MTU0IDgzLjUyNyw1NC44NzMyIDgzLjUwNCw0MS4zMjU5IEM4My40ODEsMTguNDI3NiA2NC41NzY3LC0wLjE2MDEgNDEuNDg3MywtMC4wMDI0Mjk4NzA1NiBDMTguNTM1OSwwLjE1OTIgMCwxOC42NTU3IDAsNDEuNDM5OSBDMCw1Ni4yNjQ1IDcuODY1MSw2OS4yODczIDE5LjY2MjgsNzYuNjA4MyBDMTkuNjE2OCw3Ni45NzMyIDE5LjU0NzgsNzcuMzM4MiAxOS40NTU4LDc3LjcwMzEgQzE5LjE3OTgsNzguNzk4IDE4LjY1MDksNzkuODAxIDE3LjkzOCw4MC42NjggQzE3LjIyNTEsODEuNTM1IDE2LjMyODIsODIuMjE5IDE1LjI5MzMsODIuNjk4IEMxNS4wNDAzLDgyLjgxMiAxNC43ODczLDgyLjkyNiAxNC41MTE0LDgzLjAxNyBDMTQuNDg4NCw4My4wMTcgMTQuNDg4NCw4My4wMTcgMTQuNDY1NCw4My4wNCBDMTQuMzczNCw4My4wNjMgMTQuMzA0NCw4My4wODYgMTQuMjEyNCw4My4xMDggQzEzLjE1NDUsODMuNDA1IDEyLjAyNzYsODMuNDk2IDEwLjkyMzgsODMuMzM2IEM5LjA4NCw4My4wNjMgNC4wOTM1LDgyLjg4IDIuNzU5Nyw4Ni4yNTYgQzEuNTQwOCw4OS4zNTcgNC4wOTM1LDkxLjcyOSA2LjczODIsOTIuNTUgQzcuMjIxMiw5Mi43MSA3LjcwNDEsOTIuODI0IDguMjEwMSw5Mi45MzggQzguNTc4LDkzLjA1MiA4Ljk0Niw5My4xNDMgOS4zMTQsOTMuMTg5IEMxMC4yMTA5LDkzLjMyNiAxMS4xMzA4LDkzLjM5NCAxMi4wNTA2LDkzLjM5NCBDMTMuNzUyNSw5My4zOTQgMTUuNDMxMyw5My4xNDMgMTcuMDQxMSw5Mi42NjQgQzE3LjA2NDEsOTIuNjY0IDE3LjA2NDEsOTIuNjY0IDE3LjA4NzEsOTIuNjQyIEMxNy4xMTAxLDkyLjY0MiAxNy4xMTAxLDkyLjY0MiAxNy4xMzMxLDkyLjYxOSBDMTcuMzYzMSw5Mi41NSAxNy42MTYsOTIuNDgyIDE3Ljg0Niw5Mi4zOTEgQzIwLjA1MzcsOTEuNjM4IDIyLjA3NzUsOTAuNDUyIDIzLjgyNTMsODguOTI0IEMyNC4yMTYzLDg4LjU4MiAyNC41NjEyLDg4LjIxNyAyNC45MDYyLDg3LjgwNyBDMjUuMTU5Miw4Ny41MzMgMjUuMzg5MSw4Ny4yODIgMjUuNjQyMSw4Ni45ODYgQzI1Ljc1NzEsODYuODQ5IDI1Ljg0OTEsODYuNzEyIDI1Ljk2NDEsODYuNTc1IEMyNS45ODcxLDg2LjU1MiAyNS45ODcxLDg2LjUyOSAyNi4wMTAxLDg2LjUwNyBDMjYuNTM5LDg1LjgyMiAyNy4wMjIsODUuMTE1IDI3LjQzNTksODQuMzYzIEMyNy40MzU5LDg0LjM2MyAyNy40MzU5LDg0LjM0IDI3LjQ1ODksODQuMzQgQzI3LjYxOTksODQuMDQzIDI4LjA3OTgsODQuMjI2IDI3Ljk2NDksODQuNTQ1IEMyNy45NDE5LDg0LjU5MSAyNy45NDE5LDg0LjYxNCAyNy45MTg5LDg0LjY1OSBDMjYuODYxLDg3Ljg5OCAyNS4zMjAyLDkwLjk1NCAyMy4zMTk0LDkzLjczNiBDMjEuNjg2Niw5NS45OTQgMTkuODAwOCw5OC4wNDcgMTcuNjg1LDk5Ljg0OSBDMTUuNDA4MywxMDEuNzY0IDE0LjU1NzQsMTA1LjAwMyAxNi4wOTgyLDEwNy41MzUgQzE3LjYzOSwxMTAuMDY2IDIwLjk3MzYsMTEwLjg4NyAyMy4zMTk0LDEwOS4wNjMgQzI2LjY3NywxMDYuNDYzIDI5LjYyMDcsMTAzLjM4NCAzMi4xMDQ0LDk5LjkxNyBDMzQuOTEwMSw5NS45OTQgMzcuMDQ4OCw5MS42MzggMzguNDI4Nyw4Ny4wNTQgQzM4LjU0MzcsODYuNjQzIDM4LjcwNDcsODYuMDczIDM4Ljg0MjYsODUuNDggQzM4LjkzNDYsODUuMDcgMzkuNTU1Niw4NS4xMzggMzkuNTU1Niw4NS41NDkgQzM5LjU3ODYsODYuMDczIDM5LjU3ODYsODYuNTk4IDM5LjU3ODYsODcuMSBDMzkuNTA5Niw5MS42NjEgMzguNDk3Nyw5Ni4xNTQgMzYuNjExOSwxMDAuMzI4IEMzNS40NjIsMTAyLjg1OSAzMy45OTAyLDEwNS4yMzEgMzIuMjY1NCwxMDcuMzk4IEMzMC40MDI2LDEwOS43MDEgMzAuMjE4NiwxMTMuMDU0IDMyLjIxOTQsMTE1LjI0MyBDMzQuMjIwMiwxMTcuNDMzIDM3LjY0NjgsMTE3LjYxNSAzOS42MDE2LDExNS4zOCBDNDIuMzg0MiwxMTIuMTg3IDQ0LjY4NCwxMDguNjA3IDQ2LjQ1NDgsMTA0LjcyOSBDNDguOTYxNSw5OS4yMzMgNTAuMjk1NCw5My4zMDMgNTAuMzY0Myw4Ny4yNTkgQzUwLjM2NDMsODYuODAzIDUwLjM2NDMsODYuMzQ3IDUwLjM2NDMsODUuODkxIEM1MC4zNjQzLDg1LjU3MSA1MC44MjQzLDg1LjUwMyA1MC45MTYzLDg1LjggQzUxLjA1NDMsODYuMzAxIDUxLjE5MjMsODYuNzggNTEuMjg0Miw4Ny4xMjIgQzUyLjA2NjIsOTAuNDk4IDUyLjMxOTEsOTMuOTY0IDUyLjAyMDIsOTcuNDMxIEM1MS43OTAyLDEwMC4xOTEgNTEuMTkyMywxMDIuOTI4IDUwLjI3MjQsMTA1LjUyOCBDNDkuMjgzNSwxMDguMzEgNTAuMjI2NCwxMTEuNTQ5IDUyLjg0ODEsMTEyLjk0IEM1NS40Njk4LDExNC4zMzEgNTguNzU4NCwxMTMuMzczIDU5LjgzOTMsMTEwLjYzNiBDNjEuNDAzMSwxMDYuNzE0IDYyLjM2OSwxMDIuNTYzIDYyLjczNyw5OC4zNDMgQzYzLjA1ODksOTQuNzE3IDYyLjg5NzksOTEuMDkxIDYyLjMyMyw4Ny41MzMgQzYyLjE2Miw4Ni40ODQgNjEuOTMyLDg1LjQ1NyA2MS43MDIxLDg0LjQzMSBDNjEuNjMzMSw4NC4xOCA2MS45NTUsODMuOTc1IDYyLjE2Miw4NC4xNTcgQzYyLjk4OTksODQuODY0IDYzLjg4NjgsODUuNDggNjQuODI5Nyw4Ni4wMjggQzY3LjA4MzUsODcuMzA1IDY5LjU5MDIsODguMDggNzIuMTg4OSw4OC4yODYgQzc0Ljc4NzYsODguNDkxIDc3LjM4NjMsODguMTI2IDc5LjgyNCw4Ny4yMzYgQzgyLjI2Miw4Ni4zMjQgODQuNDcsODQuOTEgODYuMjg2LDgzLjA2MyBDODYuOTMsODIuNDAxIDg3LjUyOCw4MS43MTcgODguMDU3LDgwLjk2NCBDODkuNjksNzguNzk4IDg5LjE4NCw3NS44NTU3IDg2LjcyMyw3NC41Nzg1IFogTTI2Ljk5OSw0Ny43ODAzIEMyNC4xNDczLDQ4LjE2OCAyMS41MDI2LDQ2LjIwNjYgMjEuMTExNiw0My4zNTU3IEMyMC43MjA3LDQwLjUwNDggMjIuNjk4NSwzNy45MDQ4IDI1LjU3MzEsMzcuNTE3MSBDMjguNDI0OCwzNy4xMjk0IDMxLjA2OTUsMzkuMDkwOCAzMS40NjA1LDQxLjk0MTcgQzMxLjg1MTQsNDQuNzY5NyAyOS44NzM2LDQ3LjM5MjUgMjYuOTk5LDQ3Ljc4MDMgWiBNNjQuMTg1OCw0Mi42NDg3IEM2MS4zMzQxLDQzLjAzNjQgNTguNjg5NCw0MS4wNzUgNTguMjk4NSwzOC4yMjQxIEM1Ny45MDc1LDM1LjM5NjEgNTkuODg1MywzMi43NzMyIDYyLjc2LDMyLjM4NTUgQzY1LjYxMTYsMzEuOTk3OCA2OC4yNTYzLDMzLjk1OTIgNjguNjQ3MywzNi44MTAxIEM2OS4wMzgyLDM5LjY2MSA2Ny4wMzc1LDQyLjI2MSA2NC4xODU4LDQyLjY0ODcgWiIgaWQ9IlNoYXBlIj48L3BhdGg+CiAgICAgICAgPC9nPgogICAgPC9nPgo8L3N2Zz4K';
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
