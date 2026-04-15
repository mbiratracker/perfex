<?php

defined('BASEPATH') || exit('No direct script access allowed');

$aColumns = [
    'id',
    'name',
    'template_id',
    'rel_type',
    'number_id', // ✅ Added to fetch the WhatsApp number
    '1', // campaign recipients count
    '1', // messages sent
    '1', // messages read
];

$sIndexColumn = 'id';
$sTable       = db_prefix().'whatsapp_campaigns';

$where = [];

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, [], $where);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    $row[] = $aRow['id'];

    if (staff_can('show', 'whatsapp_campaign')) {
        $row[] = '<a href="' . admin_url('whatsapp/campaigns/view/' . $aRow['id']) . '">' . $aRow['name'] . '</a>';
    } else {
        $row[] = $aRow['name'];
    }

    // Get template name
    $template = get_whatsapp_template($aRow['template_id']);
    $row[] = $template && isset($template['template_name']) ? $template['template_name'] : '<span class="text-danger">Template Missing</span>';

    // Rel type label with color
    $color = ($aRow['rel_type'] == 'leads' ? '#3a25e9' : ($aRow['rel_type'] == 'contacts' ? '#ff4646' : '#7bf565'));
    $row[] = '<span class="label" style="color:' . $color . ';border:1px solid ' . adjust_hex_brightness($color, 0.4) . ';background: ' . adjust_hex_brightness($color, 0.04) . ';">' . _l($aRow['rel_type']) . '</span>';

    // ✅ Get assigned WhatsApp number
    $number = get_whatsapp_number_data($aRow['number_id']);
    $row[] = $number ? $number["verified_name"].":".$number["phone_number"] : '<span class="text-danger">Number Missing</span>';

    // Recipient count
    $row[] = count(whatsapp_get_campaign_data($aRow['id']));

    // Sent
    $row[] = total_rows(db_prefix() . 'whatsapp_campaign_data', ['status' => 2, 'campaign_id' => $aRow['id']]);

    // Read
    $row[] = total_rows(db_prefix() . 'whatsapp_campaign_data', ['message_status' => 'read', 'campaign_id' => $aRow['id']]);

    // Actions
    $options = '<div class="tw-flex tw-items-center tw-space-x-3">';

    if (staff_can('edit', 'whatsapp_campaign')) {
        $options .= '<a href="' . admin_url('whatsapp/campaigns/campaign/' . $aRow['id']) . '" class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700" data-toggle="tooltip" data-title="' . _l('edit') . '"><i class="fa-regular fa-pen-to-square fa-lg"></i></a>';
    }

    if (staff_can('delete', 'whatsapp_campaign')) {
        $options .= '<a href="' . admin_url('whatsapp/campaigns/delete/' . $aRow['id']) . '" data-id="' . $aRow['id'] . '" class="tw-mt-px tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete" data-toggle="tooltip" data-title="' . _l('delete') . '"><i class="fa-regular fa-trash-can fa-lg"></i></a>';
    }

    if (!staff_can('edit', 'whatsapp_campaign') && !staff_can('delete', 'whatsapp_campaign')) {
        $options .= '-';
    }

    $options .= '</div>';
    $row[] = $options;

    $output['aaData'][] = $row;
}
