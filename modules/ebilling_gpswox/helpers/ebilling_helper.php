<?php
defined('BASEPATH') or exit('No direct script access allowed');

function ebg_log($message, $context = [])
{
    if (!is_string($message)) {
        $message = json_encode($message);
    }
    log_message('info', '[EBG] ' . $message . ' ' . (!empty($context) ? json_encode($context) : ''));
}

function ebg_txn_log(array $data)
{
    $CI = &get_instance();
    $row = [
        'invoice_id'        => $data['invoice_id'] ?? null,
        'external_reference' => $data['external_reference'] ?? null,
        'bill_id'           => $data['bill_id'] ?? '',
        'amount'            => $data['amount'] ?? 0,
        'operator'          => $data['operator'] ?? null,
        'status'            => $data['status'] ?? 'pending',
        'created_at'        => date('Y-m-d H:i:s')
    ];
    $CI->db->insert(db_prefix() . 'ebilling_transactions', $row);
    return $CI->db->insert_id();
}

function ebg_txn_update_by_reference($reference, array $updates)
{
    $CI = &get_instance();
    $CI->db->where('external_reference', $reference);
    $CI->db->update(db_prefix() . 'ebilling_transactions', $updates);
}

// Mapping simple Perfex client -> GPSWOX user_id (à remplacer par table dédiée si besoin)
function ebg_map_client_to_gpswox_user($client_id)
{
    // Exemple minimal: stocker l'id GPSWOX dans custom fields client, ex: custom field slug 'gpswox_user_id'
    $CI = &get_instance();
    $CI->load->model('custom_fields_model');
    $val = get_custom_field_value($client_id, 'gpswox_user_id', 'customers');
    return $val ? (int)$val : null;
}
