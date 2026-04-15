<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Ebilling_model extends App_Model
{
    // Opérateurs supportés
    const OPERATOR_AIRTEL = 'airtelmoney';
    const OPERATOR_MOOV = 'moovmoney4';
    const OPERATOR_ORABANK = 'ORABANK_NG';
    const OPERATOR_VISA_MC = 'card';

    // Credentials temporaires (utilisés par la passerelle)
    private $temp_username = null;
    private $temp_shared_key = null;
    private $temp_base_url = null;

    /**
     * Définir les credentials temporairement (pour la passerelle)
     * @param string $username
     * @param string $shared_key
     * @param string $base_url
     */
    public function setCredentials($username, $shared_key, $base_url)
    {
        $this->temp_username = $username;
        $this->temp_shared_key = $shared_key;
        $this->temp_base_url = $base_url;
    }

    private function base()
    {
        // Utiliser l'URL temporaire si définie (passerelle)
        if ($this->temp_base_url) {
            return rtrim($this->temp_base_url, '/') . '/api/v1/merchant';
        }

        $base = rtrim(get_option('eb_base_url'), '/');
        if (!$base) {
            // Déterminer l'environnement
            $env = get_option('eb_environment') ?: 'lab';
            $base = ($env === 'prod') ? 'https://stg.billing-easy.com' : 'https://lab.billing-easy.net';
        }
        return $base . '/api/v1/merchant';
    }

    private function auth()
    {
        // Utiliser les credentials temporaires si définis (passerelle)
        if ($this->temp_username && $this->temp_shared_key) {
            return [$this->temp_username, $this->temp_shared_key];
        }

        return [get_option('eb_username'), get_option('eb_shared_key')];
    }

    /**
     * Créer une facture Ebilling
     * @param array $payload Doit contenir: external_reference, amount, currency, description
     *                       Optionnel: payer_msisdn, short_description
     * @return string|false bill_id ou false en cas d'erreur
     */
    public function create_bill(array $payload)
    {
        list($user, $key) = $this->auth();

        // Validation des credentials
        if (empty($user) || empty($key)) {
            ebg_log('Ebilling create_bill error: missing credentials');
            log_message('error', '[EBG] create_bill FAILED - Missing credentials');
            return false;
        }

        $url = $this->base() . '/e_bills';

        // Ajouter les champs obligatoires manquants
        if (!isset($payload['short_description']) || empty($payload['short_description'])) {
            // Utiliser description ou external_reference
            $payload['short_description'] = isset($payload['description'])
                ? substr($payload['description'], 0, 100)
                : $payload['external_reference'];
        }

        if (!isset($payload['payer_msisdn']) || empty($payload['payer_msisdn'])) {
            // Numéro par défaut - le client pourra le modifier sur le portail Ebilling
            $payload['payer_msisdn'] = '+237690000000';
        }

        // Log de la requête
        ebg_log('Ebilling create_bill - Starting request', [
            'url' => $url,
            'username' => $user,
            'payload' => $payload
        ]);
        log_message('error', '[EBG] API Request to: ' . $url . ' | Payload: ' . json_encode($payload));

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $key);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: Perfex-Ebilling-Module/1.0'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        // Log de la réponse complète
        log_message('error', '[EBG] API Response - Status: ' . $status . ' | Body: ' . $raw);

        if ($err) {
            ebg_log('Ebilling create_bill curl error', ['error' => $err, 'url' => $url]);
            log_message('error', '[EBG] CURL Error: ' . $err);
            return false;
        }

        if ($status >= 200 && $status < 300) {
            $res = json_decode($raw, true);
            log_message('error', '[EBG] Response decoded: ' . json_encode($res));

            if (isset($res['e_bill']['bill_id'])) {
                ebg_log('Ebilling bill created successfully', ['bill_id' => $res['e_bill']['bill_id']]);
                log_message('error', '[EBG] Bill created successfully - bill_id: ' . $res['e_bill']['bill_id']);
                return $res['e_bill']['bill_id'];
            } else {
                log_message('error', '[EBG] Response missing bill_id - Full response: ' . json_encode($res));
            }
        }

        ebg_log('Ebilling create_bill error', [
            'status' => $status,
            'response' => $raw,
            'payload' => $payload
        ]);
        log_message('error', '[EBG] create_bill FAILED - Status: ' . $status . ' | Response: ' . $raw);
        return false;
    }

    /**
     * Envoyer un USSD Push
     * @param string $bill_id
     * @param string $msisdn
     * @param string $system (airtelmoney|moovmoney4)
     * @return array Response avec status et message
     */
    public function ussd_push($bill_id, $msisdn, $system)
    {
        list($user, $key) = $this->auth();

        // Validation
        if (empty($user) || empty($key)) {
            return ['status' => false, 'message' => 'Missing credentials'];
        }

        if (!$this->is_valid_operator($system)) {
            return ['status' => false, 'message' => 'Opérateur non supporté: ' . $system];
        }

        $url = $this->base() . '/e_bills/' . rawurlencode($bill_id) . '/ussd_push';
        $body = [
            'payer_msisdn' => $msisdn,
            'payment_system_name' => $system
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $key);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: Perfex-Ebilling-Module/1.0'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            ebg_log('Ebilling ussd_push curl error', ['error' => $err]);
            return ['status' => false, 'message' => 'Erreur de connexion'];
        }

        $response = json_decode($raw, true);

        if ($status >= 200 && $status < 300) {
            ebg_log('USSD push sent successfully', ['bill_id' => $bill_id, 'system' => $system]);
            return ['status' => true, 'message' => $response['message'] ?? 'Accepted'];
        }

        ebg_log('Ebilling ussd_push error', [
            'status' => $status,
            'response' => $raw,
            'bill_id' => $bill_id
        ]);

        return ['status' => false, 'message' => $response['message'] ?? 'Erreur lors de l\'envoi'];
    }

    /**
     * Récupérer le statut d'une facture
     * @param string $bill_id
     * @return array|false
     */
    public function get_bill_status($bill_id)
    {
        list($user, $key) = $this->auth();

        if (empty($user) || empty($key)) {
            return false;
        }

        $url = $this->base() . '/e_bills/' . rawurlencode($bill_id);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $key);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'User-Agent: Perfex-Ebilling-Module/1.0'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status >= 200 && $status < 300) {
            return json_decode($raw, true);
        }

        return false;
    }

    /**
     * Vérifier si un opérateur est valide
     * @param string $operator
     * @return bool
     */
    private function is_valid_operator($operator)
    {
        $valid_operators = [
            self::OPERATOR_AIRTEL,
            self::OPERATOR_MOOV,
            self::OPERATOR_ORABANK,
            self::OPERATOR_VISA_MC,
        ];

        return in_array($operator, $valid_operators);
    }

    /**
     * Obtenir la liste des opérateurs disponibles
     * @return array
     */
    public function get_available_operators()
    {
        $operators = [
            [
                'value' => self::OPERATOR_AIRTEL,
                'label' => 'Airtel Money',
                'enabled' => get_option('eb_enable_ussd') == '1'
            ],
            [
                'value' => self::OPERATOR_MOOV,
                'label' => 'Moov Money',
                'enabled' => get_option('eb_enable_ussd') == '1'
            ],
            [
                'value' => self::OPERATOR_ORABANK,
                'label' => 'VISA/MasterCard (Orabank)',
                'enabled' => get_option('eb_enable_card') == '1'
            ],
        ];

        return array_filter($operators, function($op) {
            return $op['enabled'];
        });
    }
}
