<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Log Brevo
 */
function brevo_log($message, $context = [])
{
    if (!is_string($message)) {
        $message = json_encode($message);
    }
    log_message('info', '[BREVO] ' . $message . ' ' . (!empty($context) ? json_encode($context) : ''));
}

/**
 * Envoyer un SMS via l'API Brevo
 *
 * @param string $to Numéro de téléphone au format international (ex: +237xxxxxxxxx)
 * @param string $message Contenu du SMS (max 160 caractères)
 * @param string|null $sender Nom de l'expéditeur (optionnel, 11 caractères max)
 * @return array ['success' => bool, 'message' => string, 'data' => array]
 */
function brevo_send_sms($to, $message, $sender = null)
{
    $api_key = get_option('brevo_api_key');

    if (empty($api_key)) {
        brevo_log('SMS failed: API key not configured');
        return [
            'success' => false,
            'message' => 'Brevo API key not configured',
            'data' => null
        ];
    }

    if (get_option('brevo_sms_enabled') != '1') {
        brevo_log('SMS failed: SMS feature not enabled');
        return [
            'success' => false,
            'message' => 'Brevo SMS not enabled',
            'data' => null
        ];
    }

    // Utiliser le sender configuré par défaut si non spécifié
    if (empty($sender)) {
        $sender = get_option('brevo_sms_sender') ?: 'Perfex';
    }

    // Limiter le sender à 11 caractères
    $sender = substr($sender, 0, 11);

    // Préparer le payload
    $payload = [
        'type' => 'transactional',
        'sender' => $sender,
        'recipient' => $to,
        'content' => $message,
    ];

    brevo_log('Sending SMS', ['to' => $to, 'sender' => $sender]);

    // Appel API Brevo
    $ch = curl_init('https://api.brevo.com/v3/transactionalSMS/sms');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'api-key: ' . $api_key,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        brevo_log('SMS cURL error', ['error' => $error]);
        return [
            'success' => false,
            'message' => 'cURL error: ' . $error,
            'data' => null
        ];
    }

    $response_data = json_decode($response, true);

    if ($http_code >= 200 && $http_code < 300) {
        brevo_log('SMS sent successfully', ['response' => $response_data]);
        return [
            'success' => true,
            'message' => 'SMS sent successfully',
            'data' => $response_data
        ];
    } else {
        brevo_log('SMS failed', [
            'http_code' => $http_code,
            'response' => $response_data
        ]);
        return [
            'success' => false,
            'message' => 'API error: ' . ($response_data['message'] ?? 'Unknown error'),
            'data' => $response_data
        ];
    }
}

/**
 * Envoyer un email transactionnel via l'API Brevo
 *
 * @param array $params Paramètres de l'email
 *   - to: array ['email' => string, 'name' => string] ou string
 *   - subject: string
 *   - htmlContent: string (HTML du message)
 *   - textContent: string (version texte, optionnel)
 *   - from: array ['email' => string, 'name' => string] (optionnel, utilise config par défaut)
 *   - replyTo: array ['email' => string, 'name' => string] (optionnel)
 *   - attachments: array (optionnel)
 * @return array ['success' => bool, 'message' => string, 'data' => array]
 */
function brevo_send_email($params)
{
    $api_key = get_option('brevo_api_key');

    if (empty($api_key)) {
        brevo_log('Email failed: API key not configured');
        return [
            'success' => false,
            'message' => 'Brevo API key not configured',
            'data' => null
        ];
    }

    // Normaliser le destinataire
    $to = $params['to'];
    if (is_string($to)) {
        $to = [['email' => $to]];
    } elseif (isset($to['email'])) {
        $to = [$to];
    }

    // Utiliser l'expéditeur par défaut si non spécifié
    $from = $params['from'] ?? [
        'email' => get_option('smtp_email'),
        'name' => get_option('companyname')
    ];

    // Préparer le payload
    $payload = [
        'sender' => $from,
        'to' => $to,
        'subject' => $params['subject'],
        'htmlContent' => $params['htmlContent'],
    ];

    if (!empty($params['textContent'])) {
        $payload['textContent'] = $params['textContent'];
    }

    if (!empty($params['replyTo'])) {
        $payload['replyTo'] = $params['replyTo'];
    }

    if (!empty($params['attachments'])) {
        $payload['attachment'] = $params['attachments'];
    }

    brevo_log('Sending email', ['to' => $to[0]['email'], 'subject' => $params['subject']]);

    // Appel API Brevo
    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'api-key: ' . $api_key,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        brevo_log('Email cURL error', ['error' => $error]);
        return [
            'success' => false,
            'message' => 'cURL error: ' . $error,
            'data' => null
        ];
    }

    $response_data = json_decode($response, true);

    if ($http_code >= 200 && $http_code < 300) {
        brevo_log('Email sent successfully', ['response' => $response_data]);
        return [
            'success' => true,
            'message' => 'Email sent successfully',
            'data' => $response_data
        ];
    } else {
        brevo_log('Email failed', [
            'http_code' => $http_code,
            'response' => $response_data
        ]);
        return [
            'success' => false,
            'message' => 'API error: ' . ($response_data['message'] ?? 'Unknown error'),
            'data' => $response_data
        ];
    }
}

/**
 * Tester la connexion Brevo
 *
 * @return array ['success' => bool, 'message' => string]
 */
function brevo_test_connection()
{
    $api_key = get_option('brevo_api_key');

    if (empty($api_key)) {
        return [
            'success' => false,
            'message' => 'API key not configured'
        ];
    }

    // Test avec l'endpoint account
    $ch = curl_init('https://api.brevo.com/v3/account');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'api-key: ' . $api_key,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200) {
        $data = json_decode($response, true);
        return [
            'success' => true,
            'message' => 'Connection successful',
            'data' => $data
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Connection failed (HTTP ' . $http_code . ')'
        ];
    }
}
