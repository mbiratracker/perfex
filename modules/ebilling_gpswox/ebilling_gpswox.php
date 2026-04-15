<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Ebilling + GPSWOX
Description: Integration de paiement Ebilling (Mobile Money & Cartes) avec activation automatique GPSWOX
Version: 1.1.0
Requires at least: 2.9.*
Author: Codeur X
Author URI: https://mebodorichard.com
*/

// Nom unique du module (dossier)
define('EBG_MODULE_NAME', 'ebilling_gpswox');

// Charger le helper ebilling
require_once(__DIR__ . '/helpers/ebilling_helper.php');

// Enregistrer la passerelle de paiement Ebilling
register_payment_gateway('Ebilling_gateway', EBG_MODULE_NAME);

hooks()->add_action('admin_init', 'ebg_module_init_menu_items');
hooks()->add_action('admin_init', 'ebg_register_settings');
hooks()->add_action('app_admin_head', 'ebg_add_styles');
hooks()->add_action('customers_navigation_end', 'ebg_client_invoice_button');
hooks()->add_action('after_invoice_added', 'ebg_after_invoice_added');
hooks()->add_action('after_invoice_status_changed', 'ebg_after_invoice_status_changed');

// Page de réglages dans le menu Modules -> Ebilling+GPSWOX
function ebg_module_init_menu_items()
{
    $CI = &get_instance();
    if (has_permission('settings', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('ebg-settings', [
            'name'     => _l('Ebilling + GPSWOX'),
            'href'     => admin_url('settings?group=' . EBG_MODULE_NAME),
            'position' => 51,
            'icon'     => 'fa fa-plug',
        ]);
    }
}

// Enregistrement du groupe de settings
function ebg_register_settings()
{
    $CI = &get_instance();

    // Créer l'onglet settings pour le module (compatible Perfex 3.2+)
    if (method_exists($CI->app, 'add_settings_section')) {
        $CI->app->add_settings_section('modules', [
            'title' => 'Modules',
            'position' => 100,
            'children' => [
                [
                    'slug' => EBG_MODULE_NAME,
                    'name' => 'Ebilling + GPSWOX',
                    'view' => EBG_MODULE_NAME . '/settings',
                ]
            ]
        ]);
    } else {
        // Fallback pour versions plus anciennes
        $CI->app_tabs->add_settings_tab(EBG_MODULE_NAME, [
            'name'     => 'Ebilling + GPSWOX',
            'view'     => EBG_MODULE_NAME . '/settings',
            'position' => 60,
        ]);
    }
}

// Styles mineurs (facultatif)
function ebg_add_styles()
{
    echo '<style>.ebg-badge{display:inline-block;padding:2px 6px;border-radius:4px;background:#f1f5f9;margin-left:8px}</style>';
}

// Bouton côté portail client (dans la vue facture) – affiche un bouton "Payer avec Ebilling"
function ebg_client_invoice_button($invoice)
{
    if (!is_client_logged_in() || empty($invoice) || !isset($invoice->id)) {
        return;
    }
    if ($invoice->status != Invoices_model::STATUS_UNPAID) {
        return;
    }

    $url = site_url('ebilling/create/' . $invoice->id);
    echo '<a class="btn btn-success" style="margin-left:8px" href="' . $url . '">'
        . _l('Payer avec Ebilling') . '</a>';
}

// Hook: après création facture -> rien par défaut (possibilité d’auto-créer e_bill)
function ebg_after_invoice_added($invoice_id)
{
    // Optionnel: auto-créer bill à la création
}

// Hook: changement de statut – si passe à Unpaid, on peut (ré)générer un bill
function ebg_after_invoice_status_changed($data)
{
    // $data: ['invoice_id' => X, 'status' => Y]
}

/**
 * Activation du module: exécute la migration install.php
 *
 * Note: Les exclusions CSRF sont définies dans config/csrf_exclude_uris.php
 * et chargées automatiquement par Perfex via application/hooks/InitModules.php
 */
register_activation_hook(EBG_MODULE_NAME, 'ebg_activate_module');
function ebg_activate_module()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}
