<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Brevo (Sendinblue)
Description: Intégration Brevo pour envoi d'emails transactionnels et SMS
Version: 1.0.0
Requires at least: 2.9.*
Author: Codeur X
Author URI: https://mebodorichard.com
*/

define('BREVO_MODULE_NAME', 'brevo');

// Charger le helper Brevo
require_once(__DIR__ . '/helpers/brevo_helper.php');

hooks()->add_action('admin_init', 'brevo_module_init_menu_items');
hooks()->add_action('admin_init', 'brevo_register_settings');

/**
 * Ajout du menu dans l'admin
 */
function brevo_module_init_menu_items()
{
    $CI = &get_instance();
    if (has_permission('settings', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('brevo-settings', [
            'name'     => _l('brevo_module_name'),
            'href'     => admin_url('settings?group=' . BREVO_MODULE_NAME),
            'position' => 52,
            'icon'     => 'fa fa-envelope',
        ]);
    }
}

/**
 * Enregistrement des settings
 */
function brevo_register_settings()
{
    $CI = &get_instance();

    // Créer l'onglet settings pour le module
    if (method_exists($CI->app, 'add_settings_section')) {
        $CI->app->add_settings_section('modules', [
            'title' => 'Modules',
            'position' => 100,
            'children' => [
                [
                    'slug' => BREVO_MODULE_NAME,
                    'name' => 'Brevo',
                    'view' => BREVO_MODULE_NAME . '/settings',
                ]
            ]
        ]);
    } else {
        // Fallback pour versions plus anciennes
        $CI->app_tabs->add_settings_tab(BREVO_MODULE_NAME, [
            'name'     => 'Brevo',
            'view'     => BREVO_MODULE_NAME . '/settings',
            'position' => 61,
        ]);
    }
}

/**
 * Activation du module
 */
register_activation_hook(BREVO_MODULE_NAME, 'brevo_activate_module');
function brevo_activate_module()
{
    $CI = &get_instance();

    // Créer les options par défaut
    $options = [
        'brevo_api_key' => '',
        'brevo_smtp_enabled' => '0',
        'brevo_smtp_host' => 'smtp-relay.brevo.com',
        'brevo_smtp_port' => '587',
        'brevo_smtp_username' => '',
        'brevo_smtp_password' => '',
        'brevo_sms_enabled' => '0',
        'brevo_sms_sender' => '',
        'brevo_debug' => '0',
    ];

    foreach ($options as $key => $value) {
        if (!get_option($key)) {
            add_option($key, $value);
        }
    }

    log_message('info', 'Brevo module activated');
}

/**
 * Désactivation du module
 */
register_deactivation_hook(BREVO_MODULE_NAME, 'brevo_deactivate_module');
function brevo_deactivate_module()
{
    log_message('info', 'Brevo module deactivated');
}
