<?php

defined('BASEPATH') || exit('No direct script access allowed');

if (!function_exists('get_whatsapp_bot_types')) {
    /**
     * Retrieves a list of all WhatsApp bot types or a single bot type if an ID is specified.
     *
     * @param  int|string $id Optional bot type ID.
     * @return array|null      An array of all bot types, a single bot type, or null if not found.
     */
    function get_whatsapp_bot_types($id = '')
    {
        $bot_types = [
            // Basic Text Bots
            [
                'id'          => 1,
                'label'       => _l('message_bot'),
                'description' => _l('message_bot_description'),
            ],
            // Template Bots
            [
                'id'          => 2,
                'label'       => _l('template_bot'),
                'description' => _l('template_bot_description'),
            ],
            // Menu Bots (Interactive Menu)
            [
                'id'          => 3,
                'label'       => _l('menu_bot'),
                'description' => _l('menu_bot_description'),
            ],
            // Flow Bots (Interactive flows)
            [
                'id'          => 4,
                'label'       => _l('flow_bot'),
                'description' => _l('flow_bot_description'),
            ],
            // Media Bots (e.g., Image, Video, Document, Audio)
            [
                'id'          => 5,
                'label'       => _l('media_bot'),
                'description' => _l('media_bot_description'),
            ],
            // Location Bots
            [
                'id'          => 6,
                'label'       => _l('location_bot'),
                'description' => _l('location_bot_description'),
            ],
            // Interactive Buttons Bots
            [
                'id'          => 7,
                'label'       => _l('interactive_buttons_bot'),
                'description' => _l('interactive_buttons_bot_description'),
            ],

            // Contact Bots
            [
                'id'          => 11,
                'label'       => _l('contact_bot'),
                'description' => _l('contact_bot_description'),
            ],
            // AI Bot
            [
                'id'          => 12,
                'label'       => _l('ai_bot'),
                'description' => _l('ai_bot_description'),
            ],
            // Review Bot (for requesting star reviews, etc.)
            [
                'id'          => 13,
                'label'       => _l('review_bot'),
                'description' => _l('review_bot_description'),
            ],
        ];

        // If a specific ID is requested, search the array
        if (!empty($id)) {
            $key = array_search($id, array_column($bot_types, 'id'));
            return $bot_types[$key] ?? null; // Return null if ID is not found
        }

        // Otherwise, return the entire array of bot types
        return $bot_types;
    }
}

if (!function_exists('get_whatsapp_reply_triggers')) {
    /**
     * Retrieves an array of all WhatsApp reply triggers, or a single trigger if an ID is specified.
     *
     * @param  int|string $id Optional trigger ID.
     * @return array|null     An array of all triggers, a single trigger, or null if not found.
     */
    function get_whatsapp_reply_triggers($id = '')
    {
        $reply_types = [
            // Basic Triggers
            [
                'id'      => 1,
                'label'   => _l('on_exact_match'),
                'example' => _l('on_exact_match_description'),
            ],
            [
                'id'      => 2,
                'label'   => _l('when_message_contains'),
                'example' => _l('when_message_contains_description'),
            ],
            [
                'id'      => 3,
                'label'   => _l('when_client_send_the_first_message'),
                'example' => _l('when_client_send_the_first_message_description'),
            ],
            [
                'id'      => 4,
                'label'   => _l('on_keyword_match'),
                'example' => _l('on_keyword_match_description'),
            ],
            [
                'id'      => 5,
                'label'   => _l('within_office_time_range'),
                'example' => _l('within_office_time_range_description'),
            ],
            [
                'id'      => 6,
                'label'   => _l('outof_office_time_range'),
                'example' => _l('outof_office_time_range_description'),
            ],
            [
                'id'      => 7,
                'label'   => _l('first_message_in_session'),
                'example' => _l('first_message_in_session_description'),
            ],
            // Additional or custom triggers
            [
                'id'      => 8,
                'label'   => _l('on_user_idle_timeout'),
                'example' => _l('on_user_idle_timeout_description'),
            ],
            [
                'id'      => 9,
                'label'   => _l('on_user_requests_human'),
                'example' => _l('on_user_requests_human_description'),
            ],
            [
                'id'      => 10,
                'label'   => _l('on_synonym_match'),
                'example' => _l('on_synonym_match_description'),
            ],
            // New triggers focusing on your custom use cases:
            [
                'id'      => 11,
                'label'   => _l('on_chat_timeout_reminder'),
                'example' => _l('on_chat_timeout_reminder_description'),
            ],
            [
                'id'      => 12,
                'label'   => _l('on_no_response_inform_staff'),
                'example' => _l('on_no_response_inform_staff_description'),
            ],
            [
                'id'      => 13,
                'label'   => _l('on_chat_close_request_star_rating'),
                'example' => _l('on_chat_close_request_star_rating_description'),
            ],
        ];

        // If an ID is provided, attempt to find that trigger
        if (!empty($id)) {
            $key = array_search($id, array_column($reply_types, 'id'));
            return $reply_types[$key] ?? null; // null if ID not found
        }

        // Otherwise, return all triggers
        return $reply_types;
    }
}


/**
 * Get WhatsApp template based on ID
 *
 * @param string $id
 * @return array
 */
if (!function_exists('get_whatsapp_template')) {
    function get_whatsapp_template($id = '')
    {
        $CI = get_instance();
        $CI->db->order_by('language', 'asc');

        if (is_numeric($id)) {
            // If $id is numeric, fetch by ID
            $query = $CI->db->get_where(db_prefix() . 'whatsapp_interaction_templates', ['id' => $id, 'status' => 'APPROVED']);
        } elseif (!empty($id)) {
            // If $id is a string, fetch by template_name
            $query = $CI->db->get_where(db_prefix() . 'whatsapp_interaction_templates', ['template_name' => $id, 'status' => 'APPROVED']);
        } else {
            // If no $id is provided, fetch all approved templates
            $query = $CI->db->get_where(db_prefix() . 'whatsapp_interaction_templates', ['status' => 'APPROVED']);
        }

        if (is_numeric($id) || !empty($id)) {
            return $query->row_array();
        }

        return $query->result_array();
    }
}

/**
 * Get campaign data based on campaign ID
 *
 * @param string $campaign_id
 * @return array
 */
if (!function_exists('whatsapp_get_campaign_data')) {
    function whatsapp_get_campaign_data($campaign_id = '')
    {
        return get_instance()->db->get_where(db_prefix().'whatsapp_campaign_data', ['campaign_id' => $campaign_id])->result_array();
    }
}
if (!function_exists('whatsapp_get_campaign')) {
    function whatsapp_get_campaign($campaign_id = '')
    {
        return get_instance()->db->get_where(db_prefix().'whatsapp_campaign', ['id' => $campaign_id])->result_array();
    }
}
/**
 * Check if a string is a valid JSON
 *
 * @param string $string
 * @return bool
 */
if (!function_exists('wbIsJson')) {
    function wbIsJson($string)
    {
        return ((is_string($string) &&
            (is_object(json_decode($string)) ||
                is_array(json_decode($string))))) ? true : false;
    }
}

/**
 * Get the relation types
 *
 * @return array<int, array{key: string, name: string}>
 */
if (! function_exists('whatsapp_get_rel_type')) {
    function whatsapp_get_rel_type(): array
    {
        return [
            [
                'key'  => 'leads',
                'name' => _l('leads'),
            ],
            [
                'key'  => 'contacts',
                'name' => _l('contacts'),
            ],
            [
                'key'  => 'whatsapp_groups',
                'name' => _l('whatsapp_groups'),
            ],
            [
                'key'  => 'general',
                'name' => _l('general'),
            ]
        ];
    }
}


/**
 * Replaces all merge field placeholders in the given $data array using whatsappParseText().
 *
 * @param array $data
 * @return array
 */
function whatsappReplaceAllParamsInData(array $data): array
{
    $rel_type = $data['rel_type'] ?? '';
    $types = ['header', 'body', 'footer'];

    foreach ($types as $type) {
        // Skip if data or param count is not available
        if (empty($data["{$type}_params_count"]) || (int)$data["{$type}_params_count"] <= 0) {
            continue;
        }

        // Always call the parser to apply merge field replacements
        $parsed = whatsappParseText($rel_type, $type, $data, 'data');

        // Replace original param values (header_params/body_params/footer_params)
        if (!empty($parsed)) {
            if (strtoupper($data['parameter_format'] ?? 'POSITIONAL') === 'NAMED') {
                $cleaned_params = [];

                foreach ($parsed as $item) {
                    if (isset($item['parameter_name'])) {
                        $cleaned_params[$item['parameter_name']] = ['value' => $item['text']];
                    }
                }

                $data["{$type}_params"] = json_encode($cleaned_params);
            } else {
                // For POSITIONAL, use simple index-based structure
                $cleaned_params = [];
                foreach ($parsed as $val) {
                    $cleaned_params[] = ['value' => $val];
                }
                $data["{$type}_params"] = json_encode($cleaned_params);
            }
        }

        // Also update the data string itself (header_data, body_data, etc.)
        $data["{$type}_data"] = whatsappParseText($rel_type, $type, $data, 'text');
    }

    return $data;
}


if (!function_exists('whatsappParseText')) {
    /**
     * Parse WhatsApp template data with CRM merge fields.
     *
     * @param string|null $rel_type      e.g. 'leads', 'contacts'
     * @param string      $type          'body', 'header', or 'footer'
     * @param array       $data          All template + relation data
     * @param string      $return_type   'text', 'data', or 'log'
     * @param bool        $debug         Return debug info (optional)
     * @return string|array
     */
    function whatsappParseText($rel_type, $type, $data, $return_type = 'text', $debug = false)
    {
        $rel_type = $rel_type ?? $data['rel_type'];
        $debug_log = [];

        // Load merge fields unless general/non-relational.
        if ($rel_type !== '' && !in_array($rel_type, ['all', 'whatsapp_groups', 'general'], true)) {
            // Convert "contacts" to "client" if needed.
            $rel_type = ($rel_type === 'contacts') ? 'client' : $rel_type;
            $CI = get_instance();
            $CI->load->library('merge_fields/app_merge_fields');
            $merge_fields = [];

            // Get merge fields for the relation type.
            $merge_fields = $CI->app_merge_fields->format_feature(
                $rel_type . '_merge_fields',
                $data['userid'] ?? $data['rel_id'],
                $data['rel_id'] ?? null
            );
            // Get fallback ("other") merge fields.
            $other_merge_fields = $CI->app_merge_fields->format_feature('other_merge_fields');
            $merge_fields = array_merge($other_merge_fields, $merge_fields);
        } else {
            $merge_fields = [];
        }

        $parse_data = [];
        // Get the JSON string for parameters (e.g., header_params, body_params, footer_params).
        $params = $data["{$type}_params"] ?? '[]';

        // Decode parameters if the string is valid JSON.
        if (wbIsJson($params)) {
            $parsed_text = json_decode($params, true);

            // Determine NAMED vs POSITIONAL by testing if keys are sequential numbers.
            $is_named = array_keys($parsed_text) !== range(0, count($parsed_text) - 1);

            if ($is_named) {
                foreach ($parsed_text as $key => $item) {
                    $value = isset($item['value']) ? trim($item['value']) : '';

                    // Replace fallback placeholder: e.g. {{lead_name|Customer}}
                    if (preg_match('/{{(.*?)\|(.*?)}}/', $data["{$type}_data"], $matches)) {
                        $value = $value ?: $matches[2];
                    }

                    // Replace any merge field keys found inside the value.
                    foreach ($merge_fields as $mf_key => $mf_val) {
                        $value = str_replace($mf_key, $mf_val ?: ' ', $value);
                    }

                    // Legacy support: replace @{...} with { ... }.
                    $value = preg_replace('/@{(.*?)}/', '{$1}', $value);
                    // Reduce multiple spaces.
                    $parsed_text[$key] = preg_replace('/\s+/', ' ', $value);

                    if ($debug) {
                        $debug_log[$key] = $parsed_text[$key];
                    }

                    // If we're returning text, replace the corresponding placeholder in the main data text.
                    if ($return_type === 'text') {
                        $data["{$type}_data"] = str_replace("{{{$key}}}", $parsed_text[$key], $data["{$type}_data"]);
                    }

                    $parse_data[] = [
                        'parameter_name' => $key,
                        'text'           => $parsed_text[$key],
                    ];
                }
            } else {
                // POSITIONAL format.
                $parsed_text = array_map(function ($item) use ($merge_fields, &$debug_log) {
                    $value = trim($item['value']);
                    foreach ($merge_fields as $k => $v) {
                        $value = str_replace($k, $v ?: ' ', $value);
                    }
                    $value = preg_replace('/\s+/', ' ', $value);
                    $debug_log[] = $value;
                    return $value;
                }, $parsed_text);

                $param_count = (int)($data["{$type}_params_count"] ?? count($parsed_text));
                for ($i = 1; $i <= $param_count; $i++) {
                    $val = $parsed_text[$i - 1] ?? ' ';
                    if ($return_type === 'text') {
                        $data["{$type}_data"] = str_replace("{{{$i}}}", $val, $data["{$type}_data"]);
                    }
                    $parse_data[] = $val;
                }
            }
        }

        // Return result based on requested type.
        if ($return_type === 'text') {
            return preg_replace('/\s+/', ' ', trim($data["{$type}_data"] ?? ''));
        } elseif ($return_type === 'data') {
            return $parse_data;
        } elseif ($return_type === 'log') {
            return $debug_log;
        }

        return $parse_data;
    }
}



/**
 * Get the campaign status based on status ID
 *
 * @param string $status_id
 * @return array
 */
if (!function_exists('whatsapp_campaign_status')) {
    function whatsapp_campaign_status($status_id = '')
    {
        $statusid              = ['0', '1', '2', '3'];
        $status['label']       = ['Failed', 'Pending', 'Success', _l('invalid_number_format')];
        $status['label_class'] = ['label-danger', 'label-warning', 'label-success', 'label-danger'];

        if (in_array($status_id, $statusid)) {
            $index = array_search($status_id, $statusid);
            if (false !== $index && isset($status['label'][$index])) {
                $status['label'] = $status['label'][$index];
            }
            if (false !== $index && isset($status['label_class'][$index])) {
                $status['label_class'] = $status['label_class'][$index];
            }
        } else {
            $status['label']       = _l('draft');
            $status['label_class'] = 'label-default';
        }

        return $status;
    }
}

/**
 * Get all staff members
 *
 * @return array
 */
if (!function_exists('whatsapp_get_all_staff')) {
    function whatsapp_get_all_staff()
    {
        return get_instance()->db->get(db_prefix().'staff')->result_array();
    }
}

/**
 * Get staff members allowed to view message templates
 *
 * @return array
 */
if (!function_exists('wbGetStaffMembersAllowedToViewMessageTemplates')) {
    function wbGetStaffMembersAllowedToViewMessageTemplates()
    {
        get_instance()->db->join(db_prefix().'staff_permissions', db_prefix().'staff_permissions.staff_id = '.db_prefix().'staff.staffid', 'LEFT');
        get_instance()->db->where([db_prefix().'staff_permissions.capability' => 'view', db_prefix().'staff_permissions.feature' => 'whatsapp_template']);
        get_instance()->db->or_where([db_prefix().'staff.admin' => '1']);

        return get_instance()->db->get(db_prefix().'staff')->result_array();
    }
}

/**
 * Get the interaction ID based on data, relation type, ID, name, and phone number
 *
 * @param array $data
 * @param string $rel_type
 * @param string $id
 * @param string $name
 * @param string $phonenumber
 * @return int
 */


/**
 * Decode WhatsApp signs to HTML tags
 *
 * @param string $text
 * @return string
 */
if (!function_exists('whatsappDecodeWhatsAppSigns')) {
    function whatsappDecodeWhatsAppSigns($text)
    {
        $patterns = [
            '/\*(.*?)\*/',       // Bold
            '/_(.*?)_/',         // Italic
            '/~(.*?)~/',         // Strikethrough
            '/```(.*?)```/',      // Monospace
        ];
        $replacements = [
            '<strong>$1</strong>',
            '<em>$1</em>',
            '<del>$1</del>',
            '<code>$1</code>',
        ];

        return preg_replace($patterns, $replacements, $text);
    }
}
/**
 * Generate a random filename with a number
 *
 * @param string $originalName The original file name
 * @return string The new random file name
 */
/**
 * Generate a unique filename using current date, time, and a random number
 *
 * @param string $originalName The original file name
 * @return string The new unique file name
 */
if (!function_exists('generate_random_filename')) {
function generate_random_filename($originalName)
{
    // Extract the file extension
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    
    // Get the current timestamp
    $timestamp = date('YmdHis'); // Format: YYYYMMDDHHMMSS

    // Generate a random number
    $randomNumber = rand(1000, 9999); // You can adjust the range as needed

    // Generate a new filename
    return $timestamp . '_' . $randomNumber . '.' . $extension;
}
}





if (!function_exists('whatsapp_get_allowed_extension')) {
    function whatsapp_get_allowed_extension()
    {
        return [
            'image' => [
                'extension' => '.jpg,.jpeg,.png',
                'size'      => 10
            ],
            'video' => [
                'extension' => '.mp4, .3gp',
                'size'      => 24,
            ],
            'audio' => [
                'extension' => '.aac, .amr, .mp3, .m4a, .ogg',
                'size'      => 16,
            ],
            'document' => [
                'extension' => '.pdf, .doc, .docx, .txt, .xls, .xlsx, .ppt, .pptx',
                'size'      => 100,
            ],
        ];
    }
}
if (!function_exists('truncate_message')) {
    function truncate_message($message, $limit = 25) {
    // Remove HTML tags
    $message = strip_tags($message);
    // Check if the message length exceeds the limit
    if (strlen($message) > $limit) {
        // Truncate the message and add ellipsis
        $message = substr($message, 0, $limit) . '...';
    }
    return $message;
}

}
if (!function_exists('whatsapp_get_media_types')) {
    function whatsapp_get_media_types()
    {
        return [
            [
                'id'    => 'image',
                'label' => _l('Image'),
            ],
            [
                'id'    => 'video',
                'label' => _l('Video'),
            ],
            [
                'id'    => 'document',
                'label' => _l('Document'),
            ],
            [
                'id'    => 'audio',
                'label' => _l('Audio'),
            ],
        ];
    }
}
if (!function_exists('whatsapp_default_phone_number')) {
    /**
     * Fetches the details of the default phone number from the database.
     *
     * @return array|null Default phone number details or null if not found.
     */
    function whatsapp_default_phone_number()
    {
        $CI =& get_instance(); // Get CI instance

        // Load database library
        $CI->load->database();

        // Query to get the default phone number details
        $CI->db->select('*');
        $CI->db->from(db_prefix() . 'whatsapp_numbers');
        $CI->db->where('is_default', 1);
        $query = $CI->db->get();

        // Return the result
        return $query->row_array(); // Return as associative array
    }
}
if (!function_exists('whatsapp_new_bots')) {
    /**
     * Fetches the details of all bots linked to a specific interaction ID.
     *
     * @param int $interaction_id The ID of the interaction to fetch bots for.
     * @return array|null An array of bot details or null if not found.
     */
    function whatsapp_new_bots($interaction_id)
    {
        $CI =& get_instance(); // Get CI instance

        // Start building the query
        $CI->db->select('wb.id as bot_id, wb.*, wt.*, wi.*');
        $CI->db->from(db_prefix() . 'whatsapp_bot as wb');

        // Join with whatsapp_interaction_templates table
        $CI->db->join(db_prefix() . 'whatsapp_interaction_templates as wt', 'wb.template_id = wt.id', 'left');

        // Join with whatsapp_interaction table using interaction_id
        $CI->db->join(db_prefix() . 'whatsapp_interactions as wi', 'wi.id = ' . $CI->db->escape($interaction_id), 'left');

        // Ensure bot is active
        $CI->db->where('wb.is_bot_active', 1);

        // Filter by the specific interaction
        $CI->db->where('wi.id', $interaction_id);

        // Execute the query and return the result
        $result = $CI->db->get()->result_array();

        // Return the result
        return $result;
    }
}


if (!function_exists('whatsapp_unreadmessages')) {
    /**
     * Fetches the count of unread WhatsApp messages from the database.
     *
     * @return int The total number of unread messages.
     */
    function whatsapp_unreadmessages()
    {
        $CI =& get_instance(); // Get the CodeIgniter instance

        // Load the database library if not already loaded
        $CI->load->database();

        // Build the query to sum up the 'unread' values
        $CI->db->select('SUM(unread) as unread_count');
        $CI->db->from(db_prefix() . 'whatsapp_interactions');
        $CI->db->where('unread >', 0);
        $query = $CI->db->get();

        // Retrieve the row as an associative array
        $result = $query->row_array();

        // Return the count if it exists, or 0 otherwise
        return isset($result['unread_count']) ? (int)$result['unread_count'] : 0;
    }
}

if (!function_exists('save_whatsapp_file')) {
    /**
     * Centralized file saving for WhatsApp uploads.
     *
     * @param string|array $source        File URL, tmp path, or $_FILES-style array
     * @param string       $type          Folder type (template, bot, campaign, interaction, etc.)
     * @param mixed|null   $type_id       Optional subfolder (e.g., interaction ID)
     * @param string       $inputKey      Input field name in $_FILES (default: 'filename')
     * @param string|null  $customFilename Optional static filename (e.g. template_id.jpg)
     * @return string|false               Saved filename or false on failure
     */
    function save_whatsapp_file($source, $type = 'interactions', $type_id = null, $inputKey = 'filename', $customFilename = null)
    {
        $CI = &get_instance();

        $uploadPath = get_upload_path_by_type($type);

        // ✅ Ensure directory exists
        if (!is_dir($uploadPath)) {
            if (!@mkdir($uploadPath, 0755, true) && !is_dir($uploadPath)) {
                log_message('error', 'Failed to create directory: ' . $uploadPath);
                return false;
            }
        }

        // Case 1: File from URL
        if (is_string($source) && filter_var($source, FILTER_VALIDATE_URL)) {
            $originalExtension = pathinfo(parse_url($source, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $filename = $customFilename ?: generate_random_filename('file.' . $originalExtension);
            $saveTo   = rtrim($uploadPath, '/') . '/' . $filename;

            $contents = @file_get_contents($source);
            if ($contents !== false && _upload_extension_allowed($filename)) {
                if (@file_put_contents($saveTo, $contents)) {
                    return $filename;
                }
            }

            log_message('error', 'Failed to download file from URL: ' . $source);
            return false;
        }

        // Case 2: $_FILES upload
        if (isset($_FILES[$inputKey]['name']) && !empty($_FILES[$inputKey]['tmp_name'])) {
            $tmpName  = $_FILES[$inputKey]['tmp_name'];
            $original = $_FILES[$inputKey]['name'];

            if (is_uploaded_file($tmpName)) {
                $filename = $customFilename ?: generate_random_filename($original);

                if (!_upload_extension_allowed($filename)) {
                    log_message('error', 'Disallowed extension: ' . $filename);
                    return false;
                }

                $saveTo = rtrim($uploadPath, '/') . '/' . $filename;
                if (@move_uploaded_file($tmpName, $saveTo)) {
                    return $filename;
                }

                log_message('error', 'Failed to move uploaded file to: ' . $saveTo);
                return false;
            }
        }

        // Case 3: Manual array upload
        if (is_array($source) && isset($source['tmp_name'], $source['name'])) {
            $tmpName  = $source['tmp_name'];
            $original = $source['name'];

            if (is_uploaded_file($tmpName)) {
                $filename = $customFilename ?: generate_random_filename($original);

                if (!_upload_extension_allowed($filename)) {
                    log_message('error', 'Invalid file extension: ' . $filename);
                    return false;
                }

                $saveTo = rtrim($uploadPath, '/') . '/' . $filename;
                if (@move_uploaded_file($tmpName, $saveTo)) {
                    return $filename;
                }

                log_message('error', 'Manual upload failed to save to: ' . $saveTo);
                return false;
            }
        }

        return false;
    }
}



if (!function_exists('whatsapp_handle_header_upload')) {
    /**
     * Handle file upload in header_params
     *
     * This function processes the `header_params` array, checks for files, uploads them,
     * and replaces the file path in the array.
     *
     * @param array $header_params The header_params array with potential files.
     * @param string $bot_id The ID of the bot (used to associate the uploaded files).
     * @return array The updated header_params array with file paths.
     */
    function whatsapp_handle_header_upload($header_params, $bot_id)
    {
        // Path to upload the files
        $upload_path = get_upload_path_by_type('bot');

        // Ensure the upload path exists
        _maybe_create_upload_path($upload_path);

        // Loop through each header param
        foreach ($header_params as &$param) {
            // Check if the param is a file type and the file is uploaded
            if (isset($param['type']) && $param['type'] == 'file' && isset($_FILES[$param['name']])) {
                $file = $_FILES[$param['name']];

                // Validate the temporary file path
                if (!empty($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
                    // Generate a random filename to avoid conflicts
                    $filename = generate_random_filename($file['name']);

                    // Check if the file extension is allowed
                    if (_upload_extension_allowed($filename)) {
                        $new_file_path = $upload_path . $filename;

                        // Move the file to the final destination
                        if (move_uploaded_file($file['tmp_name'], $new_file_path)) {
                            // Update the param with the new file path
                            $param['value'] = $new_file_path;

                            // Optionally, update the database with the full header_params array
                            get_instance()->db->update(db_prefix() . 'whatsapp_bot', ['header_params' => json_encode($header_params)], ['id' => $bot_id]);
                        } else {
                            log_message('error', 'Failed to move uploaded file to destination: ' . $new_file_path);
                        }
                    } else {
                        log_message('error', 'File extension not allowed for file: ' . $filename);
                    }
                }
            }
        }

        // Return the updated header_params array
        return $header_params;
    }
}

if (!function_exists('get_template_maper')) {
    function get_template_maper($templateId, $type, $type_id = null) {
        // Fetch the template details (assumes this function returns all template fields,
        // including the newly added ones)
        $template = get_whatsapp_template($templateId);

        // Initialize template data
        $header_data = $template['header_data_text'] ?? '';
        $body_data   = $template['body_data'] ?? '';
        $footer_data = $template['footer_data'] ?? '';
        $button_data = !empty($template['buttons_data']) ? json_decode($template['buttons_data'], true) : [];

        // Initialize parameters
        $campaign_or_bot = null;
        $header_params = $body_params = $footer_params = null;

        // Get the CI instance
        $CI =& get_instance();
        $CI->load->database();

        // Construct SQL based on the provided type_id
        if (!empty($type_id)) {
            // Fetch data for campaign or bot based on type
            if ($type === 'campaign') {
                $sql = "
                    SELECT 
                        wc.*, 
                        wt.template_name as template_name,
                        wt.template_id as tmp_id,
                        wt.header_params_count,
                        wt.body_params_count,
                        wt.footer_params_count,
                        wt.header_data_format,
                        wt.parameter_format,
                        wt.sub_category,
                        wt.header_example,
                        wt.body_example,
                        wt.footer_example,
                        wt.buttons_example,
                        wt.created_at,
                        wt.updated_at,
                        wt.template_description,
                        wt.is_custom,
                        CONCAT('[', GROUP_CONCAT(wcd.rel_id SEPARATOR ','), ']') as rel_ids,
                        wc.filename,
                        wc.header_params,
                        wc.body_params,
                        wc.footer_params
                    FROM 
                        " . db_prefix() . "whatsapp_campaigns wc
                    LEFT JOIN 
                        " . db_prefix() . "whatsapp_interaction_templates wt ON wt.id = wc.template_id
                    LEFT JOIN 
                        " . db_prefix() . "whatsapp_campaign_data wcd ON wcd.campaign_id = wc.id
                    WHERE 
                        wc.id = ?
                    GROUP BY wc.id
                ";
                $campaign_or_bot = $CI->db->query($sql, [$type_id])->row_array();
            } elseif ($type === 'bot') {
                $sql = "
                    SELECT 
                        wb.*, 
                        wt.template_name as template_name,
                        wt.template_id as tmp_id,
                        wt.header_params_count,
                        wt.body_params_count,
                        wt.footer_params_count,
                        wt.header_data_format,
                        wt.parameter_format,
                        wt.sub_category,
                        wt.header_example,
                        wt.body_example,
                        wt.footer_example,
                        wt.buttons_example,
                        wt.created_at,
                        wt.updated_at,
                        wt.template_description,
                        wt.is_custom,
                        wb.filename,
                        wb.header_params,
                        wb.body_params,
                        wb.footer_params
                    FROM 
                        " . db_prefix() . "whatsapp_bot wb
                    LEFT JOIN 
                        " . db_prefix() . "whatsapp_interaction_templates wt ON wt.id = wb.template_id
                    WHERE 
                        wb.id = ?
                    GROUP BY wb.id
                ";
                $campaign_or_bot = $CI->db->query($sql, [$type_id])->row_array();
            }

            // Decode parameters if available
            if ($campaign_or_bot) {
                $header_params = json_decode($campaign_or_bot['header_params'] ?? '', true);
                $body_params = json_decode($campaign_or_bot['body_params'] ?? '', true);
                $footer_params = json_decode($campaign_or_bot['footer_params'] ?? '', true);
            }
        } else {
            // Fetch all campaigns if type_id is null
            $sql = "
                SELECT 
                    wc.*, 
                    wt.template_name as template_name,
                    wt.template_id as tmp_id,
                    wt.header_params_count,
                    wt.body_params_count,
                    wt.footer_params_count,
                    CONCAT('[', GROUP_CONCAT(wcd.rel_id SEPARATOR ','), ']') as rel_ids,
                    wc.filename
                FROM 
                    " . db_prefix() . "whatsapp_campaigns wc
                LEFT JOIN 
                    " . db_prefix() . "whatsapp_interaction_templates wt ON wt.id = wc.template_id
                LEFT JOIN 
                    " . db_prefix() . "whatsapp_campaign_data wcd ON wcd.campaign_id = wc.id
                GROUP BY wc.id
            ";
            $campaign_or_bot = $CI->db->query($sql)->result_array(); // Get all campaigns
        }

        // Prepare the data array for the view
        $data = [
            'template'      => $template,
            'header_data'   => $header_data,
            'body_data'     => $body_data,
            'footer_data'   => $footer_data,
            'button_data'   => $button_data,
            'header_params' => $header_params,
            'body_params'   => $body_params,
            'footer_params' => $footer_params,
            'type'          => $type,
            'data'          => $campaign_or_bot, // This will be 'campaign' or 'bot'
            'filename'     => $campaign_or_bot['filename'],
        ];

        // Load the 'variables' view with all data
        $view = $CI->load->view('whatsapp/templates/variables', $data, true);

        // (Optional) You can still check for unsupported formats and errors if needed.
        // For now, we'll assume all data is sent to the view directly.

        // Return the complete response including the view and all data
        return [
            'view'         => $view,
            'header_data'  => $header_data,
            'body_data'    => $body_data,
            'footer_data'  => $footer_data,
            'button_data'  => $button_data,
            'header_params'=> $header_params,
            'body_params'  => $body_params,
            'footer_params'=> $footer_params,
            'type'         => $type,
            'data'         => $campaign_or_bot,
            'template'     => $template,
            'filename'     => $campaign_or_bot['filename'],
        ];
    }
}

if (!function_exists('getAIReply')) {
    function getAIReply($interaction_id, $usermessage = null, $mymessage = null)
    {
        $CI =& get_instance();

        // 1) Load interaction & bail if missing
        $interaction = $CI->whatsapp_interaction_model->get_interaction($interaction_id);
        if (empty($interaction)) {
            log_message('error', "No interaction details found for ID: {$interaction_id}");
            return 'No interaction details available.';
        }

        // Normalize
        $wa_no    = is_array($interaction) ? $interaction['wa_no']    : $interaction->wa_no;
        $wa_no_id = is_array($interaction) ? $interaction['wa_no_id'] : $interaction->wa_no_id;

        // 2) Pull per-number AI prompt
        $ai_prompt = $CI->db
            ->select('ai_prompt')
            ->where('phone_number_id', $wa_no_id)
            ->get(db_prefix() . 'whatsapp_numbers')
            ->row()
            ->ai_prompt ?? '';

        // 3) Pull global training settings
        $system_prompt = trim(get_option('ai_system_prompt') ?: '');
        $training_data = trim(get_option('ai_training_data') ?: '');

        // 4) Build messages array, starting with global prompts
        $messages = [];

        if ($system_prompt !== '') {
            $messages[] = [
                'role'    => 'system',
                'content' => $system_prompt
            ];
        }

        if ($training_data !== '') {
            $messages[] = [
                'role'    => 'system',
                'content' => $training_data
            ];
        }

        // Per-number prompt
        $messages[] = [
            'role'    => 'system',
            'content' => 'You are a helpful assistant. ' . ($ai_prompt ?: '')
        ];

// Strict AI formatting instructions
$messages[] = [
    'role' => 'system',
    'content' => "You are a helpful, human-like support agent.\n"
               . "Reply to customers in clear, friendly WhatsApp text.\n"
               . "Use these formatting rules ONLY:\n"
               . "- Use *asterisks* for bold (no spaces inside)\n"
               . "- Use _underscores_ for italics\n"
               . "- Use ~tildes~ for strikethrough\n"
               . "- Use \\n for new lines between sentences or paragraphs\n\n"
               . "STRICT RULES:\n"
               . "- Do NOT include code blocks (e.g., do not wrap anything in triple backticks)\n"
               . "- Do NOT start or include 'ASSISTANT:', 'USER:', or any labels\n"
               . "- Do NOT mention WhatsApp, AI, bots, assistant, or automation\n"
               . "- Do NOT use markdown, JSON, or any code formatting\n"
               . "- Write replies in natural, human tone as if a real support agent is replying\n"
               . "- Do NOT include quotation marks unless required in the actual message content"
];

        // 5) Append conversation history
        $history = $CI->whatsapp_interaction_model->get_interaction_history($interaction_id);
        if (!empty($history)) {
            $messages = array_merge($messages, $history);
        }

        // 6) Optional overrides from this call
        if ($mymessage) {
            $messages[] = ['role' => 'system', 'content' => $mymessage];
        }
        if ($usermessage) {
            $messages[] = ['role' => 'user', 'content' => $usermessage];
        }

        // 7) Provider setting
        $provider = get_option('ai_provider', 'disable');
        if ($provider === 'disable') {
            return '[AI is disabled by admin]';
        }

        // 8) Route to OpenAI or Gemini
        try {
            if ($provider === 'openai') {
                $token    = get_option('whatsapp_openai_token');
                $endpoint = 'https://api.openai.com/v1/chat/completions';
                $payload  = json_encode([
                    'model'    => 'gpt-4',
                    'messages' => $messages,
                ]);

                $ch = curl_init($endpoint);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $payload,
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/json',
                        "Authorization: Bearer {$token}",
                    ],
                ]);
                $result = curl_exec($ch);
                if (curl_errno($ch)) {
                    throw new Exception('cURL Error: ' . curl_error($ch));
                }
                curl_close($ch);

                $resp = json_decode($result, true);
                if (isset($resp['choices'][0]['message']['content'])) {
                    return trim($resp['choices'][0]['message']['content']);
                }
                throw new Exception('OpenAI error: ' . ($resp['error']['message'] ?? 'unknown'));

            } elseif ($provider === 'gemini') {
                $model  = 'gemini-2.0-flash';
                $token  = get_option('gemini_api_key');

                // Flatten messages into prompt text
                $promptText = '';
                foreach ($messages as $msg) {
                    $promptText .= strtoupper($msg['role']) . ': ' . $msg['content'] . "\n";
                }

                $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
                $body = json_encode([
                    'contents' => [
                        [
                            'role'  => 'user',
                            'parts' => [
                                ['text' => trim($promptText)]
                            ]
                        ]
                    ]
                ]);

                $ch = curl_init($endpoint);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $body,
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/json',
                        "x-goog-api-key: {$token}",
                    ],
                ]);
                $result = curl_exec($ch);
                if (curl_errno($ch)) {
                    throw new Exception('cURL Error: ' . curl_error($ch));
                }
                curl_close($ch);

                $resp = json_decode($result, true);
                if (isset($resp['candidates'][0]['content']['parts'][0]['text'])) {
                    return trim($resp['candidates'][0]['content']['parts'][0]['text']);
                }
                throw new Exception('Gemini Error: ' . json_encode($resp));

            } else {
                log_message('error', "Unknown AI provider: {$provider}");
                return '[AI provider not recognized]';
            }

        } catch (Exception $e) {
            log_message('error', 'AI Error [' . strtoupper($provider) . ']: ' . $e->getMessage());
            return "Error ({$provider}): " . $e->getMessage();
        }
    }
}


function json_validate_ticket_format($json)
{
    $data = json_decode($json, true);
    return is_array($data)
        && isset($data['subject'], $data['message'], $data['priority'], $data['department']);
}
if (!function_exists('json_validate_ticket_format')) {
    function json_validate_ticket_format($json) {
        $data = json_decode($json, true);
        return is_array($data) && isset($data['subject'], $data['message'], $data['priority'], $data['department']);
    }
}

if (!function_exists('get_whatsapp_number_data')) {
    /**
     * Retrieves WhatsApp number data from the database.
     *
     * @param mixed $id Optional ID. If provided, returns a single record; otherwise, returns all records.
     * @return array|null An associative array of WhatsApp number data or an array of records.
     */
    function get_whatsapp_number_data($id = '')
    {
        $CI =& get_instance();
        $table = db_prefix() . 'whatsapp_numbers';

        if (!empty($id)) {
            // Get a single record based on ID.
            $CI->db->where('phone_number_id', $id);
            $query = $CI->db->get($table);
            return $query->row_array();
        }

        // Otherwise, return all records.
        $query = $CI->db->get($table);
        return $query->result_array();
    }
}
/**
 * Saves the interaction and the incoming message.
 *
 * @param array $messageEntry The incoming message data
 * @param array $contact The contact information
 * @param array $metadata WhatsApp metadata
 * @param array $fullPayload The complete payload of the message
 * @return int The interaction ID
 */
if (!function_exists('save_interaction_and_message')) {
    function save_interaction_and_message(array $messageEntry, array $contact, array $metadata, array $fullPayload): int
    {
        // Initialize CodeIgniter instance
        $CI =& get_instance();

        $receiverId = $messageEntry['from'];
        $timestamp  = date("Y-m-d H:i:s", $messageEntry['timestamp']);

        // First extract the actual message payload
        $messageData = extract_message($messageEntry['type'], $messageEntry, null);

        // Look up an existing interaction
        $interaction = $CI->whatsapp_interaction_model->get_interaction(null, [
            'receiver_id' => $receiverId,
            'wa_no'       => $metadata['display_phone_number'],
            'wa_no_id'    => $metadata['phone_number_id'],
        ]);

        if ($interaction) {
            // Update existing interaction
            $interactionId = $interaction->id;
            $CI->whatsapp_interaction_model->update_interaction($interactionId, [
                'last_message'  => $messageData['message'] ?? $messageEntry['type'],
                'last_msg_time' => $timestamp,
            ]);
        } else {
            // Insert new interaction
            $interactionId = $CI->whatsapp_interaction_model->insert_interaction([
                'receiver_id'   => $receiverId,
                'name'          => $contact['profile']['name'] ?? null,
                'wa_no'         => $metadata['display_phone_number'],
                'wa_no_id'      => $metadata['phone_number_id'],
                'last_message'  => $messageData['message'] ?? $messageEntry['type'],
                'time_sent'     => $timestamp,
                'last_msg_time' => $timestamp,
            ]);
        }

        // Now save the message itself
        $messageInsertData = [
            'interaction_id' => $interactionId,
            'sender_id'      => $receiverId,
            'message_id'     => $messageEntry['id'],
            'ref_message_id' => $messageEntry['context']['id']
                                ?? $messageEntry['reaction']['message_id']
                                ?? null,
            'message'        => $messageData['message'] ?? $messageEntry['type'],
            'type'           => $messageEntry['type'],
            'staff_id'       => get_staff_user_id() ?? null,
            'url'            => $messageData['url'] ?? null,
            'status'         => 'delivered',
            'nature'         => 'received',
            'payload'        => json_encode($messageEntry, true),
            'time_sent'      => $timestamp,
        ];

        $CI->whatsapp_interaction_model->insert_interaction_message($messageInsertData);
        $CI->whatsapp_interaction_model->update_unread_count($interactionId);

        return $interactionId;
    }
}

/**
 * Extracts a user-friendly message from different WhatsApp message types.
 *
 * @param string $type The type of message (text, image, etc.)
 * @param array $messageEntry The message entry data
 * @return array Extracted message and URL if any
 */
if (!function_exists('extract_message')) {
    function extract_message($type, $messageEntry,$interactionId)
    {
        $data = [
            'message' => '',
            'url'     => null,
        ];
        switch ($type) {
            case 'text':
                $data['message'] = $messageEntry['text']['body'] ?? 'No text found';
                break;
            case 'image':
            case 'video':
            case 'document':
            case 'audio':
                $data = extract_media_message($type, $messageEntry,$interactionId);
                break;
            case 'location':
                $lat  = $messageEntry['location']['latitude'];
                $long = $messageEntry['location']['longitude'];
                $name = $messageEntry['location']['name'] ?? '';
                $data['message'] = "Location received: Lat: {$lat}, Long: {$long} ({$name})";
                break;
            case 'contacts':
                $formattedName = $messageEntry['contacts'][0]['name']['formatted_name'] ?? 'Unknown';
                $data['message'] = "Contact received: " . $formattedName;
                break;
            case 'interactive':
                $data['message'] = process_interactive_message($messageEntry,$interactionId);
                break;
            case 'reaction':
                $data['message'] = $messageEntry['reaction']['emoji'] ?? '';
                break;
            default:
                $data['message'] = $type; // fallback
                break;
        }
        return $data;
    }
}

/**
 * Extracts media details from image/video/document/audio message.
 *
 * @param string $type The type of media (image, video, etc.)
 * @param array $messageEntry The message entry data
 * @return array The extracted media details (message, URL)
 */
if (!function_exists('extract_media_message')) {
    function extract_media_message($type, $messageEntry,$interactionId)
    {
           $CI = get_instance();
     $data = [
            'message' => ucfirst($type) . ' received',
            'url'     => $messageEntry[$type]['link'] ?? null,
        ];
        if (isset($messageEntry[$type]['id'])) {
            $media_id     = $messageEntry[$type]['id'];
            $caption      = $messageEntry[$type]['caption'] ?? null;
            $access_token = get_option('whatsapp_access_token');
            $attachment   = $CI->WhatsappLibrary->retrieveUrl($media_id, $access_token,$interactionId);

            $data['message'] .= $caption ? " - $caption" : '';
            $data['url']      = $attachment ?: $data['url'];
        }
        return $data;
    }
}


/**
 * Processes interactive (button_reply / list_reply) messages.
 *
 * @param array $messageEntry The message entry data
 * @return string The processed message
 */
if (!function_exists('process_interactive_message')) {
    function process_interactive_message($messageEntry,$interactionId)
    {
        $interactive = $messageEntry['interactive'] ?? [];
        if (isset($interactive['button_reply'])) {
            return 'Button clicked: ' . ($interactive['button_reply']['title'] ?? 'Unknown button');
        } elseif (isset($interactive['list_reply'])) {
            return 'List option selected: ' . ($interactive['list_reply']['title'] ?? 'Unknown option');
        }
        return 'Unknown interactive response';
    }
}
