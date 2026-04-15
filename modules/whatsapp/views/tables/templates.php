<?php
defined('BASEPATH') || exit('No direct script access allowed');

// Define table and columns
$aColumns = [
    'id',
    'whatsapp_business_account_id',
    'template_name',
    'language',
    'category',
    'status',
    'header_data_format',
    'header_params_count',
    'body_params_count',
    'footer_params_count',
    'parameter_format',
    'sub_category',
    'template_description',
    'previous_category',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'whatsapp_interaction_templates';

// Apply filter (only approved)
$where   = ['AND status = "APPROVED"'];

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, [], $where);
$output  = $result['output'];
$rResult = $result['rResult'];

// Loop through result rows
foreach ($rResult as $aRow) {
    $row = [];

    $row[] = $aRow['id'];

    $row[] = !empty($aRow['whatsapp_business_account_id'])
        ? '<span class="label label-info">' . htmlspecialchars($aRow['whatsapp_business_account_id']) . '</span>'
        : '<span class="text-muted">N/A</span>';

    $row[] = $aRow['template_name'] ?? '—';
    $row[] = strtoupper($aRow['language'] ?? 'N/A');
    $row[] = ucfirst(strtolower($aRow['category'] ?? '—'));

    // Status with badge
    $row[] = ($aRow['status'] === 'APPROVED')
        ? '<span class="label label-success">' . $aRow['status'] . '</span>'
        : '<span class="label label-default">' . $aRow['status'] . '</span>';

    $row[] = strtoupper($aRow['header_data_format'] ?? '—');

    $row[] = (int) $aRow['header_params_count'];
    $row[] = (int) $aRow['body_params_count'];
    $row[] = (int) $aRow['footer_params_count'];

    $row[] = strtoupper($aRow['parameter_format'] ?? '—');
    $row[] = $aRow['sub_category'] ?? '—';
    $row[] = $aRow['template_description'] ?? '<span class="text-muted">No description</span>';
    $row[] = $aRow['previous_category'] ?? '<span class="text-muted">N/A</span>';

    $output['aaData'][] = $row;
}
