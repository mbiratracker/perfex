<?php
defined('BASEPATH') || exit('No direct script access allowed');

use WpOrg\Requests\Requests as WhatsappRequests;
use Pusher\Pusher;

/**
 * Class Whatsapp
 *
 * Controller for WhatsApp integration functionalities.
 */
class Whatsapp extends AdminController
{
    /**
     * @var object
     */
    private $whatsappLibrary;

    /**
     * Constructor for Whatsapp controller.
     * Loads necessary models and libraries.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model([
            'whatsapp_interaction_model',
            'QuickReply_model',
            'leads_model',
            'staff_model'
        ]);
        $this->load->library('whatsappLibrary');
        $this->whatsappLibrary = new whatsappLibrary();
          // Grab the CI superobject so we can use $this->ci->db, etc.
        $this->ci =& get_instance();
  }

    /* =========================================================================
       VIEW HANDLERS
    ========================================================================= */

    /**
     * Default entry point.
     * Redirects to the chat view.
     */
    public function index()
    {
        if (!staff_can('view', 'whatsapp_chat')) {
            access_denied();
        }

        $data['title'] = _l('chat');
        $this->load->view('admin/interaction', $data);
    }
    public function connection()
    {
        if (!is_admin()) {
            access_denied('WhatsApp Connection Page');
        }

        $data['title'] = 'WA Cloud Connection';
        $this->load->view('settings/connection', $data);
    }

    /**
     * Loads the second version of the chat interface.
     */
    public function interaction_v2()
    {
        if (!staff_can('view', 'whatsapp_chat')) {
            access_denied();
        }

        $data['title'] = _l('chat');
        $this->load->view('chats/interaction_v2', $data);
    }

    /**
     * Displays the chat interactions view.
     */
    public function interaction()
    {
        if (!staff_can('view', 'whatsapp_chat')) {
            access_denied();
        }

        $data['title']         = _l('chat');
        $data['numbers']       = $this->whatsapp_interaction_model->get_numbers();
        $data['quick_replies'] = $this->QuickReply_model->get_all_replies();
        $data['staffs']        = $this->staff_model->get('', ['active' => 1]);
        $data['statuses']      = $this->leads_model->get_status();

        $this->load->view('chats/interaction', $data);
    }

    /**
     * Displays the numbers view.
     */
    public function numbers()
    {
        if (!staff_can('view', 'whatsapp_chat')) {
            access_denied();
        }

        $data['title'] = _l('numbers');


        $data['numbers'] = $this->db->get(db_prefix() . 'whatsapp_numbers')->result_array();
        $this->load->view('numbers/numbers', $data);
    }




    /**
     * Displays the WhatsApp activity log view.
     */
    public function activity_log()
    {
        if (!staff_can('view', 'whatsapp_log_activity')) {
            access_denied('activity_log');
        }
        $data['title'] = _l('activity_log');
        $this->load->view('activity_log/whatsapp_activity_log', $data);
    }

    /**
     * Displays detailed log information for a specific log entry.
     *
     * @param string $id Log ID.
     */
    public function view_log_details($id = '')
    {
        $data['title']    = _l('activity_log');
        $data['log_data'] = $this->whatsapp_interaction_model->getWhatsappLogDetails($id);
        $this->load->view('activity_log/view_log_details', $data);
    }

    /**
     * Displays the contacts view.
     */
    public function contacts()
    {
        $data['title']    = _l('contacts');
        $data['contacts'] = $this->whatsapp_interaction_model->get_contacts();
        $this->load->view('contacts', $data);
    }

    /**
     * Displays the documentation view.
     */
    public function documentation()
    {
        $data['title'] = _l('documentation');
        $this->load->view('documentation', $data);
    }
public function updates()
{
    $this->load->library(['session', 'zip']);
    $this->load->helper('file');
    
    $module_name = 'whatsapp-cloud-api-chat-module';
    $license_key = get_option('module_purchase_key_whatsapp');
    
    $data['title'] = _l('updates');
    $data['error'] = null;
    $data['module_info'] = null;
    $data['applied'] = false;
    $data['current_version'] = defined('WHATSAPP_VERSION') ? WHATSAPP_VERSION : '0.0.0';
    
    // Validate license key
    if (empty($license_key)) {
        $data['error'] = 'Invalid or missing purchase key for WhatsApp module.';
        $this->load->view('updates', $data);
        return;
    }

    // Include current version in update API request
    $update_url = WHATSAPP_UPDATE_URI;

    // Prepare the query parameters as an associative array
    $query_params = [
        'license' => $license_key,
        'current_version' => $data['current_version']
    ];

    // Build the URL with properly encoded parameters
    $update_url .= '?' . http_build_query($query_params);
    
    // Fetch remote update info (with error handling)
    $response = $this->make_remote_request($update_url);
    if (!$response['success']) {
        $data['error'] = 'Failed to connect to the update server: ' . $response['message'];
        log_message('error', 'Update request failed: ' . $response['message']);
        $this->load->view('updates', $data);
        return;
    }
    
    $parsed = json_decode($response['data'], true);
    if (!is_array($parsed) || empty($parsed['status']) || empty($parsed['data'])) {
        $data['error'] = 'Invalid response from update server.';
        log_message('error', 'Invalid update server response: ' . json_encode($parsed)); 
        $this->load->view('updates', $data);
        return;
    }
    
    $module_info = $parsed['data'];
    $all_versions = array_merge(
        $module_info['versions']['stable'],
        $module_info['versions']['beta']
    );
    
    // Filter versions newer than current
    $updates = array_filter($all_versions, function ($v) use ($data) {
        return version_compare($v['version'], $data['current_version'], '>');
    });
    
    // Sort descending by version
    usort($updates, function ($a, $b) {
        return version_compare($b['version'], $a['version']);
    });
    
    // Prepare module data for the view
    $data['module_info'] = $module_info;
    $data['updates'] = $updates;
    $data['is_latest'] = empty($updates); // True if user is on the latest version

    // Always include license-related data
    $license_data = isset($module_info['license']) ? $module_info['license'] : null;
    if ($license_data) {
        $data['license_data'] = [
            'license_type' => $license_data['license_type'] ?? 'N/A',
            'buyer_name' => $license_data['buyer']['username'] ?? 'N/A',
            'purchase_date' => $license_data['sold_at'] ?? 'N/A',
            'support_until' => $license_data['supported_until'] ?? 'N/A',
        ];
    }

    // Preview the update before applying
    $preview = $this->input->get('preview');
    if ($preview) {
        // Fetch the preview details for the selected version
        $selected_version = $this->input->get('preview');
        $selected_update = null;

        foreach ($updates as $v) {
            if ($v['version'] === $selected_version) {
                $selected_update = $v;
                break;
            }
        }

        if ($selected_update) {
            // Pass selected version data to view
            $data['preview_version'] = $selected_update['version'];
            $data['preview_changelog'] = $selected_update['changelog'];
            $data['preview_release_date'] = $selected_update['release_at'];
            $data['preview_download_link'] = $selected_update['download'];
        } else {
            $data['error'] = 'Selected update version not found.';
        }
    }
    
    // Handle update via query param (?apply=x.x.x)
    $apply = $this->input->get('apply');
    if ($apply) {
        foreach ($updates as $v) {
            if ($v['version'] === $apply && !empty($v['download'])) {
                $module_path = FCPATH . 'modules/' . $module_name;
                $result = $this->apply_module_update($v['download'], $module_path);
        
                if ($result['success']) {
                    $this->session->set_flashdata('success', 'Module updated to version ' . $v['version']);
                    log_message('info', 'Module updated to version ' . $v['version']);
                    redirect(admin_url('modules/upgrade_database/whatsapp'));
                } else {
                    $data['error'] = 'Update failed: ' . $result['message'];
                    log_message('error', 'Update failed: ' . $result['message']);
                }
                break;
            }
        }
    }
    
    // Final view rendering with improved feedback
    if ($data['is_latest']) {
        $this->session->set_flashdata('info', 'You are using the latest version.');
    }

    // Pass the data to the view
    $this->load->view('updates', $data);
}


/**
 * Secure download + extract zip
 */
private function apply_module_update($url, $module_path)
{
    $this->load->helper('file');

    $temp_zip = FCPATH . 'temp_update_' . time() . '.zip';

    try {
        $zip_content = @file_get_contents($url);
        if (!$zip_content) {
            return ['success' => false, 'message' => 'Unable to download update file.'];
        }

        if (!write_file($temp_zip, $zip_content)) {
            return ['success' => false, 'message' => 'Failed to save update file to server.'];
        }

        $zip = new ZipArchive;
        if ($zip->open($temp_zip) === TRUE) {
            if (!is_dir($module_path)) {
                mkdir($module_path, 0755, true);
            }

            $zip->extractTo($module_path);
            $zip->close();
            unlink($temp_zip);

            // Optional: Clear cache if needed
            if (function_exists('delete_dir_contents')) {
                delete_dir_contents(APPPATH . 'cache');
            }

            return ['success' => true, 'message' => 'Module updated successfully.'];
        }

        unlink($temp_zip);
        return ['success' => false, 'message' => 'Failed to extract the ZIP file.'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Helper for remote requests
 */
private function make_remote_request($url)
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'PerfexDev360 Updater',
    ]);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || !$response) {
        return ['success' => false, 'message' => 'Failed to contact update server. ' . $error];
    }

    return ['success' => true, 'data' => $response];
}


    public function save_settings()
    {
        // Check if it's an AJAX request or a POST request
        if ($this->input->is_ajax_request() || $this->input->post()) {
            // Get the settings data from POST
            $data = $this->input->post('settings');

            // Validate if $data is an array
            if (is_array($data)) {
                // Iterate through each setting and update
                foreach ($data as $key => $value) {
                    // Trim any whitespace from the value before saving
                    $value = trim($value);

                    if (update_option($key, $value)) {
                        // Log success (optional logging for debugging purposes)
                        log_message('info', 'Setting updated: ' . $key . ' = ' . $value);
                    } else {
                        // Log failure (optional logging)
                        log_message('error', 'Failed to update setting: ' . $key);
                    }
                }

                // Respond with success message
                echo json_encode([
                    'success' => true,
                    'message' => _l('settings_updated'),
                ]);
                return;
            }
        }

        // If the data is not valid, return an error
        echo json_encode([
            'success' => false,
            'message' => _l('invalid_request'),
        ]);
    }


    /**
     * Sets the default WhatsApp phone number.
     * Ensures only one default exists by resetting all and marking the selected one.
     */
    public function set_default_phone_number()
    {
        // Only allow GET requests with ID param
        $phone_number_id = $this->input->get('id', true);

        if (!$phone_number_id) {
            show_error(_l('Invalid phone number ID'), 400);
        }

        // Begin DB transaction
        $this->db->trans_start();

        try {
            // Reset all to non-default
            $this->db->update(db_prefix() . 'whatsapp_numbers', ['is_default' => 0]);

            // Check if the requested phone number exists
            $existing = $this->db->where('id', $phone_number_id)->get(db_prefix() . 'whatsapp_numbers')->row();

            if ($existing) {
                // Set requested number as default
                $this->db->where('id', $phone_number_id)->update(db_prefix() . 'whatsapp_numbers', ['is_default' => 1]);
            } else {
                // Set the first available number as default
                $first = $this->db->order_by('id', 'ASC')->limit(1)->get(db_prefix() . 'whatsapp_numbers')->row();

                if ($first) {
                    $this->db->where('id', $first->id)->update(db_prefix() . 'whatsapp_numbers', ['is_default' => 1]);
                    set_alert('warning', _l('Selected number not found. First number marked as default.'));
                } else {
                    // No numbers exist
                    $this->db->trans_rollback();
                    set_alert('danger', _l('No phone numbers available to set as default'));
                    redirect(admin_url('whatsapp/numbers')); // fallback URL
                    return;
                }
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === false) {
                set_alert('danger', _l('Failed to update default phone number.'));
            } else {
                set_alert('success', _l('Default phone number updated successfully.'));
            }
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Error updating default phone number: ' . $e->getMessage());
            set_alert('danger', _l('An error occurred while updating the default phone number.'));
        }

        redirect($_SERVER['HTTP_REFERER'] ?? admin_url('whatsapp/numbers'));
    }

    /*** Updates the profile data for a given phone number.
     * Uploads a new profile picture if provided and updates the profile data.
     */
    public function update_profile()
    {
        if ($this->input->post()) {
            $profileData = $this->input->post();
            $accessToken = get_option('whatsapp_access_token');
            $phoneNumberId = $profileData['phone_number_id'];



            $response = $this->whatsappLibrary->updateProfile($profileData, $phoneNumberId, $accessToken);

            redirect(admin_url('whatsapp/numbers'));
        }
    }

public function initiate_message()
{
    $this->load->library('whatsappLibrary');
    
    // 1️⃣ Sanitize and validate input
    $phone_number   = $this->input->post('phone_number', true);
    $from_number_id = $this->input->post('from_number_id', true);
    $template_id    = $this->input->post('template_id', true);

    if (empty($phone_number) || empty($template_id) || empty($from_number_id)) {
        return $this->json_response(false, 'Sender number, recipient phone number, and template ID are required.');
    }

    // 2️⃣ Get sender WhatsApp number
    $wa_number_data = get_whatsapp_number_data($from_number_id);
    if (empty($wa_number_data)) {
        return $this->json_response(false, 'Invalid sender WhatsApp number ID.');
    }

    // 3️⃣ Insert interaction (Add error checking)
    $interactionData = [
        'receiver_id' => $phone_number,
        'wa_no_id'    => $from_number_id,
        'wa_no'       => $wa_number_data['phone_number'],
        'name'=>"-"
    ];

    $interaction_id = $this->whatsapp_interaction_model->insert_interaction($interactionData);


    // 4️⃣ Retrieve full interaction (Add validation)
    $interaction = $this->whatsapp_interaction_model->get_interaction($interaction_id);
 if (empty($interaction)) {
        log_message('error', 'Failed to fetch interaction data.');
        return $this->json_response(false, 'Failed to fetch interaction data.');
    }

    // 5️⃣ Load template (Check if template is valid and approved)
    $template = get_whatsapp_template($template_id);
    if (empty($template)) {
        log_message('error', 'Invalid or unapproved template.');
        return $this->json_response(false, 'Invalid or unapproved template.');
    }

    $interaction = array_merge($interaction, $template);

    // 6️⃣ Prepare WhatsApp message payload
    $message_data = $this->whatsapplibrary->prepare_template_message_data($phone_number, $interaction);

    if (empty($message_data)) {
        log_message('error', 'Failed to prepare message content.');
        return $this->json_response(false, 'Failed to prepare message content.');
    }

    // Log activity data (Add validation for message data)
    $activityLog = [
        'phone_number_id'      => $from_number_id,
        'business_account_id'  => $wa_number_data['whatsapp_business_account_id'] ?? null,
        'category'             => 'send_message',
        'category_id'          => $template_id,
        'rel_type'             => 'interaction',
        'rel_id'               => $interaction_id,
        'category_params' => json_encode([
            'template_name' => $interaction['template_name'] ?? '',
            'phone'         => $phone_number,
            'message_data'  => $message_data
        ]),
        'raw_data'             => json_encode($message_data),
        'recorded_at'          => date('Y-m-d H:i:s'),
    ];

    // 7️⃣ Send the message
    $response = $this->whatsapplibrary->send_message(
        $from_number_id,
        $phone_number,
        $message_data,
        $activityLog
    );

    log_message('error', '📨 WhatsApp Send Response: ' . print_r($response, true));

    // Normalize response
    $response['response_data'] = $response['response_data'] ?? [];
    $is_successful = !empty($response['success']);

    // 8️⃣ Build error message (if any)
    $status_message = '';
    if (!$is_successful && isset($response['response_data']['error'])) {
        $err = $response['response_data']['error'];
        $status_message = ($err['message'] ?? 'Failed') .
                          (!empty($err['error_data']['details']) ? ' - ' . $err['error_data']['details'] : '');
    }

    // 9️⃣ Save interaction message history
    $this->whatsapp_interaction_model->prepare_chat_message($response, $interaction, 'message');

    // 🔟 Save activity log (to `whatsapp_activity_log`)
    if (!empty($response['log_data'])) {
        $this->db->insert(db_prefix() . 'whatsapp_activity_log', $response['log_data']);
    } else {
        log_message('error', 'No log data available for activity log insertion.');
    }

    // ✅ Final JSON response
    return $this->json_response($is_successful, $is_successful ? 'Message sent successfully.' : $status_message);
}


protected function json_response(bool $success, string $message = '', array $data = [], int $status = 200)
{
    $CI =& get_instance();
    $CI->output
        ->set_status_header($status)
        ->set_content_type('application/json')
        ->set_output(json_encode([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
        ], JSON_UNESCAPED_UNICODE));
}

    /**
     * Generates an AI reply for a given interaction and message.
     */
    public function get_aireply()
    {
        $interaction_id = $this->input->get('interaction_id');
        $message        = $this->input->get('message');

        if (!$interaction_id || !is_numeric($interaction_id)) {
            echo json_encode(['error' => 'Invalid interaction ID']);
            return;
        }

        if (empty($message)) {
            echo json_encode(['error' => 'Message content is empty']);
            return;
        }

        $ai_reply = getAIReply($interaction_id, null, $message);

        if ($ai_reply === null) {
            echo json_encode(['error' => 'Failed to generate AI response']);
            return;
        }

        echo json_encode(['reply' => $ai_reply]);
    }

    /**
     * Returns a mapped template using the provided template ID and type.
     */
    public function get_template_map()
    {
        if ($this->input->is_ajax_request()) {
            $this->load->helper('whatsapp');

            $templateId = $this->input->post('template_id');
            $type       = $this->input->post('type');
            $type_id    = $this->input->post('type_id') ?? null;

            $result = get_template_maper($templateId, $type, $type_id);

            echo json_encode($result);
        }
    }

    /**
     * Fetches and returns filtered interactions as JSON.
     */
    public function interactions()
    {
        $filters = [
            'wa_no_id'           => $this->input->get('wa_no_id'),
            'interaction_type'   => $this->input->get('interaction_type'),
            'status_id'          => $this->input->get('status_id'),
            'assigned_staff_id'  => $this->input->get('assigned_staff_id'),
            'status'             => $this->input->get('status'),
            'status_id'             => $this->input->get('status_id'),
            'searchtext'         => $this->input->get('searchtext')
        ];

        $data['interactions'] = $this->whatsapp_interaction_model->get_interactions($filters);
        $data['total_count']  = $this->whatsapp_interaction_model->get_interactions_count($filters);

        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Retrieves and returns messages for a specific interaction.
     */
    public function get_interaction_messages()
    {
        $interaction_id = $this->input->get('interaction_id');
        $limit          = isset($_GET['limit']) ? (int)$this->input->get('limit') : 20;
        $offset         = isset($_GET['offset']) ? (int)$this->input->get('offset') : 0;

        $messages = $this->whatsapp_interaction_model->get_interaction_messages($interaction_id, $limit, $offset);

        header('Content-Type: application/json');
        echo json_encode(['messages' => $messages]);
        exit;
    }

    /* =========================================================================
       CHAT AND ACTIVITY HANDLERS
    ========================================================================= */

    /**
     * Marks messages as read for a given interaction.
     */
    public function chat_mark_as_read()
    {
        $id = $this->input->post('interaction_id');
        $this->whatsapp_interaction_model->chat_received_messages_mark_as_read($id);
    }

    /**
     * Clears the activity log.
     */
    public function clear_log()
    {
        if (staff_can('clear_log', 'whatsapp_log_activity')) {
            $this->db->truncate(db_prefix() . 'whatsapp_activity_log');
            set_alert('danger', _l('log_cleared_successfully'));
        }
        redirect(admin_url('whatsapp/activity_log'));
    }

    /**
     * Deletes a specific log entry.
     *
     * @param int $id Log ID.
     */
    public function delete_log($id)
    {
        if (staff_can('clear_log', 'whatsapp_log_activity')) {
            $delete = $this->whatsapp_interaction_model->delete_log($id);
            set_alert('danger', $delete ? _l('deleted', _l('log')) : _l('something_went_wrong'));
        }
        redirect(admin_url('whatsapp/activity_log'));
    }

    /**
     * Deletes a chat interaction and its associated files.
     */
    public function delete_chat()
    {
        $this->load->model('whatsapp_interaction_model');
        $chat_id = $this->input->post('chat_id');

        if ($chat_id && is_numeric($chat_id)) {
            $chat_id = (int) $chat_id;

            // Load the DB and file helpers
            $CI = &get_instance();
            $CI->load->database();
            $CI->load->helper('file'); // helper for file operations

            $messages_table = db_prefix() . 'whatsapp_interaction_messages';

            // Fetch messages if needed for further cleanup
            $messages = $CI->db->get_where($messages_table, ['interaction_id' => $chat_id])->result();

            // Define the path to the interaction folder
            $interaction_dir = get_upload_path_by_type('interactions/' . $chat_id);

            // Recursively delete all files within the directory
            if (is_dir($interaction_dir)) {
                delete_files($interaction_dir, true); // CI file helper
                @rmdir($interaction_dir);
            }

            // Delete interaction record(s) from database via model
            $result = $this->whatsapp_interaction_model->delete_interaction($chat_id);

            if (!empty($result['success']) && $result['success'] === true) {
                $response = ['success' => true, 'message' => 'Chat and related files deleted successfully.'];
                $this->output->set_status_header(200);
            } else {
                $response = [
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to delete chat. Please try again.'
                ];
                $this->output->set_status_header(500);
            }
        } else {
            $response = ['success' => false, 'message' => 'Chat ID is required and must be numeric.'];
            $this->output->set_status_header(400);
        }

        echo json_encode($response);
    }



    /**
     * Toggles the pinned state of a chat interaction.
     */
    public function pinorunpin()
    {
        $this->load->model('whatsapp_interaction_model');

        $chatId = $this->input->get('chat_id');
        if (!isset($chatId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
            return;
        }

        $chat = $this->db->where('id', $chatId)->get(db_prefix() . 'whatsapp_interactions')->row();
        if (!$chat) {
            echo json_encode(['success' => false, 'error' => 'Chat ID does not exist.']);
            return;
        }

        $newPinnedState = ($chat->is_pinned === '1') ? '0' : '1';
        $this->db->where('id', $chatId);
        $this->db->update(db_prefix() . 'whatsapp_interactions', ['is_pinned' => $newPinnedState]);

        if ($this->db->affected_rows() > 0) {
            echo json_encode(['success' => true]);
        } else {
            $error = $this->db->error();
            echo json_encode([
                'success' => false,
                'error' => 'Unable to update pinned state or no changes made. ' . ($error['message'] ?? ''),
                'chatId' => $chatId,
                'isPinned' => $newPinnedState
            ]);
        }
    }

    /**
     * Saves a new tag for a chat interaction.
     */
    public function save_tag()
    {
        if (!$this->input->is_ajax_request() || !$this->input->post()) {
            show_404();
        }

        $interaction_id = $this->input->post('interaction_id');
        $new_tag = trim($this->input->post('tag'));

        if (empty($interaction_id) || empty($new_tag)) {
            echo json_encode(['success' => false, 'message' => 'Interaction ID and tag are required.']);
            return;
        }

        $interaction = $this->db->select('tags')
            ->where('id', $interaction_id)
            ->get(db_prefix() . 'whatsapp_interactions')
            ->row();

        if (!$interaction) {
            echo json_encode(['success' => false, 'message' => 'Interaction not found.']);
            return;
        }

        $tags = array_map('strtolower', array_filter(array_map('trim', explode(',', $interaction->tags))));
        $new_tag_normalized = strtolower($new_tag);

        if (in_array($new_tag_normalized, $tags)) {
            echo json_encode(['success' => false, 'message' => 'Tag already exists for this interaction.']);
            return;
        }

        $tags[] = $new_tag_normalized;
        $updated_tags = implode(',', $tags);

        $this->db->where('id', $interaction_id)
            ->update(db_prefix() . 'whatsapp_interactions', ['tags' => $updated_tags]);

        if ($this->db->affected_rows() > 0) {
            echo json_encode(['success' => true, 'message' => 'Tag added successfully.', 'updated_tags' => $tags]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save the tag.']);
        }
    }

    /**
     * Deletes a tag from a chat interaction.
     */
    public function delete_tag()
    {
        if (!$this->input->is_ajax_request() || !$this->input->post()) {
            show_404();
        }

        $interaction_id = $this->input->post('interaction_id');
        $tag_to_remove = trim($this->input->post('tag'));

        if (empty($interaction_id) || empty($tag_to_remove)) {
            echo json_encode(['success' => false, 'message' => 'Interaction ID and tag are required.']);
            return;
        }

        $interaction = $this->db->select('tags')
            ->where('id', $interaction_id)
            ->get(db_prefix() . 'whatsapp_interactions')
            ->row();

        if (!$interaction) {
            echo json_encode(['success' => false, 'message' => 'Interaction not found.']);
            return;
        }

        $tags = array_map('strtolower', array_filter(array_map('trim', explode(',', $interaction->tags))));
        $tag_to_remove_normalized = strtolower($tag_to_remove);

        if (!in_array($tag_to_remove_normalized, $tags)) {
            echo json_encode(['success' => false, 'message' => 'Tag not found in this interaction.']);
            return;
        }

        $tags = array_filter($tags, fn($tag) => $tag !== $tag_to_remove_normalized);
        $updated_tags = implode(',', $tags);

        $this->db->where('id', $interaction_id)
            ->update(db_prefix() . 'whatsapp_interactions', ['tags' => $updated_tags]);

        if ($this->db->affected_rows() > 0) {
            echo json_encode(['success' => true, 'message' => 'Tag deleted successfully.', 'updated_tags' => $tags]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete the tag.']);
        }
    }
    public function activity_log_table()
    {
        if (!$this->input->is_ajax_request()) {
            ajax_access_denied();
        }

        $this->app->get_table_data(module_views_path(WHATSAPP_MODULE, 'tables/activity_log_table'));
    }
    public function debug_token()
    {
        $token = $this->input->post('token');
        $url = "https://graph.facebook.com/debug_token?input_token={$token}&access_token={$token}";
        echo $this->curl_get($url);
    }

public function connection_status()
{
    $this->load->library('WhatsappLibrary');
    $response = $this->whatsapplibrary->connection_status();
    echo json_encode($response);
}

public function register_webhook()
{
    $this->load->library('WhatsappLibrary');
    $response = $this->whatsapplibrary->register_webhook();
    echo json_encode($response);
}

public function unregister_webhook()
{
    $this->load->library('WhatsappLibrary');
    try {
        $response = $this->whatsapplibrary->unregister_webhook();
        echo json_encode($response);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

public function disconnect()
{
    if (!$this->input->is_ajax_request() || !is_admin()) {
        show_404();
    }

    $this->load->library('WhatsappLibrary');
    $response = $this->whatsapplibrary->disconnect();
    echo json_encode($response);
}


    private function curl_get($url)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    private function curl_post($url, $data)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    private function curl_delete($url)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => 'DELETE'
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    public function oauth_redirect()
    {
        $app_id = get_option('whatsapp_meta_app_id');
        $redirect_uri = base_url('whatsapp/webhook/oauth_callback');
        $state = bin2hex(random_bytes(8));
        $this->session->set_userdata('whatsapp_oauth_state', $state);

        $scopes = [
            'whatsapp_business_management',
            'whatsapp_business_messaging',
            'business_management',
            'pages_show_list',
            'email'
        ];

        $query = [
            'client_id' => $app_id,
            'redirect_uri' => $redirect_uri,
            'state' => $state,
            'scope' => implode(',', $scopes),
            'response_type' => 'code'
        ];

        $url = 'https://www.facebook.com/v20.0/dialog/oauth?' . http_build_query($query);
        log_message('error', '[OAuth Redirect] URL: ' . $url);
        redirect($url);
    }
}
