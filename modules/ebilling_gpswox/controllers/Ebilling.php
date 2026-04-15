<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Ebilling extends ClientsController // ou AdminController selon usage
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('invoices_model');
        $this->load->model('ebilling_gpswox/ebilling_model');
        $this->load->helper('ebilling_gpswox/ebilling');
    }

    // Crée un e_bill chez Ebilling, puis redirige vers le portail de paiement
    public function create($invoice_id)
    {
        $invoice = $this->invoices_model->get($invoice_id);
        if (!$invoice) {
            set_alert('danger', 'Facture introuvable');
            redirect(site_url('clients/invoices'));
            return;
        }
        if ((int)$invoice->status !== Invoices_model::STATUS_UNPAID) {
            set_alert('warning', 'Facture non payable');
            redirect(site_url('invoice/' . $invoice->id . '/' . $invoice->hash));
            return;
        }

        $client = $this->clients_model->get($invoice->clientid);
        $payer_name  = $client->company ?: trim($client->firstname . ' ' . $client->lastname);
        $payer_email = $client->email;
        $payer_msisdn = $this->input->post('msisdn'); // optionnel si redirection portail

        $payload = [
            'payer_msisdn'       => $payer_msisdn,
            'payer_email'        => $payer_email,
            'payer_name'         => $payer_name,
            'amount'             => (string) number_format((float)$invoice->total, 2, '.', ''),
            'external_reference' => $invoice->number,
            'short_description'  => 'Paiement facture ' . $invoice->number,
            'expiry_period'      => '60', // minutes
        ];

        $bill_id = $this->ebilling_model->create_bill($payload);
        if (!$bill_id) {
            set_alert('danger', 'Erreur de création de la facture Ebilling');
            redirect(site_url('invoice/' . $invoice->id . '/' . $invoice->hash));
            return;
        }

        // Journaliser
        ebg_txn_log([
            'invoice_id' => $invoice->id,
            'external_reference' => $invoice->number,
            'bill_id' => $bill_id,
            'amount' => $invoice->total,
            'status' => 'pending'
        ]);

        // Redirection vers le portail Ebilling via formulaire auto-submit
        // URL de retour: passe par notre handler pour vérifier le statut de paiement
        $eb_callbackurl = site_url('ebilling/return/' . $invoice->id . '/' . $invoice->hash);
        $portal_url = get_option('paymentmethod_ebilling_portal_url') ?: 'https://test.billing-easy.net';
        $post_url = rtrim($portal_url, '/');

        // Méthodologie standard Ebilling
        echo "<form action='" . $post_url . "' method='post' name='frm'>";
        echo "<input type='hidden' name='invoice_number' value='" . htmlspecialchars($bill_id, ENT_QUOTES, 'UTF-8') . "'>";
        echo "<input type='hidden' name='eb_callbackurl' value='" . htmlspecialchars($eb_callbackurl, ENT_QUOTES, 'UTF-8') . "'>";
        echo "</form>";
        echo "<script language='JavaScript'>";
        echo "document.frm.submit();";
        echo "</script>";
        exit();
    }

    // Envoi d'un USSD PUSH (Airtel/Moov) – alternative à la redirection web
    public function ussd($bill_id)
    {
        if (!get_option('eb_enable_ussd')) {
            show_404();
        }
        $msisdn = $this->input->post('msisdn');
        $system = $this->input->post('system'); // airtelmoney | moovmoney4
        if (!$msisdn || !$system) {
            echo json_encode(['status' => false, 'message' => 'MSISDN et opérateur requis']);
            return;
        }
        $res = $this->ebilling_model->ussd_push($bill_id, $msisdn, $system);
        echo $res; // {"message":"Accepted"} si OK
    }

    // Optionnel: route utilitaire de redirection post-création (si besoin)
    public function redirect($bill_id)
    {
        $eb_callbackurl = site_url();
        $portal_url = get_option('paymentmethod_ebilling_portal_url') ?: 'https://test.billing-easy.net';
        $post_url = rtrim($portal_url, '/');

        // Méthodologie standard Ebilling
        echo "<form action='" . $post_url . "' method='post' name='frm'>";
        echo "<input type='hidden' name='invoice_number' value='" . htmlspecialchars($bill_id, ENT_QUOTES, 'UTF-8') . "'>";
        echo "<input type='hidden' name='eb_callbackurl' value='" . htmlspecialchars($eb_callbackurl, ENT_QUOTES, 'UTF-8') . "'>";
        echo "</form>";
        echo "<script language='JavaScript'>";
        echo "document.frm.submit();";
        echo "</script>";
        exit();
    }

    /**
     * Page de retour après paiement sur Ebilling
     * Vérifie le statut de la facture et affiche un message approprié
     */
    public function return_from_payment($invoice_id, $hash)
    {
        // Charger la facture et valider le hash
        $invoice = $this->invoices_model->get($invoice_id);

        if (!$invoice || $invoice->hash !== $hash) {
            set_alert('danger', 'Facture introuvable');
            redirect(site_url('clients/invoices'));
            return;
        }

        ebg_log('Client return from Ebilling payment portal', [
            'invoice_id' => $invoice_id,
            'invoice_status' => $invoice->status,
            'formatted_number' => $invoice->formatted_number
        ]);

        // Vérifier le statut de la facture
        if ((int)$invoice->status === Invoices_model::STATUS_PAID) {
            // Paiement reçu et validé
            set_alert('success', 'Paiement reçu avec succès. Merci !');
            ebg_log('Payment confirmed for returning client', [
                'invoice_id' => $invoice_id,
                'status' => 'paid'
            ]);
        } else {
            // Paiement pas encore reçu
            set_alert('warning', 'Le paiement n\'a pas encore été reçu. Si vous avez effectué le paiement, veuillez patienter quelques instants.');
            ebg_log('Payment not yet received for returning client', [
                'invoice_id' => $invoice_id,
                'status' => $invoice->status
            ]);
        }

        // Rediriger vers la page de la facture
        redirect(site_url('invoice/' . $invoice->id . '/' . $invoice->hash));
    }
}
