<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

<?php
$sources  = get_instance()->leads_model->get_source();
$statuses = get_instance()->leads_model->get_status();
$staff    = get_instance()->staff_model->get('', ['active' => 1]);
?>
<div id="wrapper">

  <div class="content">
    <div class="panel_s">
      <div class="panel-body">

        <ul class="nav nav-tabs" role="tablist">
          <li class="nav-item">
            <a class="nav-link active" href="#connection-settings" role="tab" data-toggle="tab"><?php echo _l('Connection Settings'); ?></a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#lead-settings" role="tab" data-toggle="tab"><?php echo _l('Lead Automation'); ?></a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#openai-settings" role="tab" data-toggle="tab"><?php echo _l('OpenAI Integration'); ?></a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#advanced-settings" role="tab" data-toggle="tab"><?php echo _l('Advanced Settings'); ?></a>
          </li>
        </ul>
        <?php echo form_open(admin_url('whatsapp/save_settings'), ['id' => 'whatsapp_settings_form']); ?>

        <div class="tab-content mtop20">
          <!-- Connection Settings -->
          <div class="tab-pane active" id="connection-settings" role="tabpanel">
            <!-- Begin Original WhatsApp Cloud Configuration Content -->

            <!-- Embedded OAuth Setup Row -->
            <div class="row mtop30">
              <div id="embed_instructions" class="col-md-6 animated fadeIn hide">
                <p><strong><?php echo _l('Embed (OAuth)'); ?>:</strong></p>
                <ol class="mtop10">
                  <li>
                    <?php echo _l('Enter your'); ?> <code><?php echo _l('Meta App ID'); ?></code> <?php echo _l('and'); ?> <code><?php echo _l('App Secret'); ?></code>.
                    <ul class="mtop5 small">
                      <li>🔗 <a href="https://developers.facebook.com/docs/development/create-an-app/" target="_blank"><?php echo _l('How to create a Meta App'); ?></a></li>
                      <li>🔗 <a href="https://developers.facebook.com/docs/graph-api/securing-requests/#appsecret_proof" target="_blank"><?php echo _l('Understanding App Secret'); ?></a></li>
                    </ul>
                  </li>
                  <li><?php echo _l('Click'); ?> <strong><?php echo _l('Save Settings'); ?></strong>.</li>
                  <li><?php echo _l('Click'); ?> <strong><?php echo _l('Start Embedded Signup'); ?></strong> <?php echo _l('to launch the Meta OAuth popup.'); ?></li>
                  <li><?php echo _l('Complete the authorization and then click'); ?> <strong><?php echo _l('Connect'); ?></strong>.</li>
                  <li><?php echo _l('Use'); ?> <strong><?php echo _l('Register Webhook'); ?></strong> <?php echo _l('to subscribe to WhatsApp events.'); ?></li>
                </ol>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-3" id="field_business_id">
                <?php echo render_input('settings[whatsapp_business_id]', '📱 Business ID', get_option('whatsapp_business_id'), 'text', ['id' => 'business_id']); ?>
              </div>
            
              <div class="col-sm-3" id="field_waba_id">
                <?php echo render_input('settings[whatsapp_business_account_id]', '🏷️ WABA ID', get_option('whatsapp_business_account_id')); ?>
              </div>
            
              <div class="col-sm-3" id="field_access_token">
                <?php echo render_input('settings[whatsapp_access_token]', '🔑 Access Token', get_option('whatsapp_access_token')); ?>
              </div>
            
              <div class="col-sm-3" id="field_webhook_url">
                <?php echo render_input('settings[whatsapp_webhook_url]', '🌐 Webhook URL', base_url('whatsapp/webhook/getdata'), 'text', ['readonly' => true]); ?>
              </div>
            
              <div class="col-sm-3" id="field_webhook_token">
                <?php echo render_input('settings[whatsapp_webhook_token]', '🔐 Webhook Token', get_option('whatsapp_webhook_token')); ?>
              </div>
            
              <div class="col-sm-3" id="field_meta_app_id">
                <?php echo render_input('settings[whatsapp_meta_app_id]', '🆔 Meta App ID', get_option('whatsapp_meta_app_id')); ?>
              </div>
            
              <div class="col-sm-3" id="field_meta_app_secret">
                <?php echo render_input('settings[whatsapp_meta_app_secret]', '🛡️ App Secret', get_option('whatsapp_meta_app_secret')); ?>
              </div>
            
              <div class="col-sm-3" id="field_config_id">
                <?php echo render_input('settings[whatsapp_config_id]', '🔧 Meta Config ID', get_option('whatsapp_config_id')); ?>
              </div>
            
              <div class="col-sm-3" id="field_connection_method">
                <label for="connection_method" class="control-label">🔗 Connection Method</label>
                <select name="settings[whatsapp_connection_method]" id="connection_method" class="selectpicker form-control">
                  <option value="manual" <?php echo get_option('whatsapp_connection_method') == 'manual' ? 'selected' : ''; ?>>Manual</option>
                  <option value="embed" <?php echo get_option('whatsapp_connection_method') == 'embed' ? 'selected' : ''; ?>>Embed (OAuth)</option>
                </select>
              </div>
            </div>
            <hr class="hr-panel-separator" />
            <div class="mtop10" id="token_info_badges"></div>
            <div class="grid grid-cols-1 gap-6 mt-6 text-sm" id="connection_result_area" style="display: none;">
              <div id="connection_details_output"></div>
              <div id="webhook_output" class="mt-3 text-sm text-gray-800 font-medium"></div>
            </div>
            <div id="embed_connect_area" class="hidden text-center mtop20">
              <button id="launch_embed_signup" type="button" class="btn btn-facebook">
                <i class="fa-brands fa-facebook-messenger"></i> Start Embedded Signup
              </button>
              <div class="mtop15">
                <strong>📎 Redirect URI for Meta OAuth:</strong>
                <code class="text-primary"><?php echo base_url('whatsapp/webhook/oauth_callback'); ?></code>
                <a href="#" class="btn btn-link btn-sm" onclick="copyToClipboard('#oauth_uri')">Copy</a>
              </div>
              <div class="mtop10 text-muted">Used only for <strong>Embed</strong> method with Meta App OAuth.</div>
            </div>
          </div>
          <!-- Lead Settings -->
          <div class="tab-pane" id="lead-settings" role="tabpanel">
            <div class="row">
              <!-- Lead Status -->
              <div class="col-md-4">
                <div class="form-group">
                  <label for="settings[whatsapp_lead_status]">📊 Lead Status</label>
                  <select class="form-control selectpicker" name="settings[whatsapp_lead_status]" data-width="100%">
                    <?php foreach ($statuses as $status) { ?>
                      <option value="<?php echo $status['id']; ?>" <?php echo (get_option('whatsapp_lead_status') == $status['id']) ? 'selected' : ''; ?>>
                        <?php echo $status['name']; ?>
                      </option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <!-- Lead Source -->
              <div class="col-md-4">
                <div class="form-group">
                  <label for="settings[whatsapp_lead_source]">🌍 Lead Source</label>
                  <select class="form-control selectpicker" name="settings[whatsapp_lead_source]" data-width="100%">
                    <?php foreach ($sources as $source) { ?>
                      <option value="<?php echo $source['id']; ?>" <?php echo (get_option('whatsapp_lead_source') == $source['id']) ? 'selected' : ''; ?>>
                        <?php echo $source['name']; ?>
                      </option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <!-- Assigned Staff -->
              <div class="col-md-4">
                <div class="form-group">
                  <label for="settings[whatsapp_lead_assigned]">👨‍💼 Assigned Staff</label>
                  <select class="form-control selectpicker" name="settings[whatsapp_lead_assigned]" data-width="100%">
                    <?php foreach ($staff as $staff_member) { ?>
                      <option value="<?php echo $staff_member['staffid']; ?>" <?php echo (get_option('whatsapp_lead_assigned') == $staff_member['staffid']) ? 'selected' : ''; ?>>
                        <?php echo $staff_member['firstname'] . ' ' . $staff_member['lastname']; ?>
                      </option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label for="whatsapp_auto_lead_settings"><?php echo _l('convert_whatsapp_message_to_lead'); ?></label>
                  <select class="selectpicker" data-width="100%" name="settings[whatsapp_auto_lead_settings]">
                    <option value="disable" <?php echo (get_option('whatsapp_auto_lead_settings') == 'disable') ? 'selected' : ''; ?>><?php echo _l('disable'); ?></option>
                    <option value="enable" <?php echo (get_option('whatsapp_auto_lead_settings') == 'enable') ? 'selected' : ''; ?>><?php echo _l('enable'); ?></option>
                  </select>
                </div>
              </div>

            </div>
          </div>
        <!-- AI Integration -->
        <div class="tab-pane" id="openai-settings" role="tabpanel">
          <div class="row">
        
        
            <!-- Select Provider -->
            <div class="col-md-6">
              <div class="form-group">
                <label for="settings[ai_provider]">
                  <?php echo _l('AI Provider'); ?> 🧠
                </label>
                <select class="form-control selectpicker" name="settings[ai_provider]" id="ai-provider" data-width="100%">
                  <option value="disable" <?php echo (get_option('ai_provider') == 'disable') ? 'selected' : ''; ?>>
                    <?php echo _l('Disable'); ?> ❌
                  </option>
                  <option value="openai" <?php echo (get_option('ai_provider') == 'openai') ? 'selected' : ''; ?>>
                    OpenAI 🤖
                  </option>
                  <option value="gemini" <?php echo (get_option('ai_provider') == 'gemini') ? 'selected' : ''; ?>>
                    Gemini 🔮
                  </option>
                </select>
              </div>
            </div>
        
            <!-- Enable Mode (Manual / Auto) -->
            <div class="col-md-6">
              <div class="form-group">
                <label for="settings[whatsapp_openai_status]">
                  <?php echo _l('Enable OpenAI Integration'); ?> ⚙️
                </label>
                <select class="form-control selectpicker" name="settings[whatsapp_openai_status]" data-width="100%">
                  <option value="disable" <?php echo (get_option('whatsapp_openai_status') == 'disable') ? 'selected' : ''; ?>>
                    <?php echo _l('Disable'); ?> ❌
                  </option>
                  <option value="manual" <?php echo (get_option('whatsapp_openai_status') == 'manual') ? 'selected' : ''; ?>>
                    <?php echo _l('Manual'); ?> ⚙️
                  </option>
                  <option value="auto" <?php echo (get_option('whatsapp_openai_status') == 'auto') ? 'selected' : ''; ?>>
                    <?php echo _l('Auto'); ?> 🚀
                  </option>
                </select>
              </div>
            </div>
        
            <!-- OpenAI Token -->
            <div class="col-md-6 ai-key-field" id="openai-key-field" style="display: <?php echo get_option('ai_provider') === 'openai' ? 'block' : 'none'; ?>;">
              <div class="form-group">
                <label for="settings[whatsapp_openai_token]">
                  <?php echo _l('OpenAI API Key'); ?> 🔑
                </label>
                <?php echo render_input('settings[whatsapp_openai_token]', '', get_option('whatsapp_openai_token'), 'text', ['autocomplete' => 'off']); ?>
              </div>
            </div>
        
            <!-- Gemini Token -->
            <div class="col-md-6 ai-key-field" id="gemini-key-field" style="display: <?php echo get_option('ai_provider') === 'gemini' ? 'block' : 'none'; ?>;">
              <div class="form-group">
                <label for="settings[gemini_api_key]">
                  <?php echo _l('Gemini API Key'); ?> 🔑
                </label>
                <?php echo render_input('settings[gemini_api_key]', '', get_option('gemini_api_key'), 'text', ['autocomplete' => 'off']); ?>
              </div>
            </div>
        
            <!-- System Prompt -->
            <div class="col-md-12 ai-training-field" id="system-prompt-field" style="display: <?php echo in_array(get_option('ai_provider'), ['openai','gemini']) ? 'block' : 'none'; ?>;">
              <div class="form-group">
                <label for="settings[ai_system_prompt]">
                  <?php echo _l('AI System Prompt'); ?> 📝
                </label>
                <?php echo render_textarea('settings[ai_system_prompt]', '', get_option('ai_system_prompt'), ['rows' => 4]); ?>
                <small class="text-muted">
                  <?php echo _l('Provide a base system prompt to guide the AI’s overall behavior.'); ?>
                </small>
              </div>
            </div>
        
            <!-- Additional Training Data -->
            <div class="col-md-12 ai-training-field" id="training-data-field" style="display: <?php echo in_array(get_option('ai_provider'), ['openai','gemini']) ? 'block' : 'none'; ?>;">
              <div class="form-group">
                <label for="settings[ai_training_data]">
                  <?php echo _l('AI Training Data'); ?> 📚
                </label>
                <?php echo render_textarea('settings[ai_training_data]', '', get_option('ai_training_data'), ['rows' => 6]); ?>
                <small class="text-muted">
                  <?php echo _l('Paste any example prompts, FAQs, or context here for the AI to reference.'); ?>
                </small>
              </div>
            </div>
        
          </div>
        </div>



          <!-- Advanced Settings -->
        <div class="tab-pane" id="advanced-settings" role="tabpanel">
            <div class="row">
                <!-- Cron Job Command -->
                <div class="col-sm-9 col-12">
                    <div class="form-group">
                        <label><?php echo _l('Cron Job Command'); ?> 🖥️</label>
                            <input type="text" id="cronjob" value="<?php echo 'wget -q -O- ' . base_url('whatsapp/webhook/send_campaign'); ?>" class="form-control" readonly>
                    </div>
                </div>
        
                <!-- Reload Interval -->
                <div class="col-md-3 col-12">
                    <div class="form-group">
                        <label for="settings[whatsapp_reload_interval]"><?php echo _l('Reload Interval (seconds) ⏱️'); ?></label>
                        <input type="number" name="settings[whatsapp_reload_interval]" class="form-control" min="1" value="<?php echo get_option('whatsapp_reload_interval') ?: 5; ?>">
                    </div>
                </div>
            </div>
        
            <br>
        
            <div class="row">
                <!-- Enable Webhooks Forward -->
                <div class="col-md-4 col-12">
                    <div class="form-group">
                        <label for="enable_webhooks_select"><?php echo _l('Enable Webhooks 🔌'); ?></label>
                        <select id="enable_webhooks_select" class="form-control selectpicker" name="settings[enable_webhooks]" data-width="100%">
                            <option value="1" <?php echo ('1' == get_option('enable_webhooks')) ? 'selected' : ''; ?>><?php echo _l('Enable'); ?></option>
                            <option value="0" <?php echo ('0' == get_option('enable_webhooks')) ? 'selected' : ''; ?>><?php echo _l('Disable'); ?></option>
                        </select>
                    </div>
                </div>
        
                <!-- Webhooks URL Input -->
                <div class="col-md-4 col-12">
                    <div class="form-group">
                        <label for="settings[webhooks_url]">
                            <i class="fa fa-question-circle padding-5"></i>
                            <?php echo _l('Webhooks Forward URL 🌐'); ?>
                        </label>
                        <?php echo render_input('settings[webhooks_url]', '', get_option('webhooks_url'), 'text'); ?>
                    </div>
                </div>
        
                <!-- Blueticks Status Dropdown -->
                <div class="col-md-4 col-12">
                    <div class="form-group">
                        <label for="settings[whatsapp_blueticks_status]">
                            <i class="fa fa-question-circle padding-5"></i>
                            <?php echo _l('WhatsApp Blueticks Status ✔️'); ?>
                        </label>
                        <select class="form-control selectpicker" data-width="100%" name="settings[whatsapp_blueticks_status]">
                            <option value="disable" <?php echo (get_option('whatsapp_blueticks_status') == 'disable') ? 'selected' : ''; ?>><?php echo _l('Disable'); ?></option>
                            <option value="enable" <?php echo (get_option('whatsapp_blueticks_status') == 'enable') ? 'selected' : ''; ?>><?php echo _l('Enable'); ?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

          <div class="row mtop15">
            <div class="col-md-12">
            <button type="submit" class="btn btn-success" id="saveSettingsButton">
              <i class="fa fa-save"></i> Save Settings
            </button>
              <button id="connect_btn" type="button" class="btn btn-primary"><i class="fa fa-link"></i> Connect</button>
              <button onclick="disconnectConnection()" type="button" class="btn btn-danger"><i class="fa fa-unlink"></i> Disconnect</button>
              <button id="btn_register_webhook" class="btn btn-success btn-sm webhook_controls" onclick="registerWhatsappWebhook()">🔗 Register Webhook</button>
              <button id="btn_unregister_webhook" class="btn btn-danger btn-sm webhook_controls" onclick="unregisterWhatsappWebhook()">🔌 Unregister Webhook</button>
            </div>
          </div>
        </div>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</div>



<?php init_tail(); ?>
<script>
  function copyToClipboard(element) {
    var $temp = $("<input>");
    $("body").append($temp);
    $temp.val($(element).val()).select();
    document.execCommand("copy");
    $temp.remove();
  }
</script>
<script>
  $(function() {


    const WhatsappCloud = {
      method: '',
      token: '',
      businessId: '',
      isConnected: "<?php echo get_option('whatsapp_connection_status') === 'connected' ? 'yes' : 'no'; ?>",
      bindEvents: function() {
        $('#connect_btn').on('click', this.connect);
      },

      init: function() {
        this.method = $('#connection_method').val();
        this.token = $('#api_key').val()?.trim() || '';
        this.businessId = $('#business_id').val()?.trim() || '';

        this.setupEmbedSDK();
        handleConnectionMethodUI();
        this.bindEvents();

        if (this.isConnected === 'yes') {
          $('#connect_btn').addClass('hidden');
          $('.webhook_controls').show();
          this.connect(); // 👈 Auto-call to fetch connection info
        }

        if (this.method === 'embed') {
          const hasToken = this.token.length > 10;
          const hasBusiness = this.businessId.length > 5;

          $('.webhook_controls').toggle(this.isConnected === 'yes');

          if (hasToken && hasBusiness) {
            this.connect(); // 👈 Connect silently with embed method
          } else {
            $('#embed_connect_area').removeClass('hidden');
          }
        }
      },

      setupEmbedSDK: function() {
        if (!document.getElementById('facebook-jssdk')) {
          const js = document.createElement('script');
          js.id = 'facebook-jssdk';
          js.src = 'https://connect.facebook.net/en_US/sdk.js';
          document.body.appendChild(js);
        }

        const CONFIG_ID = "<?php echo get_option('whatsapp_config_id'); ?>";

        window.fbAsyncInit = () => {
          FB.init({
            appId: '<?php echo get_option("whatsapp_meta_app_id"); ?>',
            autoLogAppEvents: true,
            xfbml: true,
            version: 'v22.0'
          });

          console.log('[Facebook SDK] Initialized');
        };

        $('#launch_embed_signup').off('click').on('click', function() {
          const configId = CONFIG_ID?.trim(); // pulled from PHP

          if (!configId) {
            alert_float('danger', '⚠️ Missing Configuration ID in system settings.');
            return;
          }

          console.log('[Embed Signup] Launching with Config ID:', configId);

          const $btn = $(this);
          $btn.prop('disabled', true).text('Launching...');

          try {
            FB.login(null, {
              config_id: configId,
              response_type: 'code',
              override_default_response_type: true,
              extras: {
                setup: {},
                sessionInfoVersion: '3'
              }
            });
          } catch (e) {
            console.error('[Embed Signup Error]', e);
            alert_float('danger', '❌ Failed to launch embed flow.');
            $btn.prop('disabled', false).text('Start Embedded Signup');
          }
        });

      },

connect: async function() {
  const $btn = $('#connect_btn');
  const originalBtnHtml = $btn.html();

  const $embedArea = $('#embed_connect_area');
  const $registerBtn = $('#btn_register_webhook');
  const $unregisterBtn = $('#btn_unregister_webhook');
  const $tokenBadges = $('#token_info_badges');
  const $connectionResult = $('#connection_result_area');

  // Show loading state on button
  $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Connecting...');

  try {
    const response = await $.post(admin_url + 'whatsapp/connection_status');
    const res = typeof response === 'string' ? JSON.parse(response) : response;

    if (!res.success) throw new Error(res.message || 'Connection failed.');

    alert_float('success', res.message || 'Connected successfully!');
    console.log('WhatsApp connected:', res);

    $btn.html('<i class="fa fa-check-circle text-success"></i> Connected!').addClass('hidden');

    if (res.waba_id) {
      window._CURRENT_WABA_ID = res.waba_id;
    }

    $embedArea.slideUp();
    $tokenBadges.removeClass('hidden');
    $connectionResult.removeClass('hidden');

    this.renderConnectionDetails?.(res);
    this.renderTokenMeta?.(res.permissions || {});

    const hasWebhook = Array.isArray(res.webhook?.data) && res.webhook.data.length > 0;
    $registerBtn.toggle(!hasWebhook);
    $unregisterBtn.toggle(hasWebhook);

    this.updateWebhookUI?.(res.webhook);

  } catch (err) {
    console.error('Error during WhatsApp connect:', err);
    alert_float('danger', err.message || 'Connection request failed.');
    $btn.html(originalBtnHtml); // Restore original content
  } finally {
    if (!$btn.hasClass('hidden')) {
      $btn.prop('disabled', false);
    }
  }
},


      disconnect: async function() {
        const wabaId = $('#whatsapp_business_account_id').val() || window._CURRENT_WABA_ID;
        if (!confirm('Disconnect everything (including webhook)?')) return;

        try {
          if (wabaId) {
            await $.post(admin_url + 'whatsapp/unregister_webhook');
            $('#webhook_output').html('<span class="text-red-600">❌ Webhook is not registered.</span>');
          }

          await $.post(admin_url + 'whatsapp/disconnect');
          alert_float('success', 'Disconnected.');
          location.reload();
        } catch (err) {
          alert_float('danger', 'Disconnect failed: ' + (err.responseText || 'Unknown error.'));
        }
      },

      registerWebhook: function() {
        $.post(admin_url + 'whatsapp/register_webhook', () => {
          alert_float('success', '✅ Webhook registered.');
          this.connect();
        }).fail(err => {
          alert_float('danger', 'Webhook register failed: ' + err.responseText);
        });
      },

      unregisterWebhook: function() {
        const wabaId = $('#whatsapp_business_account_id').val() || window._CURRENT_WABA_ID;

        if (!wabaId) {
          return alert_float('danger', 'WABA ID is required.');
        }

        $.post(admin_url + 'whatsapp/unregister_webhook', {
          waba_id: wabaId
        }, () => {
          alert_float('success', '❌ Webhook unregistered.');
          this.connect();
        }).fail(err => {
          alert_float('danger', 'Unregister failed: ' + err.responseText);
        });
      },

      renderTokenMeta: function(permissions) {
        const data = permissions?.data || {};
        let html = '';
        if (data.application) html += `<span class="badge badge-info">App: ${data.application}</span>`;
        if (typeof data.is_valid !== 'undefined') {
          html += `<span class="badge ${data.is_valid ? 'badge-success' : 'badge-danger'}">${data.is_valid ? '✅ Valid' : '❌ Invalid'}</span>`;
        }
        $('#token_info_badges').html(html);
      },

renderConnectionDetails: function(res) {
  const permissions = res.permissions?.data || {};
  const webhookEntries = res.webhook?.data || [];
  const scopes = permissions.scopes || [];

  const scopesRequired = [
    'business_management',
    'whatsapp_business_management',
    'whatsapp_business_messaging',
    'whatsapp_business_manage_events'
  ];

  const scopesHtml = scopesRequired.map(scope => {
    const has = scopes.includes(scope);
    return `
      <div class="flex items-center gap-2 text-sm">
        <span class="${has ? 'text-green-600' : 'text-red-600'}">
          ${has ? '✅' : '❌'}
        </span>
        <code>${scope}</code>
      </div>
    `;
  }).join('');

  const tokenHTML = `
    <div class="bg-white shadow border rounded-lg p-5">
      <h3 class="font-semibold text-lg text-gray-800 mb-4">🔐 Token Info</h3>
      <p><strong>App:</strong> ${permissions.application || 'N/A'}</p>
      <p><strong>User ID:</strong> ${permissions.user_id || 'N/A'}</p>
      <p><strong>Token Valid:</strong> ${permissions.is_valid ? '✅ Yes' : '❌ No'}</p>
      <p><strong>Expires At:</strong> ${permissions.expires_at ? new Date(permissions.expires_at * 1000).toLocaleString() : 'Never'}</p>
      <p><strong>Data Access Expires:</strong> ${permissions.data_access_expires_at ? new Date(permissions.data_access_expires_at * 1000).toLocaleString() : 'N/A'}</p>
      <p class="mt-3 font-semibold">Required Scopes:</p>
      <div class="mt-2 space-y-1">${scopesHtml}</div>
    </div>
  `;

  const wabaCards = (res.wab_accounts || []).map(waba => {
    const w = waba.waba || {};
    const phones = waba.phones?.data || [];

    const phoneHTML = phones.length > 0
      ? phones.map(p => `
        <div class="border border-gray-200 rounded p-3 bg-gray-50 mb-2">
          <p><strong>📞 Number:</strong> ${p.display_phone_number}</p>
          <p><strong>Verified Name:</strong> ${p.verified_name}</p>
          <p><strong>Status:</strong> 
            <span class="font-semibold text-${p.code_verification_status === 'EXPIRED' ? 'red' : 'green'}-600">
              ${p.code_verification_status}
            </span>
          </p>
          <p><strong>Quality Rating:</strong> 
            <span class="font-semibold text-${p.quality_rating === 'GREEN' ? 'green' : 'orange'}-600">
              ${p.quality_rating}
            </span>
          </p>
          <p><strong>Tier:</strong> ${p.throughput?.level || 'N/A'}</p>
          <p><strong>Webhook:</strong> 
            <a href="${p.webhook_configuration?.application}" target="_blank" class="text-blue-600 underline">
              ${p.webhook_configuration?.application || 'N/A'}
            </a>
          </p>
        </div>
      `).join('')
      : `<p class="text-gray-600">No phone numbers found.</p>`;

    return `
      <div class="bg-white shadow border rounded-lg p-5">
        <h3 class="font-semibold text-lg text-gray-800 mb-4">💼 ${w.name || 'Unnamed WABA'}</h3>
        <p><strong>WABA ID:</strong> ${waba.waba_id}</p>
        <p><strong>Currency:</strong> ${w.currency || 'N/A'}</p>
        <p><strong>Timezone:</strong> ${w.timezone_id || 'N/A'}</p>
        <p><strong>Namespace:</strong> <code>${w.message_template_namespace || 'N/A'}</code></p>
        <div class="mt-4">
          <h4 class="font-medium mb-2">📱 Phone Numbers</h4>
          ${phoneHTML}
        </div>
      </div>
    `;
  }).join('');

  const wbaWebhooks = webhookEntries.filter(w => w.object === 'whatsapp_business_account');
  const webhookHTML = `
    <div class="bg-white shadow border rounded-lg p-5">
      <h3 class="font-semibold text-lg text-gray-800 mb-4">🔗 Webhooks</h3>
      ${wbaWebhooks.length === 0
        ? `<p class="text-red-600">No WhatsApp webhooks configured.</p>`
        : wbaWebhooks.map(entry => {
            return `
              <div class="mb-4">
                <p><strong>Callback:</strong> 
                  <a href="${entry.callback_url}" target="_blank" class="text-blue-600 underline">${entry.callback_url}</a>
                </p>
                <p><strong>Status:</strong> 
                  <span class="${entry.active ? 'text-green-600' : 'text-red-600'} font-semibold">
                    ${entry.active ? '✅ Active' : '❌ Inactive'}
                  </span>
                </p>
                <div class="mt-2 grid grid-cols-2 gap-2">
                  ${entry.fields.map(f => `
                    <div class="border px-2 py-1 rounded text-xs bg-gray-100 flex justify-between">
                      <code>${f.name}</code><span>v${f.version}</span>
                    </div>
                  `).join('')}
                </div>
              </div>
            `;
          }).join('')}
    </div>
  `;

  // Final render
  $('#connection_details_output').html(`
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      ${tokenHTML}
      ${webhookHTML}
      ${wabaCards}
    </div>
  `);

  $('#connection_result_area').show();
}
    };
    window.addEventListener('message', function(event) {
      const validOrigins = ["https://www.facebook.com", "https://web.facebook.com"];
      if (!validOrigins.includes(event.origin)) return;

      let data;
      try {
        data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;
      } catch (err) {
        console.warn('[Embed Signup] Invalid JSON in message', event.data);
        return;
      }

      if (data?.type === 'WA_EMBEDDED_SIGNUP') {
        if (data.event === 'FINISH') {
          $('#launch_embed_signup').text('Connected!');

          $.post(admin_url + 'whatsapp/save_settings',function() {
            alert_float('success', '✅ Signup complete. Redirecting...');
            setTimeout(() => {
              window.location.href = admin_url + 'whatsapp/oauth_redirect';
            }, 1500);
          });

        } else if (data.event === 'CLOSE') {
          $('#launch_embed_signup').prop('disabled', false).text('Start Embedded Signup');
          alert_float('warning', '❗ Signup window was closed.');
        }
      }
    });

    window.handleConnectionMethodUI = function() {
      const method = $('#connection_method').val();

      // 🔄 All fields (to reset)
      const allFields = [
        '#field_business_id', '#field_waba_id', '#field_access_token',
        '#field_config_id', '#field_meta_app_id', '#field_meta_app_secret',
        '#field_webhook_token', '#field_webhook_url',
        '#embed_connect_area', '#embed_app_settings'
      ];

      // 🎯 Fields to show for each mode
      const showFields = {
        manual: [
          '#field_business_id',
          '#field_access_token',
          '#field_meta_app_id',
          '#field_meta_app_secret',
          '#field_webhook_token',
          '#field_webhook_url'
        ],
        embed: [
          '#field_config_id',
          '#field_meta_app_id',
          '#field_meta_app_secret',
          '#embed_connect_area'
        ]
      };

      // 🚫 Hide all first
      allFields.forEach(id => $(id).addClass('hidden'));

      // ✅ Show mode-specific fields
      (showFields[method] || []).forEach(id => $(id).removeClass('hidden'));

      // 🔁 Hide/show Connect Button
      $('#connect_btn').toggle(method === 'manual');

      // ✅ Re-run visibility updater
      if (typeof updateButtonVisibility === 'function') {
        updateButtonVisibility();
      }
    };


    $('#connection_method').on('change', handleConnectionMethodUI);
        $('#whatsapp_settings_form').on('submit', function(e) {
      e.preventDefault();
    
      const $form        = $(this);
      const url          = $form.attr('action');
      const $btn         = $form.find('button[type=submit]');
      const originalHtml = $btn.html();
    
      // 1) spinner
      $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
    
      // 2) temporarily re-enable disabled settings[…] controls
      const $disabled = $form
        .find('[name^="settings["]')
        .filter(':disabled')
        .prop('disabled', false);
    
      // 3) build FormData (captures all inputs/selects/textareas)
      const formData = new FormData($form[0]);
    
      // 4) restore disabled state
      $disabled.prop('disabled', true);
    
      // 5) send via AJAX
      $.ajax({
        url:         url,
        method:      'POST',
        data:        formData,
        processData: false,
        contentType: false
      })
      .done(function() {
        alert_float('success', '✅ All settings saved.');
      })
      .fail(function(xhr) {
        alert_float('danger', '❌ Save failed: ' + (xhr.responseText||'Unknown'));
      })
      .always(function() {
        $btn.prop('disabled', false).html(originalHtml);
      });
    });

    function copyToClipboard(selector) {
      const el = document.querySelector(selector);
      if (!el) return;
      navigator.clipboard.writeText(el.innerText).then(() => {
        alert_float('success', 'Copied to clipboard!');
      });
    }

    function showExpiryCountdown(timestamp) {
      const interval = setInterval(() => {
        const secondsLeft = Math.max(0, Math.floor((timestamp * 1000 - Date.now()) / 1000));
        $('#token_countdown').text(`${secondsLeft}s`);
        if (secondsLeft <= 0) clearInterval(interval);
      }, 1000);
    }

    function updateButtonVisibility(debug = false) {
      const method = $('#connection_method').val();

      // Inject PHP values directly
      const appId = "<?php echo get_option('whatsapp_meta_app_id'); ?>";
      const appSecret = "<?php echo get_option('whatsapp_meta_app_secret'); ?>";

      const hasAppId = appId && appId.length > 5;
      const hasAppSecret = appSecret && appSecret.length > 5;

      const $connectBtn = $('#connect_btn');
      const $registerBtn = $('#btn_register_webhook');
      const $unregisterBtn = $('#btn_unregister_webhook');
      const $webhookButtons = $('.webhook_controls');

      const isConnected = WhatsappCloud?.isConnected === 'yes';
      const canConnect = method === 'manual';
      const canConfigureWebhook = hasAppId && hasAppSecret;

      // --- CONNECT BUTTON ---
      if (canConnect && !isConnected) {
        $connectBtn.removeClass('hidden');
      } else {
        $connectBtn.addClass('hidden');
      }

      // --- WEBHOOK BUTTONS (just show both when connected + creds) ---
      if (isConnected && canConfigureWebhook) {
        $webhookButtons.removeClass('hidden');
        $registerBtn.removeClass('hidden');
        $unregisterBtn.removeClass('hidden');
      } else {
        $webhookButtons.addClass('hidden');
        $registerBtn.addClass('hidden');
        $unregisterBtn.addClass('hidden');
      }

      // Optional debug
      if (debug) {
        console.table({
          method,
          appId: hasAppId ? '✅ present' : '❌ missing',
          appSecret: hasAppSecret ? '✅ present' : '❌ missing',
          isConnected,
          canConnect,
          canConfigureWebhook
        });
      }
    }

    // Run on load
    updateButtonVisibility(true);

    // Re-run on field input changes
    $('#api_key, #field_meta_app_id input, #field_meta_app_secret input').on('input', updateButtonVisibility);
    $('#connection_method').on('change', updateButtonVisibility);

    // Expose globally
    window.connectWhatsappCloudApi = () => WhatsappCloud.connect();
    window.disconnectConnection = () => WhatsappCloud.disconnect();
    window.registerWhatsappWebhook = () => WhatsappCloud.registerWebhook();
    window.unregisterWhatsappWebhook = () => WhatsappCloud.unregisterWebhook();

    // Init on load
    WhatsappCloud.init();
  });
</script>
<script>
  $(document).ready(function() {
    function toggleAIFields() {
      var provider = $('#ai-provider').val();
      // tokens
      $('#openai-key-field').toggle(provider === 'openai');
      $('#gemini-key-field').toggle(provider === 'gemini');
      // training
      $('.ai-training-field').toggle(provider === 'openai' || provider === 'gemini');
    }
    $('#ai-provider').on('changed.bs.select', toggleAIFields);
    toggleAIFields();
  });
</script>