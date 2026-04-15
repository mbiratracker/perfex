<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// 1) Créer la table ebilling_transactions si elle n'existe pas
if (!$CI->db->table_exists(db_prefix() . 'ebilling_transactions')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "ebilling_transactions` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `invoice_id` INT NOT NULL,
        `external_reference` VARCHAR(191) NULL,
        `bill_id` VARCHAR(64) NOT NULL,
        `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
        `operator` VARCHAR(64) NULL,
        `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
        `callback_payload` LONGTEXT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        KEY `invoice_idx` (`invoice_id`),
        KEY `bill_idx` (`bill_id`),
        KEY `status_idx` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
}

// 2) Créer le custom field gpswox_user_id pour les clients
$exists = $CI->db->get_where(db_prefix() . 'customfields', [
    'fieldto' => 'customers',
    'slug' => 'gpswox_user_id'
])->row();

if (!$exists) {
    $CI->db->insert(db_prefix() . 'customfields', [
        'fieldto' => 'customers',
        'name' => 'GPSWOX User ID',
        'slug' => 'gpswox_user_id',
        'type' => 'input',
        'required' => 0,
        'active' => 1,
        'show_on_table' => 0,
        'show_on_client_portal' => 0,
        'field_order' => 0,
    ]);

    if (function_exists('log_activity')) {
        log_activity('Custom field GPSWOX User ID created by ebilling_gpswox module');
    }
}

// 3) Créer les options GPSWOX (la passerelle Ebilling gère ses propres options)
if (!function_exists('add_option')) {
    $CI->load->helper('settings');
}

add_option('gpswox_api_key', '');
add_option('gpswox_base_url', 'https://app.mbiratracker.com');
