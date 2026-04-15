<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
    <div class="col-md-12">
        <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
            <i class="fa-regular fa-envelope tw-mr-1"></i>
            Configuration Brevo (Sendinblue)
        </h4>
        <hr class="hr-panel-separator" />
    </div>
</div>

<?php echo form_open(admin_url('settings/brevo_settings'), ['id' => 'brevo-settings-form']); ?>

<div class="row">
    <div class="col-md-6">
        <div class="panel_s">
            <div class="panel-body">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-base tw-text-neutral-700">
                    Configuration générale
                </h4>

                <!-- API Key -->
                <div class="form-group">
                    <label for="brevo_api_key" class="control-label">
                        Clé API Brevo <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           id="brevo_api_key"
                           name="settings[brevo_api_key]"
                           class="form-control"
                           value="<?php echo get_option('brevo_api_key'); ?>"
                           placeholder="xkeysib-...">
                    <p class="text-muted">
                        Obtenez votre clé API depuis <a href="https://app.brevo.com/settings/keys/api" target="_blank">votre compte Brevo</a>
                    </p>
                </div>

                <!-- Test Connection Button -->
                <div class="form-group">
                    <button type="button" id="test-brevo-connection" class="btn btn-info">
                        <i class="fa fa-plug"></i> Tester la connexion
                    </button>
                    <span id="test-connection-result"></span>
                </div>

                <!-- Debug Mode -->
                <div class="form-group">
                    <div class="checkbox checkbox-primary">
                        <input type="checkbox"
                               id="brevo_debug"
                               name="settings[brevo_debug]"
                               value="1"
                               <?php echo get_option('brevo_debug') == '1' ? 'checked' : ''; ?>>
                        <label for="brevo_debug">Activer le mode debug (logs détaillés)</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-body">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-base tw-text-neutral-700">
                    Configuration SMTP (Emails)
                </h4>

                <!-- Enable SMTP -->
                <div class="form-group">
                    <div class="checkbox checkbox-primary">
                        <input type="checkbox"
                               id="brevo_smtp_enabled"
                               name="settings[brevo_smtp_enabled]"
                               value="1"
                               <?php echo get_option('brevo_smtp_enabled') == '1' ? 'checked' : ''; ?>>
                        <label for="brevo_smtp_enabled">Utiliser Brevo SMTP pour les emails</label>
                    </div>
                </div>

                <div id="smtp-config" style="display: <?php echo get_option('brevo_smtp_enabled') == '1' ? 'block' : 'none'; ?>;">
                    <!-- SMTP Host -->
                    <div class="form-group">
                        <label for="brevo_smtp_host" class="control-label">Hôte SMTP</label>
                        <input type="text"
                               id="brevo_smtp_host"
                               name="settings[brevo_smtp_host]"
                               class="form-control"
                               value="<?php echo get_option('brevo_smtp_host'); ?>"
                               placeholder="smtp-relay.brevo.com">
                    </div>

                    <!-- SMTP Port -->
                    <div class="form-group">
                        <label for="brevo_smtp_port" class="control-label">Port SMTP</label>
                        <input type="number"
                               id="brevo_smtp_port"
                               name="settings[brevo_smtp_port]"
                               class="form-control"
                               value="<?php echo get_option('brevo_smtp_port'); ?>"
                               placeholder="587">
                        <p class="text-muted">Port 587 (TLS) ou 465 (SSL)</p>
                    </div>

                    <!-- SMTP Username -->
                    <div class="form-group">
                        <label for="brevo_smtp_username" class="control-label">Nom d'utilisateur SMTP</label>
                        <input type="text"
                               id="brevo_smtp_username"
                               name="settings[brevo_smtp_username]"
                               class="form-control"
                               value="<?php echo get_option('brevo_smtp_username'); ?>"
                               placeholder="Votre email Brevo">
                    </div>

                    <!-- SMTP Password -->
                    <div class="form-group">
                        <label for="brevo_smtp_password" class="control-label">Mot de passe SMTP</label>
                        <input type="password"
                               id="brevo_smtp_password"
                               name="settings[brevo_smtp_password]"
                               class="form-control"
                               value="<?php echo get_option('brevo_smtp_password'); ?>"
                               placeholder="Clé SMTP">
                        <p class="text-muted">
                            Différent de la clé API. Obtenez-la depuis <a href="https://app.brevo.com/settings/keys/smtp" target="_blank">SMTP & API</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="panel_s">
            <div class="panel-body">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-base tw-text-neutral-700">
                    Configuration SMS
                </h4>

                <!-- Enable SMS -->
                <div class="form-group">
                    <div class="checkbox checkbox-primary">
                        <input type="checkbox"
                               id="brevo_sms_enabled"
                               name="settings[brevo_sms_enabled]"
                               value="1"
                               <?php echo get_option('brevo_sms_enabled') == '1' ? 'checked' : ''; ?>>
                        <label for="brevo_sms_enabled">Activer l'envoi de SMS via Brevo</label>
                    </div>
                </div>

                <div id="sms-config" style="display: <?php echo get_option('brevo_sms_enabled') == '1' ? 'block' : 'none'; ?>;">
                    <!-- SMS Sender -->
                    <div class="form-group">
                        <label for="brevo_sms_sender" class="control-label">Nom de l'expéditeur</label>
                        <input type="text"
                               id="brevo_sms_sender"
                               name="settings[brevo_sms_sender]"
                               class="form-control"
                               value="<?php echo get_option('brevo_sms_sender'); ?>"
                               placeholder="Perfex"
                               maxlength="11">
                        <p class="text-muted">Maximum 11 caractères alphanumériques</p>
                    </div>

                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i>
                        <strong>Note :</strong> L'envoi de SMS nécessite des crédits SMS dans votre compte Brevo.
                        Vérifiez vos crédits dans <a href="https://app.brevo.com/account/plan" target="_blank">votre compte</a>.
                    </div>
                </div>
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-body">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-base tw-text-neutral-700">
                    Test d'envoi
                </h4>

                <!-- Test SMS -->
                <div class="form-group">
                    <label for="test_sms_number" class="control-label">Test SMS</label>
                    <div class="input-group">
                        <input type="text"
                               id="test_sms_number"
                               class="form-control"
                               placeholder="+237xxxxxxxxx">
                        <span class="input-group-btn">
                            <button type="button" id="send-test-sms" class="btn btn-success">
                                <i class="fa fa-mobile"></i> Envoyer test SMS
                            </button>
                        </span>
                    </div>
                    <span id="test-sms-result"></span>
                </div>

                <!-- Test Email -->
                <div class="form-group">
                    <label for="test_email" class="control-label">Test Email</label>
                    <div class="input-group">
                        <input type="email"
                               id="test_email"
                               class="form-control"
                               placeholder="email@example.com">
                        <span class="input-group-btn">
                            <button type="button" id="send-test-email" class="btn btn-success">
                                <i class="fa fa-envelope"></i> Envoyer test email
                            </button>
                        </span>
                    </div>
                    <span id="test-email-result"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <hr class="hr-panel-separator" />
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-check"></i> Enregistrer les paramètres
        </button>
    </div>
</div>

<?php echo form_close(); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle SMTP config visibility
    document.getElementById('brevo_smtp_enabled').addEventListener('change', function() {
        document.getElementById('smtp-config').style.display = this.checked ? 'block' : 'none';
    });

    // Toggle SMS config visibility
    document.getElementById('brevo_sms_enabled').addEventListener('change', function() {
        document.getElementById('sms-config').style.display = this.checked ? 'block' : 'none';
    });

    // Test connection
    document.getElementById('test-brevo-connection').addEventListener('click', function() {
        const btn = this;
        const resultSpan = document.getElementById('test-connection-result');

        btn.disabled = true;
        resultSpan.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Test en cours...';

        $.post(admin_url + 'brevo/test_connection', {}, function(response) {
            if (response.success) {
                resultSpan.innerHTML = '<span class="text-success"><i class="fa fa-check"></i> Connexion réussie!</span>';
            } else {
                resultSpan.innerHTML = '<span class="text-danger"><i class="fa fa-times"></i> ' + response.message + '</span>';
            }
            btn.disabled = false;
        }).fail(function() {
            resultSpan.innerHTML = '<span class="text-danger"><i class="fa fa-times"></i> Erreur de connexion</span>';
            btn.disabled = false;
        });
    });

    // Send test SMS
    document.getElementById('send-test-sms').addEventListener('click', function() {
        const btn = this;
        const number = document.getElementById('test_sms_number').value;
        const resultSpan = document.getElementById('test-sms-result');

        if (!number) {
            alert('Veuillez entrer un numéro de téléphone');
            return;
        }

        btn.disabled = true;
        resultSpan.innerHTML = '<br><i class="fa fa-spinner fa-spin"></i> Envoi en cours...';

        $.post(admin_url + 'brevo/send_test_sms', {number: number}, function(response) {
            if (response.success) {
                resultSpan.innerHTML = '<br><span class="text-success"><i class="fa fa-check"></i> SMS envoyé!</span>';
            } else {
                resultSpan.innerHTML = '<br><span class="text-danger"><i class="fa fa-times"></i> ' + response.message + '</span>';
            }
            btn.disabled = false;
        }).fail(function() {
            resultSpan.innerHTML = '<br><span class="text-danger"><i class="fa fa-times"></i> Erreur d\'envoi</span>';
            btn.disabled = false;
        });
    });

    // Send test email
    document.getElementById('send-test-email').addEventListener('click', function() {
        const btn = this;
        const email = document.getElementById('test_email').value;
        const resultSpan = document.getElementById('test-email-result');

        if (!email) {
            alert('Veuillez entrer une adresse email');
            return;
        }

        btn.disabled = true;
        resultSpan.innerHTML = '<br><i class="fa fa-spinner fa-spin"></i> Envoi en cours...';

        $.post(admin_url + 'brevo/send_test_email', {email: email}, function(response) {
            if (response.success) {
                resultSpan.innerHTML = '<br><span class="text-success"><i class="fa fa-check"></i> Email envoyé!</span>';
            } else {
                resultSpan.innerHTML = '<br><span class="text-danger"><i class="fa fa-times"></i> ' + response.message + '</span>';
            }
            btn.disabled = false;
        }).fail(function() {
            resultSpan.innerHTML = '<br><span class="text-danger"><i class="fa fa-times"></i> Erreur d\'envoi</span>';
            btn.disabled = false;
        });
    });
});
</script>
