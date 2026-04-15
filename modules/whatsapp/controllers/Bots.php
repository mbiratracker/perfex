<?php

defined('BASEPATH') || exit('No direct script access allowed');

/**
 * Bots Controller
 * Handles functionality related to bots management.
 */
class Bots extends AdminController
{
    /**
     * Constructor
     * Loads necessary models.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['bots_model', 'campaigns_model']);
    }

    /**
     * Check if staff has the required permission.
     */
    private function check_permission($permission, $resource = 'whatsapp_bots')
    {
        if (!staff_can($permission, $resource)) {
            access_denied($resource);
        }
    }

    /**
     * Index method
     * Loads the main management page for bots.
     */
    public function index()
    {
        $this->check_permission('view', 'whatsapp_bots');

        $data['title'] = _l('bots');
        $this->load->view('bots/manage', $data);
    }

    /**
     * Table method
     * Loads the data for the specified table.
     *
     * @param string $table The table name.
     */
    public function table($table)
    {
        if (!$this->input->is_ajax_request()) {
            ajax_access_denied();
        }

        $this->check_permission('view', 'whatsapp_bots');

        $this->app->get_table_data(module_views_path(WHATSAPP_MODULE, 'tables/' . $table));
    }

    /**
     * Bot flow method
     * Manages bot flow creation and editing.
     */
    public function botflow($id = '')
    {
        $this->check_permission(empty($id) ? 'create' : 'edit', 'whatsapp_bots_flow');

        $data['title'] = _l('whatsapp_bots_flow');

        if (!empty($id)) {
            $data['flow'] = $this->bots_model->get($id);
        }

        $this->load->view('bots/botflow', $data);
    }

    /**
     * Form method
     * Loads the view for creating or editing a bot.
     *
     * @param string $id The ID of the bot (optional).
     */
    public function form($id = '')
    {
        $this->check_permission(empty($id) ? 'create' : 'edit', 'whatsapp_bots');

        $data['bot_types'] = get_whatsapp_bot_types();
        $data['templates'] = get_whatsapp_template();

        if (!empty($id)) {
            $data['bot'] = $this->bots_model->get($id);
            $data['bot']['header_params'] = !empty($data['bot']['header_params']) ? json_decode($data['bot']['header_params'], true) : [];
            $data['bot']['body_params'] = !empty($data['bot']['body_params']) ? json_decode($data['bot']['body_params'], true) : [];
            $data['bot']['footer_params'] = !empty($data['bot']['footer_params']) ? json_decode($data['bot']['footer_params'], true) : [];
            $data['bot']['flow_data'] = !empty($data['bot']['flow_data']) ? json_decode($data['bot']['flow_data'], true) : [];  // Fetch flow data
        } else {
            $data['bot'] = null;
            $data['bot']['flow_data'] = []; // Empty flow data for new bot creation
        }

        $data['title'] = isset($data['bot']) ? _l('edit_bot') : _l('create_bot');

        $this->load->view('bots/form_bot', $data);
    }

    /**
     * Save Bot method
     * Saves bot data from POST request.
     */
    public function saveBot()
    {
        // If no POST data, redirect to the bot listing page
        if (!$this->input->post()) {
            redirect(admin_url('whatsapp/bots'));
            return;
        }
    
        $postData = $this->input->post();
        $isEdit   = !empty($postData['id']);
        $permission = $isEdit ? 'edit' : 'create';
    
        // Check permission for either create or edit
        $this->check_permission($permission, 'whatsapp_bots');
    
        // Validate menu items if they exist
        if (isset($postData['menu_items']) && is_array($postData['menu_items'])) {
            $postData['menu_items'] = json_encode($postData['menu_items']);
        }
    
    
        // Save bot data via model method
        $res = $this->bots_model->saveBots($postData);
    
        // Handle result
        if ($res['status']) {
            // Save uploaded file if exists
            $filename = save_whatsapp_file($_FILES, 'bot', $res['id'], 'bot_file');
    
            // Update the bot record with the saved filename
            if ($filename) {
                $this->db->update(db_prefix() . 'whatsapp_bot', ['filename' => $filename], ['id' => $res['id']]);
            }
    
            // Set success alert
            set_alert('success', $res['message']);
        } else {
            // Set failure alert
            set_alert('danger', $res['message']);
        }
    
        // Redirect back to the bot listing page
        redirect(admin_url('whatsapp/bots'));
    }

    /**
     * Update Flow method
     * Updates flow data for a specific bot.
     */
    public function updateFlow()
    {
        if ($this->input->post()) {
            $flowData = json_decode($this->input->post('flow_data'), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                set_alert('danger', 'Invalid flow data');
                redirect(admin_url('whatsapp/bots'));
                return;
            }

            $botId = $this->input->post('id');
            $result = $this->bots_model->updateFlowData($botId, $flowData);

            if ($result) {
                set_alert('success', 'Flow data updated successfully');
            } else {
                set_alert('danger', 'Failed to update flow data');
            }

            redirect(admin_url('whatsapp/bots'));
        } else {
            redirect(admin_url('whatsapp/bots'));
        }
    }

    /**
     * Change Active Status method
     * Changes the active status of a bot.
     */
    public function change_active_status($id, $status)
    {
        if (!$this->input->is_ajax_request()) {
            ajax_access_denied();
        }

        $this->check_permission('edit', 'whatsapp_bots');

        $response = $this->bots_model->change_active_status($id, $status);
        echo json_encode($response);
    }

    /**
     * Delete Bot Files method
     * Deletes the files associated with a bot.
     */
    public function delete_bot_files($id)
    {
        $this->check_permission('delete', 'whatsapp_bots');

        $res = $this->bots_model->delete_bot_files($id);

        if ($res['status']) {
            set_alert('success', $res['message']);
        } else {
            set_alert('danger', $res['message']);
        }

        redirect(admin_url('whatsapp/bots/form/' . $id));
    }

    /**
     * Delete Bot method
     * Deletes a bot.
     */
    public function delete($id)
    {
        if (!$this->input->is_ajax_request()) {
            ajax_access_denied();
        }

        $this->check_permission('delete', 'whatsapp_bots');

        $response = $this->bots_model->deleteBot($id);
        echo json_encode($response);
    }

    /**
     * Duplicate Bot method
     * Duplicates an existing bot.
     */
    public function duplicate($id)
    {
        $this->check_permission('duplicate', 'whatsapp_bots');

        $botData = $this->bots_model->get($id);

        if (!$botData) {
            set_alert('danger', 'Bot not found.');
            redirect(admin_url('whatsapp/bots'));
        }

        unset($botData['id']);
        $botData['name'] .= ' (Copy)';
        $botData['is_bot_active'] = 0;

        $newBotId = $this->bots_model->saveBots($botData);

        if ($newBotId) {
            set_alert('success', 'Bot duplicated successfully.');
        } else {
            set_alert('danger', 'Failed to duplicate bot.');
        }

        redirect(admin_url('whatsapp/bots'));
    }
}
