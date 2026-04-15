<?php

defined('BASEPATH') || exit('No direct script access allowed');

/**
 * WhatsApp Campaigns Controller
 */
class Campaigns extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model([
            'campaigns_model',
            'leads_model',
            'clients_model',
            'Group_model',
        ]);
    }

    /**
     * View all campaigns
     */
    public function index()
    {
        $this->_check_permission('view');
        $data['title'] = _l('campaigns');
        $this->load->view('campaigns/manage', $data);
    }

    /**
     * Create or edit a campaign
     */
    public function campaign($id = '')
    {
        $this->_check_permission(empty($id) ? 'create' : 'edit');

        $data['title']      = _l('campaigns');
        $data['leads']      = $this->leads_model->get();
        $data['contacts']   = $this->clients_model->get_contacts();
        $data['groups']     = $this->Group_model->get_all_groups();
        $data['templates']  = get_whatsapp_template();
$data['whatsapp_numbers'] = get_whatsapp_number_data(); // ✅ Fetch all numbers

        if (!empty($id)) {
            $data['campaign'] = $this->campaigns_model->get($id);
            $rel = $data['campaign']['rel_type'];
            $data['campaign'][$rel . '_ids'] = json_decode($data['campaign']['rel_ids'] ?? '[]', true);
        }

        $this->load->view('campaigns/campaign', $data);
    }

    /**
     * Save a campaign (create or update)
     */
    public function save()
    {
        $postData  = $this->input->post();
        $isEdit    = !empty($postData['id']);
        $this->_check_permission($isEdit ? 'edit' : 'create');

        $res         = $this->campaigns_model->save($postData);
        $campaign_id = $res['campaign_id'] ?? null;

        // Handle file upload
        if ($campaign_id && !empty($_FILES['filename']['name'])) {
            $filename = save_whatsapp_file($_FILES, 'campaign', $campaign_id, 'filename');
            if ($filename) {
                $this->db->update(db_prefix() . 'whatsapp_campaigns', ['filename' => $filename], ['id' => $campaign_id]);
            } else {
                log_message('error', '[WhatsApp Campaign] Upload failed for ID: ' . $campaign_id);
            }
        }

        set_alert($res['type'], $res['message']);
        redirect(admin_url('whatsapp/campaigns'));
    }

    /**
     * AJAX table data
     */
    public function get_table_data($table, $id = '', $rel_type = '')
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $this->app->get_table_data(module_views_path(WHATSAPP_MODULE, 'tables/' . $table), compact('id', 'rel_type'));
    }

    /**
     * Delete a campaign
     */
    public function delete($id)
    {
        $this->_check_permission('delete');
        $res = $this->campaigns_model->delete($id);
        set_alert('danger', $res['message']);
        redirect(admin_url('whatsapp/campaigns'));
    }

    /**
     * View single campaign
     */
    public function view($id)
    {
        $this->_check_permission('show');

        $data['title']     = _l('view_campaign');
        $data['campaign']  = $this->campaigns_model->get($id);
        $total_relations   = [
            'leads'    => total_rows(db_prefix() . 'leads'),
            'contacts' => total_rows(db_prefix() . 'contacts'),
        ];

        $countSelected = count(json_decode($data['campaign']['rel_ids'] ?? '[]', true));
        $type          = $data['campaign']['rel_type'];

        $data['total_percent'] = $countSelected && isset($total_relations[$type])
            ? number_format(($countSelected / $total_relations[$type]) * 100, 2)
            : 0;

        $data['delivered_to_count']   = total_rows(db_prefix() . 'whatsapp_campaign_data', ['status' => 2, 'campaign_id' => $id]);
        $data['read_by_count']        = total_rows(db_prefix() . 'whatsapp_campaign_data', ['message_status' => 'read', 'campaign_id' => $id]);
        $data['delivered_to_percent'] = $data['read_by_percent'] = 0;

        if ($data['delivered_to_count'] > 0) {
            $data['delivered_to_percent'] = number_format(($data['delivered_to_count'] / $countSelected) * 100, 2);
            $data['read_by_percent']      = number_format(($data['read_by_count'] / $data['delivered_to_count']) * 100, 2);
        }

        $this->load->view('campaigns/view', $data);
    }

    /**
     * Toggle pause/resume on a campaign
     */
    public function pause_resume_campaign($id)
    {
        $res = $this->campaigns_model->pause_resume_campaign($id);
        set_alert('success', $res['message']);
        redirect(admin_url('whatsapp/campaigns/view/' . $id));
    }

    /**
     * Delete campaign's uploaded file
     */
    public function delete_campaign_files($id)
    {
        $res = $this->campaigns_model->delete_campaign_files($id);
        set_alert('danger', $res['message']);
        redirect(admin_url('whatsapp/campaigns'));
    }

    /**
     * Load template mapping for the selected template
     */
    public function get_template_map()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $this->load->helper('whatsapp');
        $templateId = $this->input->post('template_id');
        $type       = $this->input->post('type') ?? 'campaign';
        echo json_encode(get_template_maper($templateId, $type));
    }

    /**
     * Get filtered leads for dynamic dropdowns
     */
    public function get_filtered_leads()
    {
        $filters = [
            'status'   => $this->input->post('status_id'),
            'assigned' => $this->input->post('assigned_id'),
            'source'   => $this->input->post('source_id'),
        ];

        echo json_encode($this->campaigns_model->get_filtered_leads($filters));
    }

    /**
     * Permission check utility
     */
    private function _check_permission($action)
    {
        if (!staff_can($action, 'whatsapp_campaign')) {
            access_denied();
        }
    }
}
