<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Brevo extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('brevo/brevo');
    }

    /**
     * Test de connexion à l'API Brevo
     */
    public function test_connection()
    {
        if (!has_permission('settings', '', 'view')) {
            ajax_access_denied();
        }

        $result = brevo_test_connection();

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * Envoyer un SMS de test
     */
    public function send_test_sms()
    {
        if (!has_permission('settings', '', 'view')) {
            ajax_access_denied();
        }

        $number = $this->input->post('number');

        if (empty($number)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Numéro de téléphone requis'
            ]);
            return;
        }

        $result = brevo_send_sms(
            $number,
            'Ceci est un message de test depuis Perfex CRM via Brevo.',
            get_option('brevo_sms_sender') ?: 'Perfex'
        );

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * Envoyer un email de test
     */
    public function send_test_email()
    {
        if (!has_permission('settings', '', 'view')) {
            ajax_access_denied();
        }

        $email = $this->input->post('email');

        if (empty($email)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Adresse email requise'
            ]);
            return;
        }

        $result = brevo_send_email([
            'to' => ['email' => $email],
            'subject' => 'Email de test depuis Perfex CRM',
            'htmlContent' => '
                <h2>Test Brevo</h2>
                <p>Ceci est un email de test envoyé depuis Perfex CRM via l\'API Brevo.</p>
                <p>Si vous recevez ce message, la configuration fonctionne correctement !</p>
                <hr>
                <p style="color: #666; font-size: 12px;">
                    Envoyé le ' . date('d/m/Y à H:i:s') . '
                </p>
            ',
            'textContent' => 'Ceci est un email de test envoyé depuis Perfex CRM via l\'API Brevo.'
        ]);

        header('Content-Type: application/json');
        echo json_encode($result);
    }
}
