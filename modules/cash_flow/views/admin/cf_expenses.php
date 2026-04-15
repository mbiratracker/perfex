<?php defined('BASEPATH') or exit('No direct script access allowed');

// 1. Définition des colonnes (doit correspondre à l'ordre des <th> de ton HTML)
$aColumns = [
    db_prefix() . 'cf_expenses.id as id',
    'expense_name',
    'operation',
    'date',
    'company', 
    'amount',
    'balance',
    'buisness_id'
];

$sIndexColumn = 'id';
$sTable = db_prefix() . 'cf_expenses';

// 2. Jointures (LEFT JOIN pour ne rien bloquer si le client est vide)
$join = [
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'cf_expenses.clientid',
];

// 3. Clauses de filtrage
$where = [];

// Filtrage par Business ID (passé par l'URL /table/1)
if (isset($data['buisness_id']) && $data['buisness_id'] != '') {
    $where[] = 'AND ' . db_prefix() . 'cf_expenses.buisness_id = ' . $this->ci->db->escape_str($data['buisness_id']);
}

// Filtrage par client (si applicable)
if (isset($clientid) && $clientid != '') {
    $where[] = 'AND ' . db_prefix() . 'cf_expenses.clientid = ' . $this->ci->db->escape_str($clientid);
}

// 4. Initialisation du moteur DataTables de Perfex
$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    'clientid',
]);

$output  = $result['output'];
$rResult = $result['rResult'];

// 5. Formatage des lignes pour l'affichage
foreach ($rResult as $aRow) {
    $row = [];
    
    // Colonne # (ID)
    $row[] = $aRow['id'];

    // Colonne Libellé + Options
    $nameOutput = '<a href="' . admin_url('cash_flow/list_cf_expenses/' . $aRow['buisness_id'] . '/' . $aRow['id']) . '" class="tw-font-semibold">' . $aRow['expense_name'] . '</a>';
    
    $options = '<div class="row-options">';
    $options .= '<a href="' . admin_url('cash_flow/list_cf_expenses/' . $aRow['buisness_id'] . '/' . $aRow['id']) . '">' . _l('view') . '</a>';
    
    if (staff_can('edit', 'cash_flow')) {
        $options .= ' | <a href="' . admin_url('cash_flow/expense/' . $aRow['buisness_id'] . '/' . $aRow['id']) . '">' . _l('edit') . '</a>';
    }
    if (staff_can('delete', 'cash_flow')) {
        $options .= ' | <a href="' . admin_url('cash_flow/delete/' . $aRow['buisness_id'] . '/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
    }
    $options .= '</div>';
    
    $row[] = $nameOutput . $options;

    // Colonne Opération (Badges)
    $op = strtolower($aRow['operation']);
    if ($op == 'cash-in') {
        $row[] = '<span class="label label-success">CASH-IN</span>';
    } else {
        $row[] = '<span class="label label-danger">CASH-OUT</span>';
    }

    // Colonne Date
    $row[] = _d($aRow['date']);

    // Colonne Client
    $row[] = ($aRow['clientid']) ? '<a href="' . admin_url('clients/client/' . $aRow['clientid']) . '">' . $aRow['company'] . '</a>' : 'N/A';

    // Colonne Référence (REF-00001)
    $row[] = 'REF-' . str_pad($aRow['id'], 5, '0', STR_PAD_LEFT);

    // Colonne Reçu (Cash-in)
    $row[] = ($op == 'cash-in') ? '<span class="text-success">+' . app_format_money($aRow['amount'], '') . '</span>' : '0';

    // Colonne Décaissement (Cash-out)
    $row[] = ($op == 'cash-out') ? '<span class="text-danger">-' . app_format_money($aRow['amount'], '') . '</span>' : '0';

    // Colonne Solde
    $row[] = '<strong>' . app_format_money($aRow['balance'], '') . '</strong>';

    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}

// IMPORTANT : On affiche le JSON final
echo json_encode($output);
die();
