<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Ebilling_gateway extends App_gateway
{
    public bool $processingFees = false;

    public function __construct()
    {
        parent::__construct();

        /**
         * REQUIRED
         * Gateway unique id
         */
        $this->setId('ebilling');

        /**
         * REQUIRED
         * Gateway name
         */
        $this->setName('Ebilling (Mobile Money & Cartes)');

        /**
         * Add gateway settings
         */
        $this->setSettings([
            [
                'name'  => 'username',
                'encrypted' => false,
                'label' => 'Username Ebilling',
            ],
            [
                'name'  => 'shared_key',
                'encrypted' => true,
                'label' => 'Shared Key',
            ],
            [
                'name'  => 'api_base_url',
                'label' => 'API Base URL',
                'default_value' => 'https://lab.billing-easy.net',
            ],
            [
                'name'  => 'portal_url',
                'label' => 'Portail de paiement URL',
                'default_value' => 'https://test.billing-easy.net/',
            ],
            [
                'name'  => 'webhook_secret',
                'encrypted' => true,
                'label' => 'Webhook Secret (optionnel)',
            ],
            [
                'name'  => 'test_mode_enabled',
                'type'  => 'yes_no',
                'default_value' => 1,
                'label' => 'Mode Test (LAB)',
            ],
            [
                'name'          => 'description_dashboard',
                'label'         => 'settings_paymentmethod_description',
                'type'          => 'textarea',
                'default_value' => 'Paiement facture {invoice_number}',
            ],
            [
                'name'             => 'currencies',
                'label'            => 'settings_paymentmethod_currencies',
                'default_value'    => 'XAF',
                'field_attributes' => ['disabled' => true],
            ],
            [
                'name'  => 'gpswox_api_key',
                'encrypted' => true,
                'label' => 'GPSWOX API Key',
            ],
            [
                'name'  => 'gpswox_base_url',
                'label' => 'GPSWOX Base URL',
                'default_value' => 'https://app.mbiratracker.com',
            ],
            [
                'name'  => 'gpswox_enable_auto_renew',
                'type'  => 'yes_no',
                'default_value' => 1,
                'label' => 'Activer renouvellement automatique GPSWOX',
            ],
        ]);
    }

    /**
     * REQUIRED FUNCTION
     * Process payment
     * @param  array $data
     * @return mixed
     */
    public function process_payment($data)
    {
        // Charger le modèle Ebilling
        $CI = &get_instance();
        $CI->load->model('ebilling_gpswox/ebilling_model');

        $invoice = $data['invoice'];
        $amount  = $data['amount'];

        // Utiliser les paramètres de la passerelle
        $username   = $this->getSetting('username');
        $sharedKey  = $this->decryptSetting('shared_key');
        $apiBaseUrl = $this->getSetting('api_base_url');
        $portalUrl  = $this->getSetting('portal_url');

        // Log de début de traitement
        log_message('error', '[EBG Gateway] Starting payment processing for invoice #' . $invoice->id . ' - Amount: ' . $amount . ' XAF');
        log_message('error', '[EBG Gateway] Configuration - Username: ' . $username . ' | API URL: ' . $apiBaseUrl . ' | Portal: ' . $portalUrl);

        // Configurer le modèle Ebilling avec les credentials de la passerelle
        $CI->ebilling_model->setCredentials($username, $sharedKey, $apiBaseUrl);

        // Créer la facture Ebilling
        // Ajouter un timestamp pour différencier les tentatives de paiement
        $externalRef = format_invoice_number($invoice->id) . '-' . time();

        $billData = [
            'external_reference' => $externalRef,
            'amount'            => number_format($amount, 0, '', ''),
            'currency'          => 'XAF',
            'description'       => str_replace('{invoice_number}', format_invoice_number($invoice->id), $this->getSetting('description_dashboard')),
        ];

        log_message('error', '[EBG Gateway] Bill data prepared: ' . json_encode($billData));

        try {
            // create_bill retourne le bill_id (string) ou false
            $bill_id = $CI->ebilling_model->create_bill($billData);

            log_message('error', '[EBG Gateway] create_bill returned: ' . var_export($bill_id, true));

            if ($bill_id) {
                // Enregistrer la transaction
                if (function_exists('ebg_txn_log')) {
                    ebg_txn_log([
                        'invoice_id'         => $invoice->id,
                        'external_reference' => $externalRef,
                        'bill_id'            => $bill_id,
                        'amount'             => $amount,
                        'status'             => 'pending',
                    ]);
                }

                // URL de callback (retour après paiement)
                $eb_callbackurl = site_url('invoice/' . $invoice->id . '/' . $invoice->hash);

                // URL du portail Ebilling (POST_URL)
                $post_url = rtrim($portalUrl, '/');

                log_message('error', '[EBG Gateway] Redirecting to: ' . $post_url . ' with invoice_number: ' . $bill_id);

                // Redirection vers le portail Ebilling via formulaire auto-submit (méthodologie standard)
                echo "<form action='" . $post_url . "' method='post' name='frm'>";
                echo "<input type='hidden' name='invoice_number' value='" . htmlspecialchars($bill_id, ENT_QUOTES, 'UTF-8') . "'>";
                echo "<input type='hidden' name='eb_callbackurl' value='" . htmlspecialchars($eb_callbackurl, ENT_QUOTES, 'UTF-8') . "'>";
                echo "</form>";
                echo "<script language='JavaScript'>";
                echo "document.frm.submit();";
                echo "</script>";
                exit();
            } else {
                log_message('error', '[EBG Gateway] Failed to create Ebilling bill');
                set_alert('danger', 'Erreur lors de la création de la facture Ebilling. Vérifiez vos identifiants.');
                redirect(site_url('invoice/' . $invoice->id . '/' . $invoice->hash));
            }
        } catch (Exception $e) {
            log_message('error', '[EBG Gateway] Payment processing error: ' . $e->getMessage());
            set_alert('danger', 'Une erreur est survenue lors du traitement du paiement');
            redirect(site_url('invoice/' . $invoice->id . '/' . $invoice->hash));
        }
    }
}
