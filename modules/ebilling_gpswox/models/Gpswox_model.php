<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Gpswox_model extends App_Model
{
    // Credentials temporaires (utilisés par la passerelle)
    private $temp_api_key = null;
    private $temp_base_url = null;

    /**
     * Définir les credentials temporairement (pour la passerelle)
     * @param string $api_key
     * @param string $base_url
     */
    public function setCredentials($api_key, $base_url)
    {
        $this->temp_api_key = $api_key;
        $this->temp_base_url = $base_url;
    }

    private function base()
    {
        // Utiliser l'URL temporaire si définie (passerelle)
        if ($this->temp_base_url) {
            return rtrim($this->temp_base_url, '/');
        }

        $base = rtrim(get_option('gpswox_base_url'), '/');
        return $base ?: 'https://app.mbiratracker.com';
    }

    private function headers()
    {
        // Utiliser l'API key temporaire si définie (passerelle)
        $key = $this->temp_api_key ?: get_option('gpswox_api_key');

        return [
            'Authorization: Bearer ' . $key,
            'Accept: application/json',
            'Content-Type: application/json',
        ];
    }

    /**
     * Renouvelle l'abonnement d'un user_id GPSWOX pour N jours
     * @param int $gpswox_user_id ID utilisateur GPSWOX
     * @param int $periodDays Nombre de jours de renouvellement
     * @return array|false Retourne la réponse API ou false en cas d'erreur
     */
    public function renew_account($gpswox_user_id, $periodDays = 30)
    {
        // Validation
        if (empty($gpswox_user_id)) {
            ebg_log('GPSWOX renew_account error: missing user_id');
            return false;
        }

        $url = $this->base() . '/api/account/renew';
        $payload = ['user_id' => (int)$gpswox_user_id, 'period' => (int)$periodDays];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers());
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            ebg_log('GPSWOX renew_account curl error', ['error' => $err, 'url' => $url]);
            return false;
        }

        if ($status >= 200 && $status < 300) {
            ebg_log('GPSWOX renew_account success', [
                'user_id' => $gpswox_user_id,
                'period' => $periodDays,
                'status' => $status
            ]);
            return json_decode($raw, true);
        }

        ebg_log('GPSWOX renew_account error', [
            'status' => $status,
            'response' => $raw,
            'user_id' => $gpswox_user_id
        ]);

        return false;
    }
}
