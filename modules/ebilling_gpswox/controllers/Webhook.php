<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Webhook extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Le CSRF est désactivé pour cette route via config/csrf_exclude_uris.php
        // Chargé automatiquement par application/hooks/InitModules.php

        $this->load->model('invoices_model');
        $this->load->model('payments_model');
        $this->load->model('ebilling_gpswox/gpswox_model');
        $this->load->helper('ebilling_gpswox/ebilling');
    }

    // Réception du callback Ebilling (POST)
    public function listen()
    {
        $payment_updated = false; // Flag pour tracker si le paiement a été mis à jour
        $invoice = null;

        try {
            ebg_log('=== WEBHOOK START ===');

            // 1) Validation de sécurité
            if (!$this->validate_webhook_security()) {
                $this->output->set_status_header(403);
                echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
                ebg_log('Webhook rejected: security validation failed');
                ebg_log('=== WEBHOOK END (403) ===');
                return;
            }

            // 2) Récupération et validation du payload
            $payload = $this->input->post(NULL, true);

            // Fallback: essayer de lire le raw input si POST vide (certains webhooks envoient du JSON pur)
            if (empty($payload)) {
                $raw = file_get_contents('php://input');
                $payload = json_decode($raw, true) ?: [];
                ebg_log('Payload from raw input', ['raw' => substr($raw, 0, 500)]);
            }

            ebg_log('Webhook payload received', $payload);

            // Validation des champs obligatoires
            if (!$this->validate_payload($payload)) {
                $this->output->set_status_header(400);
                echo json_encode(['status' => 'error', 'message' => 'Invalid payload']);
                ebg_log('=== WEBHOOK END (400 - Invalid payload) ===');
                return;
            }

            // Normaliser les champs (Ebilling utilise différents noms)
            $reference = $payload['reference'] ?? $payload['external_reference'] ?? null;
            $amount    = $payload['amount'] ?? null;

            // Ebilling utilise 'state' avec les valeurs: paid, failed, pending
            $state     = strtolower($payload['state'] ?? $payload['status'] ?? '');
            // Normaliser en 'success' pour compatibilité
            $status    = ($state === 'paid') ? 'success' : $state;

            $operator  = $payload['paymentsystem'] ?? $payload['operator'] ?? $payload['payment_system_name'] ?? null;
            $transaction_id = $payload['transactionid'] ?? $payload['transaction_id'] ?? null;

            ebg_log('Webhook normalized data', [
                'reference' => $reference,
                'amount' => $amount,
                'status' => $status,
                'state' => $state,
                'operator' => $operator,
                'transaction_id' => $transaction_id
            ]);

            // 3) Retrouver la facture
            // La référence peut contenir un timestamp (ex: INV-000001-1761385114)
            // Il faut extraire le vrai numéro de facture
            $invoice_number = $reference;
            if (preg_match('/^(.+)-\d{10,}$/', $reference, $matches)) {
                $invoice_number = $matches[1];
                ebg_log('Extracted invoice number from reference', [
                    'original_reference' => $reference,
                    'invoice_number' => $invoice_number
                ]);
            }

            // Chercher la facture par formatted_number (ex: INV-000001)
            // Ce champ contient le numéro formaté complet et est indexé
            $invoice = $this->db->get_where(db_prefix() . 'invoices', [
                'formatted_number' => $invoice_number
            ])->row();

            if (!$invoice) {
                $this->output->set_status_header(404);
                echo json_encode(['status' => 'error', 'message' => 'Invoice not found']);
                ebg_log('Webhook error: invoice not found', [
                    'reference' => $reference,
                    'extracted_invoice_number' => $invoice_number
                ]);
                ebg_log('=== WEBHOOK END (404 - Invoice not found) ===');
                return;
            }

            ebg_log('Invoice found', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'invoice_status' => $invoice->status,
                'formatted_number' => $invoice->formatted_number
            ]);

            // Vérifier si la facture est déjà payée
            if ((int)$invoice->status === Invoices_model::STATUS_PAID) {
                ebg_log('Invoice already paid, returning success without reprocessing', [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->formatted_number,
                    'status' => $invoice->status
                ]);

                $this->output->set_status_header(200);
                echo json_encode(['status' => 'ok', 'message' => 'Invoice already paid']);
                ebg_log('=== WEBHOOK END (200 - Already paid) ===');
                return;
            }

            // 4) Mettre à jour la transaction locale
            ebg_log('Updating transaction in database');
            try {
                ebg_txn_update_by_reference($reference, [
                    'status' => $status === 'success' ? 'paid' : $status,
                    'operator' => $operator,
                    'callback_payload' => json_encode($payload),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                ebg_log('Transaction updated successfully', ['reference' => $reference]);
            } catch (Exception $e) {
                ebg_log('WARNING: Failed to update transaction', [
                    'error' => $e->getMessage(),
                    'reference' => $reference
                ]);
            }

            // 5) Traiter le paiement selon le statut
            if ($status === 'success') {
                ebg_log('Processing successful payment');

                // Appeler le traitement du paiement avec gestion d'erreur robuste
                $payment_result = $this->process_successful_payment_safe($invoice, $amount, $transaction_id, $operator, $payload);

                if ($payment_result['payment_created']) {
                    $payment_updated = true;
                    ebg_log('Payment successfully registered', ['payment_id' => $payment_result['payment_id']]);
                }

                // Même si le renouvellement GPSWOX échoue, on retourne 200 car le paiement est enregistré
                if ($payment_updated) {
                    $this->output->set_status_header(200);
                    echo json_encode([
                        'status' => 'ok',
                        'message' => 'Payment processed',
                        'warnings' => $payment_result['warnings'] ?? []
                    ]);
                    ebg_log('=== WEBHOOK END (200 - Payment processed with warnings) ===', $payment_result);
                    return;
                }

            } elseif ($status === 'failed') {
                ebg_log('Payment failed', ['invoice' => $invoice->number, 'payload' => $payload]);
                // Optionnel: notifier le client de l'échec
            }

            $this->output->set_status_header(200);
            echo json_encode(['status' => 'ok', 'message' => 'Webhook processed']);
            ebg_log('=== WEBHOOK END (200 - Success) ===');

        } catch (Exception $e) {
            ebg_log('Webhook exception caught', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // Si le paiement a été mis à jour AVANT l'erreur, on retourne 200 pour éviter les retries
            if ($payment_updated) {
                ebg_log('Payment was updated before exception, returning 200 to prevent retries');
                $this->output->set_status_header(200);
                echo json_encode([
                    'status' => 'warning',
                    'message' => 'Payment updated but post-processing failed',
                    'error' => $e->getMessage()
                ]);
                ebg_log('=== WEBHOOK END (200 - Payment updated, but errors after) ===');
            } else {
                // Sinon, on retourne 500 pour que Ebilling réessaie
                ebg_log('Payment was NOT updated, returning 500 for retry');
                $this->output->set_status_header(500);
                echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
                ebg_log('=== WEBHOOK END (500 - Error before payment update) ===');
            }

            // Notification admin si activé
            if (get_option('paymentmethod_ebilling_notify_admin') == '1') {
                $this->notify_admin_error('Webhook Error', $e->getMessage() . "\n\nInvoice: " . ($invoice ? $invoice->formatted_number : 'N/A'));
            }
        }
    }

    /**
     * Validation de sécurité du webhook
     */
    private function validate_webhook_security()
    {
        // Log pour debugging
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        ebg_log('Webhook security check', [
            'ip' => $this->input->ip_address(),
            'headers' => $headers,
            'method' => $this->input->method()
        ]);

        // 1) Vérification du secret partagé (si configuré)
        $webhook_secret = get_option('paymentmethod_ebilling_webhook_secret');

        if (!empty($webhook_secret)) {
            // Ebilling peut envoyer le secret de différentes manières
            $received_secret = $this->input->get_request_header('X-Ebilling-Secret', true)
                            ?: $this->input->get_request_header('X-Webhook-Secret', true)
                            ?: $this->input->post('secret');

            if ($received_secret !== $webhook_secret) {
                ebg_log('Webhook secret mismatch', [
                    'expected' => substr($webhook_secret, 0, 5) . '***',
                    'received' => $received_secret ? substr($received_secret, 0, 5) . '***' : 'null'
                ]);
                return false;
            }

            ebg_log('Webhook secret validated');
        } else {
            // Pas de secret configuré - accepter la requête (à sécuriser en production)
            ebg_log('Webhook security: no secret configured, accepting request');
        }

        // 2) Vérification IP (optionnelle - à activer si Ebilling fournit des IPs fixes)
        // $allowed_ips = ['IP_EBILLING_1', 'IP_EBILLING_2'];
        // $client_ip = $this->input->ip_address();
        // if (!empty($allowed_ips) && !in_array($client_ip, $allowed_ips)) {
        //     ebg_log('Webhook IP rejected', ['ip' => $client_ip]);
        //     return false;
        // }

        return true;
    }

    /**
     * Validation du payload
     */
    private function validate_payload($payload)
    {
        if (empty($payload)) {
            ebg_log('Webhook validation failed: empty payload');
            return false;
        }

        // Le champ reference peut être 'reference' ou 'external_reference'
        $has_reference = isset($payload['reference']) || isset($payload['external_reference']);

        if (!$has_reference) {
            ebg_log('Webhook validation failed: missing reference');
            return false;
        }

        // Le champ status peut être 'status' ou 'state' (Ebilling utilise 'state')
        $has_status = isset($payload['status']) || isset($payload['state']);

        if (!$has_status) {
            ebg_log('Webhook validation failed: missing status/state');
            return false;
        }

        ebg_log('Webhook payload validated successfully');
        return true;
    }

    /**
     * Traiter un paiement réussi avec gestion d'erreur robuste
     * Retourne un tableau avec le statut et les warnings éventuels
     */
    private function process_successful_payment_safe($invoice, $amount, $transaction_id, $operator, $payload)
    {
        $result = [
            'payment_created' => false,
            'payment_id' => null,
            'gpswox_renewed' => false,
            'warnings' => []
        ];

        // 1) Enregistrer le paiement dans Perfex (CRITIQUE - doit réussir)
        try {
            if ((int)$invoice->status !== Invoices_model::STATUS_PAID) {
                ebg_log('Creating payment in Perfex', [
                    'invoice_id' => $invoice->id,
                    'amount' => $amount ?: $invoice->total
                ]);

                $payment = [
                    'invoiceid'    => $invoice->id,
                    'amount'       => $amount ?: $invoice->total,
                    'paymentmode'  => 'ebilling',
                    'transactionid' => $transaction_id,
                    'note'         => 'Paiement Ebilling via ' . ($operator ?: 'Mobile Money'),
                ];

                $payment_id = $this->payments_model->add($payment);

                if ($payment_id) {
                    $result['payment_created'] = true;
                    $result['payment_id'] = $payment_id;
                    ebg_log('✓ Payment registered in Perfex successfully', [
                        'invoice_id' => $invoice->id,
                        'payment_id' => $payment_id,
                        'amount' => $amount,
                        'transaction_id' => $transaction_id,
                        'operator' => $operator
                    ]);
                } else {
                    ebg_log('✗ CRITICAL: Failed to register payment in Perfex', [
                        'invoice_id' => $invoice->id,
                        'amount' => $amount
                    ]);
                    $result['warnings'][] = 'Payment registration failed';
                }
            } else {
                ebg_log('Invoice already paid, skipping payment registration', [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->number
                ]);
                $result['payment_created'] = true; // Déjà payé = considéré comme succès
            }
        } catch (Exception $e) {
            ebg_log('✗ EXCEPTION during payment creation', [
                'invoice' => $invoice->number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $result['warnings'][] = 'Payment creation exception: ' . $e->getMessage();
            // Ne pas propager l'exception ici, continuer le traitement
        }

        // 2) Activer/renouveler dans GPSWOX (NON-CRITIQUE - peut échouer)
        try {
            $client_id = (int)$invoice->clientid;
            ebg_log('Attempting GPSWOX renewal', ['client_id' => $client_id]);

            $gpswox_user_id = ebg_map_client_to_gpswox_user($client_id);

            if ($gpswox_user_id) {
                ebg_log('GPSWOX mapping found, calling renew API', ['gpswox_user_id' => $gpswox_user_id]);

                $renew_result = $this->gpswox_model->renew_account($gpswox_user_id, 30);

                if ($renew_result) {
                    $result['gpswox_renewed'] = true;
                    ebg_log('✓ GPSWOX account renewed successfully', [
                        'client_id' => $client_id,
                        'gpswox_user_id' => $gpswox_user_id,
                        'result' => $renew_result
                    ]);
                } else {
                    ebg_log('✗ WARNING: GPSWOX renewal returned false', [
                        'client_id' => $client_id,
                        'gpswox_user_id' => $gpswox_user_id
                    ]);
                    $result['warnings'][] = 'GPSWOX renewal failed';
                }
            } else {
                ebg_log('✗ WARNING: GPSWOX mapping not found', ['client_id' => $client_id]);
                $result['warnings'][] = 'No GPSWOX mapping for client #' . $client_id;

                // Notification admin si mapping manquant
                if (get_option('paymentmethod_ebilling_notify_admin') == '1') {
                    try {
                        $this->notify_admin_warning(
                            'Mapping GPSWOX manquant',
                            "Le client #{$client_id} n'a pas de mapping GPSWOX configuré.\nFacture: {$invoice->formatted_number}\nMontant: {$amount}"
                        );
                    } catch (Exception $email_ex) {
                        ebg_log('Failed to send admin notification', ['error' => $email_ex->getMessage()]);
                    }
                }
            }
        } catch (Exception $e) {
            ebg_log('✗ EXCEPTION during GPSWOX renewal (non-critical)', [
                'invoice' => $invoice->number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $result['warnings'][] = 'GPSWOX renewal exception: ' . $e->getMessage();
            // Ne pas propager - le paiement a été enregistré, c'est le principal
        }

        ebg_log('Payment processing completed', $result);
        return $result;
    }

    /**
     * Traiter un paiement réussi (ancienne méthode - gardée pour compatibilité)
     * @deprecated Utiliser process_successful_payment_safe à la place
     */
    private function process_successful_payment($invoice, $amount, $transaction_id, $operator, $payload)
    {
        try {
            // 1) Enregistrer le paiement dans Perfex (si pas déjà payé)
            if ((int)$invoice->status !== Invoices_model::STATUS_PAID) {
                $payment = [
                    'invoiceid'    => $invoice->id,
                    'amount'       => $amount ?: $invoice->total,
                    'paymentmode'  => 'ebilling',
                    'transactionid' => $transaction_id,
                    'note'         => 'Paiement Ebilling via ' . ($operator ?: 'Mobile Money'),
                ];

                $payment_id = $this->payments_model->add($payment);

                if ($payment_id) {
                    ebg_log('Payment registered in Perfex', [
                        'invoice_id' => $invoice->id,
                        'payment_id' => $payment_id,
                        'amount' => $amount,
                        'transaction_id' => $transaction_id,
                        'operator' => $operator
                    ]);
                } else {
                    ebg_log('Failed to register payment in Perfex', [
                        'invoice_id' => $invoice->id,
                        'amount' => $amount
                    ]);
                }
            } else {
                ebg_log('Invoice already paid, skipping payment registration', [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->number
                ]);
            }

            // 2) Activer/renouveler dans GPSWOX
            $client_id = (int)$invoice->clientid;
            $gpswox_user_id = ebg_map_client_to_gpswox_user($client_id);

            if ($gpswox_user_id) {
                $result = $this->gpswox_model->renew_account($gpswox_user_id, 30);
                ebg_log('GPSWOX account renewed', [
                    'client_id' => $client_id,
                    'gpswox_user_id' => $gpswox_user_id,
                    'result' => $result
                ]);
            } else {
                ebg_log('GPSWOX mapping not found', ['client_id' => $client_id]);

                // Notification admin si mapping manquant
                if (get_option('paymentmethod_ebilling_notify_admin') == '1') {
                    $this->notify_admin_warning(
                        'Mapping GPSWOX manquant',
                        "Le client #{$client_id} n'a pas de mapping GPSWOX configuré."
                    );
                }
            }

        } catch (Exception $e) {
            ebg_log('Error processing successful payment', [
                'invoice' => $invoice->number,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Notifier l'administrateur d'une erreur
     */
    private function notify_admin_error($subject, $message)
    {
        $admin_email = get_option('admin_email');
        if ($admin_email) {
            $this->load->library('email');
            $this->email->from('noreply@' . $_SERVER['HTTP_HOST'], 'Ebilling Module');
            $this->email->to($admin_email);
            $this->email->subject('[ERREUR] ' . $subject);
            $this->email->message($message);
            $this->email->send();
        }
    }

    /**
     * Notifier l'administrateur d'un avertissement
     */
    private function notify_admin_warning($subject, $message)
    {
        $admin_email = get_option('admin_email');
        if ($admin_email) {
            $this->load->library('email');
            $this->email->from('noreply@' . $_SERVER['HTTP_HOST'], 'Ebilling Module');
            $this->email->to($admin_email);
            $this->email->subject('[ATTENTION] ' . $subject);
            $this->email->message($message);
            $this->email->send();
        }
    }
}
