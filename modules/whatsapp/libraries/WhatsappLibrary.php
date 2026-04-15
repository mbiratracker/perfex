<?php

use GuzzleHttp\Client as GuzzleClient;
use WpOrg\Requests\Requests as Whatsapp;

class WhatsappLibrary
{
        protected $ci;
public static $facebookAPI = 'https://graph.facebook.com/v20.0/';
    protected $clientHandler;
    protected $client;
    public static $extensionMap = [
        'image/jpeg'                                                                => 'jpg',
        'image/png'                                                                 => 'png',
        'audio/mp3'                                                                 => 'mp3',
        'video/mp4'                                                                 => 'mp4',
        'audio/aac'                                                                 => 'aac',
        'audio/amr'                                                                 => 'amr',
        'audio/ogg'                                                                 => 'ogg',
        'audio/mp4'                                                                 => 'mp4',
        'text/plain'                                                                => 'txt',
        'application/pdf'                                                           => 'pdf',
        'application/vnd.ms-powerpoint'                                             => 'ppt',
        'application/msword'                                                        => 'doc',
        'application/vnd.ms-excel'                                                  => 'xls',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
        'video/3gp'                                                                 => '3gp',
        'image/webp'                                                                => 'webp',
    ];
    public function __construct()
    {
        $this->client = new GuzzleClient();
        $this->clientHandler = new GuzzleHttp\Client();
    }

    /**
     * Handle OAuth callback, exchange short-lived token for a long-lived token
     * and save the access token.
     * 
     * @param string $code The OAuth code received
     * @return void
     */
    public function handle_oauth_callback($code)
    {
        // Step 1: Exchange code for short-lived access token
        $shortTokenResponse = $this->make_get_request('https://graph.facebook.com/v22.0/oauth/access_token', [
            'client_id' => get_option('whatsapp_meta_app_id'),
            'redirect_uri' => base_url('whatsapp/Webhook/oauth_callback'),
            'client_secret' => get_option('whatsapp_meta_app_secret'),
            'code' => $code,
        ]);
        
        log_message('error', '[OAuth] Short-lived Token Response: ' . json_encode($shortTokenResponse));

        if (empty($shortTokenResponse['access_token'])) {
            log_message('error', '[OAuth Error] Could not retrieve short-lived access token.');
            set_alert('danger', 'Failed to get access token from Meta.');
            redirect(admin_url('whatsapp/connection'));
            return;
        }

        $shortToken = $shortTokenResponse['access_token'];

        // Step 2: Exchange short-lived token for long-lived access token
        $longTokenResponse = $this->make_get_request('https://graph.facebook.com/v22.0/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => get_option('whatsapp_meta_app_id'),
            'client_secret' => get_option('whatsapp_meta_app_secret'),
            'fb_exchange_token' => $shortToken,
        ]);

        log_message('error', '[OAuth] Long-lived Token Response: ' . json_encode($longTokenResponse));

        $accessToken = $longTokenResponse['access_token'] ?? $shortToken;

        // Step 3: Save the access token
        update_option('whatsapp_access_token', $accessToken);
        update_option('whatsapp_connection_status', 'connected');

        log_message('error', '[OAuth] Access token saved successfully.');

        set_alert('success', 'Access token generated and saved.');
        redirect(admin_url('whatsapp/connection'));
    }




  
    private function request_get($url)
    {
        try {
            $res = Whatsapp::get($url);
            return $res->body;
        } catch (Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    private function request_post($url, $data)
    {
        try {
            $res = Whatsapp::post($url, [], $data);
            return $res->body;
        } catch (Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    private function request_delete($url)
    {
        try {
            $res = Whatsapp::delete($url);
            return $res->body;
        } catch (Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }

public function connection_status()
{
    $token       = get_option('whatsapp_access_token');
    $business_id = get_option('whatsapp_business_id');
    $appId       = get_option('whatsapp_meta_app_id');
    $appSecret   = get_option('whatsapp_meta_app_secret');

    $result = [
        'success'     => false,
        'message'     => '',
        'permissions' => null,
        'wab_accounts'=> [],
        'webhook'     => null,
        'status'      => 'disconnected',
    ];

    if (!$token || !$business_id) {
        $result['message'] = 'Missing saved token or business ID.';
        return $result;
    }

    $permissions = json_decode($this->request_get("https://graph.facebook.com/debug_token?input_token={$token}&access_token={$token}"));
    if (empty($permissions->data)) {
        $result['message'] = 'Token is invalid or expired.';
        update_option('whatsapp_connection_status', 'disconnected');
        return $result;
    }

    $business = json_decode($this->request_get("https://graph.facebook.com/v20.0/{$business_id}?fields=owned_whatsapp_business_accounts&access_token={$token}"));
    $owned_accounts = $business->owned_whatsapp_business_accounts->data ?? [];

    if (empty($owned_accounts)) {
        $result['message'] = 'No WhatsApp Business Accounts found.';
        return $result;
    }

    foreach ($owned_accounts as $account) {
        $waba_id = $account->id;
        $waba    = json_decode($this->request_get("https://graph.facebook.com/v20.0/{$waba_id}?access_token={$token}"));
        $phones  = json_decode($this->request_get("https://graph.facebook.com/v20.0/{$waba_id}/phone_numbers?access_token={$token}"));

        $result['wab_accounts'][] = [
            'waba_id' => $waba_id,
            'waba'    => $waba,
            'phones'  => $phones
        ];
    }

    $webhook = json_decode($this->request_get("https://graph.facebook.com/{$appId}/subscriptions?access_token={$appId}|{$appSecret}"));

    update_option('whatsapp_connection_status', 'connected');

    $result['success']     = true;
    $result['message']     = 'Connected successfully!';
    $result['permissions'] = $permissions;
    $result['webhook']     = $webhook;
    $result['status']      = 'connected';

    // ✅ Call sync without passing token
    $this->syncNumbersFromConnectionStatus($result['wab_accounts']);

    return $result;
}
public function syncNumbersFromConnectionStatus($wabAccounts = [])
{
    $CI = &get_instance();
    $tableName = db_prefix() . 'whatsapp_numbers';
    $token = get_option('whatsapp_access_token'); // Token fetched here

    if (empty($wabAccounts) || !$token) {
        log_message('error', '[WhatsappLibrary] Missing WABA accounts or token for sync.');
        return [];
    }

    foreach ($wabAccounts as $waba) {
        $phones = $waba['phones']->data ?? [];

        foreach ($phones as $number) {
            $phoneId     = $number->id;
            $phoneNumber = preg_replace('/[^\d]/', '', $number->display_phone_number ?? '');

            if (empty($waba['waba_id'])) {
                log_message('error', '[WhatsappLibrary] Missing whatsapp_business_account_id for phone ID: ' . $phoneId);
                continue;
            }

            $updateData = [
                'phone_number'                  => $phoneNumber,
                'display_phone_number'          => $phoneNumber,
                'verified_name'                 => $number->verified_name ?? '',
                'code_verification_status'      => $number->code_verification_status ?? '',
                'quality_rating'                => $number->quality_rating ?? '',
                'platform_type'                 => $number->platform_type ?? '',
                'throughput_level'              => $number->throughput->level ?? '',
                'external_id'                   => $phoneId,
                'phone_number_id'               => $phoneId,
                'whatsapp_business_account_id'  => $waba['waba_id'],
            ];

            // ✅ No profileUrl call – removed as requested

            $CI->db->where('phone_number_id', $phoneId);
            $exists = $CI->db->get($tableName)->row();

            if ($exists) {
                $CI->db->where('phone_number_id', $phoneId)->update($tableName, $updateData);
            } else {
                $CI->db->insert($tableName, $updateData);
            }
        }
    }

    // Ensure one default number is set
    $CI->db->where('is_default', 1);
    $default = $CI->db->get($tableName)->row();

    if (!$default) {
        $first = $CI->db->order_by('phone_number_id', 'ASC')->limit(1)->get($tableName)->row();
        if ($first) {
            $CI->db->where('phone_number_id', $first->phone_number_id)->update($tableName, ['is_default' => 1]);
            update_option('whatsapp_default_phone_number_id', $first->phone_number_id);
            update_option('whatsapp_default_phone_number', $first->phone_number);
        }
    }

    return $CI->db->get($tableName)->result_array();
}

    public function register_webhook()
    {
        $appId       = get_option('whatsapp_meta_app_id');
        $appSecret   = get_option('whatsapp_meta_app_secret');
        $verifyToken = get_option('whatsapp_webhook_token');
        $callbackUrl = get_option('whatsapp_webhook_url');

        $url = "https://graph.facebook.com/{$appId}/subscriptions?access_token={$appId}|{$appSecret}";

        $payload = [
            'object'       => 'whatsapp_business_account',
            'fields'       => implode(',', [
                'message_template_components_update',
                'message_template_quality_update',
                'message_template_status_update',
                'messages',
                'campaign_status_update',
                'business_status_update',
                'business_capability_update',
                'account_update',
                'account_review_update',
                'account_alerts',
            ]),
            'callback_url' => $callbackUrl,
            'verify_token' => $verifyToken,
        ];

        $response = json_decode($this->request_post($url, $payload), true);

        if (isset($response['error'])) {
            return [
                'success' => false,
                'message' => 'Webhook registration failed: ' . $response['error']['message'],
                'meta'    => $response
            ];
        } else {
            update_option('whatsapp_webhook_configure', 1);
            return [
                'success' => true,
                'message' => '✅ Webhook registered successfully!',
                'meta'    => $response
            ];
        }
    }

    public function unregister_webhook()
    {
        $appId     = get_option('whatsapp_meta_app_id');
        $appSecret = get_option('whatsapp_meta_app_secret');

        $url = "https://graph.facebook.com/{$appId}/subscriptions?access_token={$appId}|{$appSecret}";
        $response = json_decode($this->request_delete($url), true);

        if (isset($response['error'])) {
            throw new Exception($response['error']['message']);
        }

        update_option('wb_webhook_configure', 0);

        return [
            'success' => true,
            'message' => '✅ Webhook unregistered successfully!',
            'meta'    => $response
        ];
    }

    public function disconnect()
    {
        delete_option('whatsapp_access_token');
        delete_option('whatsapp_business_id');
        delete_option('whatsapp_business_account_id');
        delete_option('whatsapp_connection_status');

        return [
            'success' => true,
            'message' => 'Disconnected and cleared local settings.'
        ];
    }

  public function readmessage($fromNumber, $messageId)
    {
        $accessToken = get_option('whatsapp_access_token');
    
        if (empty($accessToken)) {
            log_message('error', 'Access token is not set');
            return ['error' => 'Access token is not set'];
        }
    
        $apiUrl = 'https://graph.facebook.com/v20.0/' . $fromNumber . '/messages';
    
        $data = [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId,
        ];
    
        $timeout = 10; // Set the desired timeout value in seconds
    
        try {
            $response = $this->clientHandler->request('POST', $apiUrl, [
                'json' => $data,
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'timeout' => $timeout,
            ]);
    
            // Handle the response
            $responseData = json_decode($response->getBody(), true);
            log_message('info', 'Message marked as read successfully. Response: ' . json_encode($responseData));
    
            return ['success' => true, 'response' => $responseData];
    
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = (string) $response->getBody();
    
            log_message('error', 'Client error: ' . $e->getMessage());
            log_message('error', 'Response body: ' . $responseBodyAsString);
    
            return json_decode($responseBodyAsString, true);
        } catch (Exception $e) {
            log_message('error', 'Error: ' . $e->getMessage());
            return ['error' => 'Failed to mark message as read: ' . $e->getMessage()];
        }
    }
    public function updateProfile(array $profileData, $phoneNumberId, $accessToken)
    {
        $apiVersion = 'v20.0';
        $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/whatsapp_business_profile";

        $data = [
            "messaging_product" => $profileData['messaging_product'],
            "about" => $profileData['about'],
            "address" => $profileData['address'],
            "vertical" => $profileData['vertical'],
            "email" => $profileData['email'],
            "websites" => $profileData['websites'],
        ];

        $timeout = 10; // Set the desired timeout value in seconds

        try {
            $response = $this->clientHandler->request('POST', $url, [
                'json' => $data,
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'timeout' => $timeout,
            ]);

            $responseData = json_decode($response->getBody(), true);
            log_message('info', 'POST Request URL: ' . $url);
            log_message('info', 'POST Request Data: ' . json_encode($data));
            log_message('info', 'POST Request Headers: ' . json_encode([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ]));
            log_message('info', 'POST Response Data: ' . json_encode($responseData));

            return $responseData;
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = (string) $response->getBody();

            log_message('error', 'Client error: ' . $e->getMessage());
            log_message('error', 'Response body: ' . $responseBodyAsString);

            return json_decode($responseBodyAsString, true);
        } catch (Exception $e) {
            log_message('error', 'Error: ' . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }
    public function uploadProfilePicture($filePath, $accessToken)
    {
        $apiVersion = 'v20.0';
        $fileContent = file_get_contents($filePath);

        // Step 1: Upload the image
        $uploadUrl = "https://graph.facebook.com/{$apiVersion}/me/photos";
        $response = $this->client->request('POST', $uploadUrl, [
            'multipart' => [
                [
                    'name'     => 'source',
                    'contents' => $fileContent,
                    'filename' => basename($filePath)
                ],
                [
                    'name'     => 'access_token',
                    'contents' => $accessToken
                ]
            ],
        ]);

        $responseData = json_decode($response->getBody(), true);
        $photoId = $responseData['id'] ?? null;

        log_message('info', 'Uploaded Photo ID: ' . $photoId);

        return $photoId;
    }



public function loadTemplatesFromWhatsApp($accountId)
{
    $accessToken = get_option('whatsapp_access_token');
    $CI          = &get_instance();

    if (!$accessToken || !$accountId) {
        return [
            'success' => false,
            'type'    => 'danger',
            'message' => 'Missing WhatsApp API credentials.',
        ];
    }

    // Fetch templates from Meta API
    $url      = self::$facebookAPI 
              . $accountId 
              . '/?fields=id,name,message_templates,phone_numbers&access_token=' 
              . $accessToken;
    $request  = Whatsapp::get($url);
    $response = json_decode($request->body);

    if (isset($response->error)) {
        return [
            'success' => false,
            'type'    => 'danger',
            'message' => $response->error->message,
        ];
    }

    if (empty($response->message_templates->data)) {
        return [
            'success' => false,
            'type'    => 'warning',
            'message' => 'No templates returned from WhatsApp API.',
        ];
    }

    // Filter out system templates whose names start with "sample_"
    $filtered = array_filter(
        $response->message_templates->data,
        fn($t) => strpos($t->name, 'sample_') !== 0
    );

$records = array_map(function ($tpl) use ($accountId) {
        $components   = $tpl->components ?? [];
        $parameterFormat = strtoupper($tpl->parameter_format ?? '');
        $componentMap = [];

        // Build a map: $componentMap['HEADER'], ['BODY'], ['FOOTER'], ['BUTTONS'], etc.
        foreach ($components as $comp) {
            $type = strtoupper($comp->type ?? '');
            if ($type !== '') {
                $componentMap[$type] = $comp;
            }
        }

        // ==== 1. Detect and download HEADER media, if any ====
        $hasMedia  = 0;
        $headerUrl = null;
        if (
            isset($componentMap['HEADER']->format) &&
            in_array(strtoupper($componentMap['HEADER']->format), ['IMAGE', 'VIDEO', 'DOCUMENT'])
        ) {
            $mediaList = $componentMap['HEADER']->example->header_handle ?? null;
            if (is_array($mediaList) && isset($mediaList[0])) {
                $remoteMediaUrl = $mediaList[0];
                $extension      = pathinfo(
                    parse_url($remoteMediaUrl, PHP_URL_PATH),
                    PATHINFO_EXTENSION
                );
                $filename  = $tpl->id . '.' . $extension;
                $savedPath = save_whatsapp_file(
                    $remoteMediaUrl,
                    'template',
                    null,
                    'filename',
                    $filename
                );
                if ($savedPath) {
                    $headerUrl = $savedPath;
                    $hasMedia  = 1;
                }
            }
        }

        // ==== 2. Count parameters in each component (HEADER, BODY, FOOTER) ====
        $headerParamsCount = 0;
        if (isset($componentMap['HEADER'])) {
            if ($parameterFormat === 'NAMED') {
                // NAMED: look for header_text_named_params array
                $headerParamsCount = is_array(
                    $componentMap['HEADER']->example->header_text_named_params ?? null
                )
                    ? count($componentMap['HEADER']->example->header_text_named_params)
                    : 0;
            } elseif ($parameterFormat === 'POSITIONAL') {
                // POSITIONAL header parameters are rare but could appear similarly to "body_text"
                // If an example->header_text is an array of arrays, count items in the first row
                if (isset($componentMap['HEADER']->example->header_text)
                    && is_array($componentMap['HEADER']->example->header_text)
                    && is_array($componentMap['HEADER']->example->header_text[0] ?? null)
                ) {
                    $headerParamsCount = count($componentMap['HEADER']->example->header_text[0]);
                }
            }
        }

        $bodyParamsCount = 0;
        if (isset($componentMap['BODY'])) {
            if ($parameterFormat === 'NAMED') {
                $bodyParamsCount = is_array(
                    $componentMap['BODY']->example->body_text_named_params ?? null
                )
                    ? count($componentMap['BODY']->example->body_text_named_params)
                    : 0;
            } elseif ($parameterFormat === 'POSITIONAL') {
                // example->body_text is usually an array of arrays of strings
                if (isset($componentMap['BODY']->example->body_text)
                    && is_array($componentMap['BODY']->example->body_text)
                    && is_array($componentMap['BODY']->example->body_text[0] ?? null)
                ) {
                    $bodyParamsCount = count($componentMap['BODY']->example->body_text[0]);
                }
            }
        }

        $footerParamsCount = 0;
        if (isset($componentMap['FOOTER'])) {
            if ($parameterFormat === 'NAMED') {
                $footerParamsCount = is_array(
                    $componentMap['FOOTER']->example->footer_text_named_params ?? null
                )
                    ? count($componentMap['FOOTER']->example->footer_text_named_params)
                    : 0;
            } elseif ($parameterFormat === 'POSITIONAL') {
                if (isset($componentMap['FOOTER']->example->footer_text)
                    && is_array($componentMap['FOOTER']->example->footer_text)
                    && is_array($componentMap['FOOTER']->example->footer_text[0] ?? null)
                ) {
                    $footerParamsCount = count($componentMap['FOOTER']->example->footer_text[0]);
                }
            }
        }

        return [
            // ==== Top-level template fields ====
                       'whatsapp_business_account_id'  => $accountId,
             'template_id'                 => $tpl->id,
            'template_name'               => $tpl->name,
            'language'                    => $tpl->language,
            'status'                      => $tpl->status ?? 'Unknown',
            'category'                    => $tpl->category ?? 'Unknown',
            'sub_category'                => $tpl->sub_category ?? null,
            'parameter_format'            => $tpl->parameter_format ?? '',
            'template_description'        => $tpl->description ?? null,
            'has_media'                   => $hasMedia,
            'is_legacy_template'          => $tpl->is_legacy_template ?? 0,
            'message_send_ttl_seconds'    => $tpl->message_send_ttl_seconds ?? null,
            'previous_category'           => $tpl->previous_category ?? null,
            'add_security_recommendation' => $tpl->add_security_recommendation ?? 0,
            'example_values'              => isset($tpl->example_values)
                                               ? json_encode($tpl->example_values)
                                               : null,

            // ==== HEADER component fields ====
            'header_data_format'  => $componentMap['HEADER']->format ?? null,
            'header_data_text'    => $componentMap['HEADER']->text ?? null,
            'header_params_count' => $headerParamsCount,
            'header_example'      => $headerUrl,

            // ==== BODY component fields ====
            'body_data'           => $componentMap['BODY']->text ?? null,
            'body_params_count'   => $bodyParamsCount,
            'body_example'        => isset($componentMap['BODY']->example)
                                        ? (is_object($componentMap['BODY']->example)
                                            ? json_encode($componentMap['BODY']->example)
                                            : null)
                                        : null,

            // ==== FOOTER component fields ====
            'footer_data'         => $componentMap['FOOTER']->text ?? null,
            'footer_params_count' => $footerParamsCount,
            'footer_example'      => isset($componentMap['FOOTER']->example)
                                        ? (is_object($componentMap['FOOTER']->example)
                                            ? json_encode($componentMap['FOOTER']->example)
                                            : null)
                                        : null,

            // ==== BUTTONS component fields ====
            'buttons_data'   => isset($componentMap['BUTTONS'])
                                  ? json_encode($componentMap['BUTTONS'])
                                  : null,
            'buttons_example'=> isset($componentMap['BUTTONS']->example)
                                  ? json_encode($componentMap['BUTTONS']->example)
                                  : null,
        ];
    }, $filtered);

// ==== Sync with the database ====
$tableName = db_prefix() . 'whatsapp_interaction_templates';

// Step 1: Build existing map for current account only
$existingMap = [];
$existing = $CI->db
    ->where('whatsapp_business_account_id', $accountId)
    ->get($tableName)
    ->result_array();

foreach ($existing as $row) {
    $key = $row['template_id'] . '::' . $row['whatsapp_business_account_id'];
    $existingMap[$key] = $row['id'];
}

// Step 2: Prepare incoming keys for this account
$newKeys = [];

foreach ($records as $record) {
    $key = $record['template_id'] . '::' . $record['whatsapp_business_account_id'];
    $newKeys[] = $key;

    if (isset($existingMap[$key])) {
        // Update
        $CI->db->where('template_id', $record['template_id']);
        $CI->db->where('whatsapp_business_account_id', $record['whatsapp_business_account_id']);
        $CI->db->update($tableName, $record);
    } else {
        // Insert
        $CI->db->insert($tableName, $record);
    }
}

// Step 3: Only delete templates from this specific account
foreach ($existingMap as $key => $id) {
    if (!in_array($key, $newKeys)) {
        list($templateId, $accountKeyId) = explode('::', $key);
        if ($accountKeyId == $accountId) {
            $CI->db->where('template_id', $templateId);
            $CI->db->where('whatsapp_business_account_id', $accountKeyId);
            $CI->db->delete($tableName);
        }
    }
}


    return [
        'success' => true,
        'type'    => 'success',
        'message' => 'Templates synced successfully. Added/updated: ',
    ];
}





public function retrieveUrl($media_id, $accessToken, $interactionId)
{
    $CI =& get_instance();
    $CI->load->helper('file');

    // Get upload path
    $uploadFolder = get_upload_path_by_type('interactions/'.$interactionId);
            log_message('error', 'Failed to create interaction upload folder: ' . $interactionId);

    // ✅ Ensure directory exists
    if (!is_dir($uploadFolder)) {
        if (!@mkdir($uploadFolder, 0755, true) && !is_dir($uploadFolder)) {
            log_message('error', 'Failed to create interaction upload folder: ' . $uploadFolder);
            return null;
        }
    }

    $client = new \GuzzleHttp\Client();
    $url    = self::$facebookAPI . $media_id;

    try {
        $response = $client->get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);
    } catch (Exception $e) {
        log_message('error', 'Failed to get media metadata: ' . $e->getMessage());
        return null;
    }

    if ($response->getStatusCode() !== 200) {
        return null;
    }

    $responseData = json_decode($response->getBody(), true);
    if (!isset($responseData['url'])) {
        return null;
    }

    $mediaUrl = $responseData['url'];

    try {
        $mediaData = $client->get($mediaUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);
    } catch (Exception $e) {
        log_message('error', 'Failed to download media content: ' . $e->getMessage());
        return null;
    }

    if ($mediaData->getStatusCode() !== 200) {
        return null;
    }

    $imageContent = $mediaData->getBody()->getContents();
    $contentType  = $mediaData->getHeader('Content-Type')[0] ?? 'application/octet-stream';

    $extensionMap = self::$extensionMap ?? [];
    $extension    = $extensionMap[$contentType] ?? 'bin';

    $filename     = 'media_' . $media_id . '.' . $extension;
    $storagePath  = rtrim($uploadFolder, '/') . '/' . $filename;

    if (!write_file($storagePath, $imageContent)) {
        log_message('error', 'Failed to write file: ' . $storagePath);
        return null;
    }

    return $filename;
}


    /**
     * Handle attachment upload and save the file
     *
     * @param array $attachment Attachment file information
     * @return string|bool Filename of the saved attachment or false on failure
     */
public function handle_attachment_upload($attachment, $interaction_id)
{
    $uploadFolder = WHATSAPP_MODULE_UPLOAD_FOLDER . '/interactions/' . $interaction_id;

    // Ensure target folder exists
    if (!is_dir($uploadFolder)) {
        mkdir($uploadFolder, 0755, true);
        file_put_contents($uploadFolder . '/index.html', '<!-- Protected -->');
    }

    $contentType  = $attachment['type'];
    $extensionMap = self::$extensionMap;
    $extension = $extensionMap[$contentType] ?? pathinfo($attachment['name'], PATHINFO_EXTENSION) ?? 'bin';

    $filename = uniqid('attachment_') . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $extension;

    $destination = $uploadFolder . '/' . $filename;

    if (move_uploaded_file($attachment['tmp_name'], $destination)) {
        // Return the relative path to reconstruct full URL elsewhere
        return $filename;
    }

    return false;
}
 public function prepare_template_message_data($interaction, $data)
{
    $data = whatsappReplaceAllParamsInData($data);
    log_message('error', "Final data after processing parameters: " . print_r($data, true));

    $rel_type = $data['rel_type'] ?? '';
    $parameter_format = strtoupper($data['parameter_format'] ?? 'POSITIONAL');

    $components = [];

    // Helper to get fallback value
    $get_example_value = function($example, $index = 0) {
        if (is_string($example)) {
            $example = json_decode($example, true);
        }
        return $example[0][$index] ?? 'Example';
    };

    // 1️⃣ HEADER (TEXT or MEDIA)
    $header_data_format = $data['header_data_format'] ?? null;

if ($header_data_format === 'TEXT') {
    $header_params = whatsappParseText($rel_type, 'header', $data, 'data');
    $header_component = ['type' => 'header', 'parameters' => []];

    // 🛑 Only add if param count > 0
    if (!empty($data['header_params_count']) && $data['header_params_count'] > 0) {
        $header_value = $header_params[0]['text'] ?? $get_example_value($data['header_example']);

        if ($parameter_format === 'NAMED') {
            $header_component['parameters'][] = [
                'type' => 'text',
                'parameter_name' => $header_params[0]['parameter_name'] ?? 'param1',
                'text' => $header_value,
            ];
        } else {
            $header_component['parameters'][] = [
                'type' => 'text',
                'text' => $header_value,
            ];
        }

        $components[] = $header_component;
    }
}

    if (in_array($header_data_format, ['VIDEO', 'DOCUMENT', 'IMAGE'])) {
        $upload_path = 'template';
        if (!empty($data['filename'])) {
            $upload_path = isset($data['bot_type']) ? 'bot' : 'campaign';
        }

        $filename = $data['filename'] ?? $data['header_example'] ?? '';
        $file_url = base_url(get_upload_path_by_type($upload_path) . $filename);

        if ($filename && $file_url) {
            $components[] = [
                'type' => 'header',
                'parameters' => [
                    [
                        'type' => strtolower($header_data_format),
                        strtolower($header_data_format) => ['link' => $file_url],
                    ]
                ]
            ];
        }
    }

    // 2️⃣ BODY
    $body_params = whatsappParseText($rel_type, 'body', $data, 'data');
    log_message('error', "Body params: " . print_r($body_params, true));

    $body_parameters = [];
    $expected_body_count = (int) $data['body_params_count'];

    for ($i = 0; $i < $expected_body_count; $i++) {
        $param = $body_params[$i] ?? null;
        $value = $param['text'] ?? $get_example_value($data['body_example'], $i);
        $param_name = $param['parameter_name'] ?? 'param' . ($i + 1);

        $body_parameters[] = $parameter_format === 'NAMED'
            ? ['type' => 'text', 'parameter_name' => $param_name, 'text' => $value]
            : ['type' => 'text', 'text' => $value];
    }

    if (!empty($body_parameters)) {
        $components[] = ['type' => 'body', 'parameters' => $body_parameters];
        log_message('error', "Added body component: " . print_r($body_parameters, true));
    }

    // 3️⃣ FOOTER
    $footer_params = whatsappParseText($rel_type, 'footer', $data, 'data');
    log_message('error', "Footer params: " . print_r($footer_params, true));

    $footer_parameters = [];
    $expected_footer_count = (int) $data['footer_params_count'];

    for ($i = 0; $i < $expected_footer_count; $i++) {
        $param = $footer_params[$i] ?? null;
        $value = $param['text'] ?? $get_example_value($data['footer_example'], $i);
        $param_name = $param['parameter_name'] ?? 'param' . ($i + 1);

        $footer_parameters[] = $parameter_format === 'NAMED'
            ? ['type' => 'text', 'parameter_name' => $param_name, 'text' => $value]
            : ['type' => 'text', 'text' => $value];
    }

    if (!empty($footer_parameters)) {
        $components[] = ['type' => 'footer', 'parameters' => $footer_parameters];
        log_message('error', "Added footer component: " . print_r($footer_parameters, true));
    }

    $raw_buttons = $data['buttons_data'] ?? $data['buttons_example'] ?? null;
    log_message('error', 'Buttons Data: ' . print_r($raw_buttons, true));
    
    if (!empty($raw_buttons)) {
        $buttons_data = is_string($raw_buttons) ? json_decode($raw_buttons, true) : $raw_buttons;
    
        $allowed_sub_types = ['CATALOG', 'COPY_CODE', 'FLOW', 'MPM', 'ORDER_DETAILS', 'QUICK_REPLY', 'REMINDER', 'VOICE_CALL'];
    
        if (!empty($buttons_data['buttons']) && is_array($buttons_data['buttons'])) {
            foreach ($buttons_data['buttons'] as $index => $button) {
                $original_type = strtoupper($button['type'] ?? '');
                $mapped_type   = ($original_type === 'PHONE_NUMBER') ? 'VOICE_CALL' : $original_type;
    
                // ❌ Skip URL buttons completely
                if ($mapped_type === 'URL') {
                    log_message('error', "⏩ Skipping URL button completely (index {$index})");
                    continue;
                }
    
                // ✅ Proceed if allowed
                if (in_array($mapped_type, $allowed_sub_types)) {
                    $component = [
                        'type'     => 'button',
                        'sub_type' => $mapped_type,
                        'index'    => $index,
                    ];
    
                    // COPY_CODE
                    if ($mapped_type === 'COPY_CODE') {
                        $coupon = $button['example'][0] ?? null;
                        if ($coupon) {
                            $component['parameters'][] = [
                                'type'        => 'coupon_code',
                                'coupon_code' => $coupon,
                            ];
                        } else {
                            log_message('error', "❌ COPY_CODE missing coupon_code at index {$index}");
                            continue;
                        }
                    }
    
                    // VOICE_CALL
                    elseif ($mapped_type === 'VOICE_CALL') {
                        $component['parameters'][] = [
                            'type' => 'text',
                            'text' => $button['text'] ?? '',
                        ];
                    }
    
                    // Default TEXT parameter for others
                    else {
                        $component['parameters'][] = [
                            'type' => 'text',
                            'text' => $button['text'] ?? '',
                        ];
                    }
    
                    $components[] = $component;
                    log_message('error', "✅ Added button (index {$index}): " . print_r($component, true));
                } else {
                    log_message('error', "❌ Invalid or unsupported button sub_type: {$original_type} at index {$index}. Skipping.");
                }
            }
        }
    }

    // ✅ Final payload
    $message_data = [
        'messaging_product' => 'whatsapp',
        'recipient_type'    => 'individual',
        'to'                => $interaction,
        'type'              => 'template',
        'template'          => [
            'name'     => $data['template_name'] ?? '',
            'language' => [
                'code'   => $data['language'] ?? 'en_US',
                'policy' => 'deterministic',
            ],
            'components' => $components
        ]
    ];

    log_message('error', "Final message_data: " . print_r($message_data, true));

    return $message_data;
}


    function send_message($from_phone_number_id, $to, $message_data, $log_data = null)
    {
        // Remove '+' signs and spaces from recipient number
        $to = str_replace(['+', ' '], '', $to);
        $access_token = get_option('whatsapp_access_token');
        
            // Validate recipient and message data
            if (empty($to) || empty($message_data) || empty($from_phone_number_id) || empty($access_token)) {
                log_message('error', 'Invalid recipient, message data, phone number ID, or access token');
                return [
                    'error'       => 'Invalid recipient, message data, phone number ID, or access token',
                    'log_data'    => $log_data,
                    'receiver_no' => $to,
                    'sender_no'   => $from_phone_number_id,
                    'wa_no_id'    => $from_phone_number_id,
                    'wa_no'       => get_whatsapp_number_data($from_phone_number_id)['phone_number'] ?? '',
                ];
            }
        
            // Define API endpoint
            $api_url = 'https://graph.facebook.com/v20.0/' . $from_phone_number_id . '/messages';
        
            // Check if the type key exists in message data
            if (!isset($message_data['type'])) {
                log_message('error', 'Message type is not set');
                return [
                    'error'       => 'Message type is not set',
                    'log_data'    => $log_data,
                    'receiver_no' => $to,
                    'sender_no'   => $from_phone_number_id,
                    'wa_no_id'    => $from_phone_number_id,
                    'wa_no'       => get_whatsapp_number_data($from_phone_number_id)['phone_number'] ?? '',
                ];
            }
        
            // Prepare request data
            $data = [
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $to,
                'type'              => $message_data['type']
            ];
        
            // Add specific message data based on message type
            switch ($message_data['type']) {
                case 'text':
                    $data['text'] = $message_data['text'];
                    break;
                case 'reaction':
                    $data['reaction'] = $message_data['reaction'];
                    break;
                case 'audio':
                    $data['audio'] = $message_data['audio'];
                    break;
                case 'image':
                    $data['image'] = $message_data['image'];
                    break;
                case 'video':
                    $data['video'] = $message_data['video'];
                    break;
                case 'document':
                    $data['document'] = $message_data['document'];
                    break;
                case 'location':
                    $data['location'] = $message_data['location'];
                    break;
                case 'contacts':
                    $data['contacts'] = $message_data['contacts'];
                    break;
                case 'interactive':
                    $data['interactive'] = $message_data['interactive'];
                    break;
                case 'template':
                    $data['template'] = [
                        'name'     => $message_data['template']['name'],
                        'language' => [
                            'code'   => $message_data['template']['language']['code'],
                            'policy' => $message_data['template']['language']['policy'] ?? 'deterministic',
                        ],
                        'components' => $message_data['template']['components'] ?? []
                    ];
                    break;
                default:
                    log_message('error', 'Invalid message type');
                    return [
                        'error'       => 'Invalid message type',
                        'log_data'    => $log_data,
                        'receiver_no' => $to,
                        'sender_no'   => $from_phone_number_id,
                        'wa_no_id'    => $from_phone_number_id,
                        'wa_no'       => get_whatsapp_number_data($from_phone_number_id)['phone_number'] ?? '',
                    ];
            }
        
            // Add context if it exists in message data
            if (isset($message_data['context'])) {
                $data['context'] = $message_data['context'];
        }
        
            // Log the data being sent
            log_message('error', 'Request data: ' . json_encode($data));
            $log_data['category_params'] = json_encode($data);
            $log_data['phone_number_id']   = $from_phone_number_id;
            $log_data['business_account_id'] = get_whatsapp_number_data($from_phone_number_id)['whatsapp_business_account_id'];
            $log_data['access_token'] = $access_token;
        
            // Initialize cURL session
            $ch = curl_init();
        
            // Set cURL options
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json',
            ]);
        
            // Execute cURL request
            $response = curl_exec($ch);
        
            // Log raw response
            log_message('error', 'Raw response: ' . $response);
        
            // Check for cURL errors
            if (curl_errno($ch)) {
                $error_message = curl_error($ch);
                log_message('error', 'Failed to send message: ' . $error_message);
                curl_close($ch);
                $log_data['response_data'] = json_encode(['error' => $error_message]);
                return [
                    'error'       => 'Failed to send message: ' . $error_message,
                    'log_data'    => $log_data,
                    'receiver_no' => $to,
                    'wa_no_id'    => $from_phone_number_id,
                    'wa_no'       => get_whatsapp_number_data($from_phone_number_id)['phone_number'] ?? '',
                ];
            }
        
            // Get response code
            $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $log_data['response_code'] = $response_code;
        
            // Close cURL session
            curl_close($ch);
        
            // Process the response
            $response_data = json_decode($response, true);
        
            // Add response and raw data to log
            $log_data['response_data'] = json_encode($response_data);
            $log_data['raw_data'] = json_encode($data);
        
            // Retrieve sender's WhatsApp number
            $sender_number = get_whatsapp_number_data($from_phone_number_id)['phone_number'] ?? '';
        
            // Check if the response data contains the message ID
            if (isset($response_data['messages'][0]['id'])) {
                // Message sent successfully
                $messageId = $response_data['messages'][0]['id'];
                return [
                    'success'      => true,
                    'id'           => $messageId,
                    'log_data'     => $log_data,
                    'response_data'=> $response_data,
                    'wa_no_id'     => $from_phone_number_id,
                    'wa_no'        => $sender_number,
                    'receiver_no'  => $to,
                    'ref_message_id' => $message_data['context']['message_id'] ?? null,

                ];
            } else {
                // Failed to send message
                log_message('error', 'Failed to send message. Response: ' . json_encode($response_data));
                return [
                    'success'      => false,
                    'error'        => 'Failed to send message',
                    'details'      => $response_data,
                    'log_data'     => $log_data,
                    'response_data'=> $response_data,
                    'wa_no_id'     => $from_phone_number_id,
                    'wa_no'        => $sender_number,
                    'receiver_no'  => $to,
                    'ref_message_id' => $message_data['context']['message_id'] ?? null,
                ];
            }
        }

}
