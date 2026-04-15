<?php
hooks()->add_action('subscription_created', 'wa_subscription_added_hook');
function wa_subscription_added_hook($subId)
{
    $CI = &get_instance();
    $CI->whatsapp_api_lib->send_mapped_template('subscriptions', false, 'MANUAL', $subId);
}

hooks()->add_action('lead_created', 'wa_lead_added_hook');
function wa_lead_added_hook($leadID)
{
    if (!is_array($leadID)) {
        $CI = &get_instance();
        $CI->whatsapp_api_lib->send_mapped_template('leads', false, 'MANUAL', $leadID);
    }
}

hooks()->add_action('web_to_lead_form_submitted', 'wa_web_to_lead_added_hook');
function wa_web_to_lead_added_hook($data)
{
    $CI = &get_instance();
    $CI->whatsapp_api_lib->send_mapped_template('leads', false, 'MANUAL', $data['lead_id']);
}

hooks()->add_action('contact_created', 'wa_contact_added_hook');
function wa_contact_added_hook($contactID)
{
    $CI = &get_instance();
    $CI->whatsapp_api_lib->send_mapped_template('client', false, 'MANUAL', $contactID);
}

hooks()->add_action('after_invoice_added', 'wa_invoice_added_hook');
function wa_invoice_added_hook($invoiceID)
{
    $CI = &get_instance();
    $CI->whatsapp_api_lib->send_mapped_template('invoice', false, 'MANUAL', $invoiceID);
}

hooks()->add_action('after_add_task', 'wa_task_added_hook');
function wa_task_added_hook($taskID)
{
    $CI = &get_instance();
    $CI->whatsapp_api_lib->send_mapped_template('tasks', false, 'MANUAL', $taskID);
}

hooks()->add_action('after_add_project', 'wa_project_added_hook');
function wa_project_added_hook($projectID)
{
    $CI = &get_instance();
    $CI->whatsapp_api_lib->send_mapped_template('projects', false, 'MANUAL', $projectID);
}

hooks()->add_action('proposal_created', 'wa_proposal_added_hook');
function wa_proposal_added_hook($proposalID)
{
    $CI = &get_instance();
    $CI->whatsapp_api_lib->send_mapped_template('proposals', false, 'MANUAL', $proposalID);
}
hooks()->add_action('after_payment_added', 'wa_payment_added_hook');
function wa_payment_added_hook($paymentID)
{
    $CI = &get_instance();
    $CI->whatsapp_api_lib->send_mapped_template('payments', false, 'MANUAL', $paymentID);
}
hooks()->add_action('ticket_created', 'wa_ticket_created_hook');
function wa_ticket_created_hook($ticketID)
{
    $CI = &get_instance();
    $CI->whatsapp_api_lib->send_mapped_template('ticket', false, 'MANUAL', $ticketID);
}

hooks()->add_action('after_cron_run', 'send_reminder_whatsapp');
function send_reminder_whatsapp($manual)
{
    $CI = &get_instance();
    
    $CI->db->select('' . db_prefix() . 'reminders.*, email, phonenumber');
    $CI->db->join(db_prefix() . 'staff', '' . db_prefix() . 'staff.staffid=' . db_prefix() . 'reminders.staff');
    $CI->db->where('whatsapp_notified', 0);
    $reminders     = $CI->db->get(db_prefix() . 'reminders')->result_array();

    foreach ($reminders as $reminder) {
        if (date('Y-m-d H:i:s') >= $reminder['date']) {
            $CI->whatsapp_api_lib->send_mapped_template('staff_reminder', false, 'MANUAL', $reminder['id']);
            $CI->db->where('id', $reminder['id']);
            $CI->db->update(db_prefix() . 'reminders', [
                'whatsapp_notified' => 1,
            ]);
        }
    }
}
