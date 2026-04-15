<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="col-md-12">
    <div class="panel_s">
        <div class="panel-body">
            <h4 class="no-margin">Ebilling + GPSWOX <span class="ebg-badge">Module v1.1</span></h4>
            <hr class="hr-panel-heading" />

            <!-- Alert pour configuration Ebilling -->
            <div class="alert alert-success">
                <h5><i class="fa fa-check-circle"></i> Configuration de la passerelle Ebilling</h5>
                <p>La passerelle de paiement Ebilling est maintenant disponible dans :</p>
                <p><strong>Setup → Settings → Payment Gateways → Ebilling (Mobile Money & Cartes)</strong></p>
                <a href="<?php echo admin_url('settings?group=payment_gateways'); ?>" class="btn btn-success">
                    <i class="fa fa-cog"></i> Configurer Ebilling
                </a>
            </div>

            <!-- URL Webhook -->
            <div class="alert alert-info">
                <strong><i class="fa fa-link"></i> URL Webhook à configurer dans Ebilling :</strong><br>
                <code style="font-size: 14px; padding: 5px 10px; background: white; border-radius: 3px;"><?php echo site_url('ebilling/webhook'); ?></code>
                <button type="button" class="btn btn-xs btn-primary pull-right" onclick="navigator.clipboard.writeText('<?php echo site_url('ebilling/webhook'); ?>')">
                    <i class="fa fa-copy"></i> Copier
                </button>
            </div>

            <div class="row">
                <!-- Configuration GPSWOX -->
                <div class="col-md-6">
                    <h5><i class="fa fa-map-marker"></i> Configuration GPSWOX</h5>
                    <p class="text-muted">Activez la synchronisation automatique avec GPSWOX après paiement.</p>

                    <?php echo render_input('settings[gpswox_base_url]', 'Base URL GPSWOX', get_option('gpswox_base_url'), 'text', [
                        'placeholder' => 'https://app.mbiratracker.com'
                    ]); ?>

                    <?php echo render_input('settings[gpswox_api_key]', 'API Key GPSWOX', get_option('gpswox_api_key'), 'password', [
                        'required' => true
                    ]); ?>

                    <div class="alert alert-warning">
                        <strong><i class="fa fa-info-circle"></i> Mapping Client → GPSWOX</strong><br>
                        Le champ personnalisé <code>gpswox_user_id</code> a été créé automatiquement.<br>
                        <small>Renseignez l'ID utilisateur GPSWOX dans la fiche client Perfex pour activer la synchronisation automatique.</small>
                    </div>
                </div>

                <!-- Informations et statistiques -->
                <div class="col-md-6">
                    <h5><i class="fa fa-info-circle"></i> Flux de fonctionnement</h5>
                    <ol class="text-muted" style="font-size: 13px; line-height: 1.8;">
                        <li>Client paie une facture via Ebilling (Mobile Money ou Carte)</li>
                        <li>Webhook reçoit la notification de paiement</li>
                        <li>Facture marquée comme payée dans Perfex</li>
                        <li>API GPSWOX appelée pour renouveler l'abonnement (30 jours)</li>
                    </ol>

                    <hr style="margin: 15px 0;">
                    <h5><i class="fa fa-bar-chart"></i> Statistiques des transactions</h5>
                    <?php
                    $CI = &get_instance();
                    $total_txn = 0;
                    $paid_txn = 0;
                    $pending_txn = 0;

                    // Vérifier si la table existe (module activé)
                    if ($CI->db->table_exists(db_prefix() . 'ebilling_transactions')) {
                        $total_txn = $CI->db->count_all(db_prefix() . 'ebilling_transactions');
                        $paid_txn = $CI->db->where('status', 'paid')->count_all_results(db_prefix() . 'ebilling_transactions');
                        $pending_txn = $CI->db->where('status', 'pending')->count_all_results(db_prefix() . 'ebilling_transactions');
                    }
                    ?>
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <h3 class="text-info bold"><?php echo $total_txn; ?></h3>
                            <small class="text-muted">Total transactions</small>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3 class="text-success bold"><?php echo $paid_txn; ?></h3>
                            <small class="text-muted">Paiements réussis</small>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3 class="text-warning bold"><?php echo $pending_txn; ?></h3>
                            <small class="text-muted">En attente</small>
                        </div>
                    </div>
                </div>
            </div>

            <hr />
            <div class="alert alert-info">
                <h6><strong><i class="fa fa-shield"></i> Astuces et sécurité</strong></h6>
                <ul class="mb-0" style="font-size: 13px;">
                    <li>Configurez la passerelle Ebilling dans <strong>Setup → Settings → Payment Gateways</strong></li>
                    <li>Utilisez toujours le <strong>Mode Test (LAB)</strong> avant de passer en production</li>
                    <li>Ne divulguez jamais votre <code>Shared Key</code> ou <code>API Key GPSWOX</code></li>
                    <li>Configurez un <strong>Webhook Secret</strong> pour sécuriser les callbacks Ebilling</li>
                    <li>Vérifiez que le custom field <code>gpswox_user_id</code> est rempli pour chaque client</li>
                    <li>Les logs détaillés sont disponibles dans <code>application/logs/</code> (préfixe [EBG])</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.ebg-badge {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
}
</style>
