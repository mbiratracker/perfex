<?php defined('BASEPATH') || exit('No direct script access allowed'); ?>

<?php
use WpOrg\Requests\Requests as WhatsappRequests;
use Pusher\Pusher; // Ensure you have installed the Pusher PHP library via Composer

class Webhook extends ClientsController
{
    public $is_first_time = false;
    private $interaction_menu_state_table = 'interaction_menu_state'; // Table for menu state tracking

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['whatsapp_interaction_model', 'bots_model']);
             $this->load->library('WhatsappLibrary');
            $this->WhatsappLibrary = new WhatsappLibrary();
           $this->load->library('BotHandler');
            $this->BotHandler = new BotHandler();
    }

    /* ================= Pusher Event Trigger ================= */

    /**
     * Triggers a Pusher event on the 'whatsapp-channel'.
     *
     * @param string $event The event name.
     * @param array  $data  The data payload to send.
     */
        public function oauth_callback()
        {
            $code = $this->input->get('code');
            $state = $this->input->get('state');
            $storedState = $this->session->userdata('whatsapp_oauth_state');
            $this->session->unset_userdata('whatsapp_oauth_state');
            
            log_message('error', '[OAuth] Code received: ' . $code);
        
            // Load the WhatsappLibrary
            $this->load->library('WhatsappLibrary');
        
            // Use WhatsappLibrary to handle the OAuth callback and save the access token
            $this->WhatsappLibrary->handle_oauth_callback($code);
    }



    private function triggerPusherEvent($event, $data)
    {
        $options = [
            'cluster' => get_option('pusher_cluster'),
            'useTLS'  => true,
        ];
        $pusher = new Pusher(
            get_option('pusher_app_key'),
            get_option('pusher_app_secret'),
            get_option('pusher_app_id'),
            $options
        );
        $pusher->trigger('whatsapp-channel', $event, $data);
    }


    /* ================= Campaign and Verification Methods ================= */

    public function send_campaign()
    {
        $CI = get_instance();
        $CI->load->model(WHATSAPP_MODULE . '/whatsapp_interaction_model');

        // Send campaign regardless of the template update schedule
        $result = $CI->whatsapp_interaction_model->send_campaign();

        // Current timestamp for both stamps
        $currentTime = date('Y-m-d H:i:s');
        $currentTimestamp = strtotime($currentTime);

        // Update the campaign timestamp every time the campaign is sent
        update_option('whatsapp_campaign_cronjob_run_at', $currentTime);

        // Get the last template update timestamp
        $lastTemplatesRunAt = get_option('whatsapp_interaction_templates_cronjob_run_at');
        $lastTemplatesTimestamp = $lastTemplatesRunAt ? strtotime($lastTemplatesRunAt) : 0;

        // Check if an hour (3600 seconds) has passed since the last template update
        if (($currentTimestamp - $lastTemplatesTimestamp) >= 3600) {
    // Load templates only once an hour
            $CI->whatsapp_interaction_model->load_templates();
            update_option('whatsapp_interaction_templates_cronjob_run_at', $currentTime);
        }

        echo json_encode([
            'success' => (bool)$result,
            'message' => $result ? 'Campaign sent successfully.' : 'Failed to send campaign.'
        ]);
    }

    /**
     * Main method: The webhook endpoint that receives WhatsApp callbacks.
     */
public function getdata()
{

    if ($this->isVerificationRequest()) {
        $this->handleVerificationRequest();
    } else {
        // if you need the decoded data:
        $this->processWebhookData();
    }
}


    private function isVerificationRequest(): bool
    {
        return isset($_GET['hub_mode'], $_GET['hub_challenge'], $_GET['hub_verify_token']);
    }

    /**
     * Handle the GET-based Facebook/Meta verification request.
     */
    private function handleVerificationRequest()
    {
   if ($_GET['hub_verify_token'] === get_option('whatsapp_webhook_token')) {
            echo $_GET['hub_challenge'];
            http_response_code(200);
        } else {
            http_response_code(403);
        }
        exit();
    }

    /* ================= Webhook Processing Methods ================= */

    /**
     * The main body for handling POSTed JSON from the WhatsApp webhook.
     */
    private function processWebhookData()
    {
        $feedData = file_get_contents('php://input');
        if (!$feedData) {
            http_response_code(400);
            exit();
        }
        $payload = json_decode($feedData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            exit();
        }
        log_message('error', 'Payload received: ' . print_r($feedData, true));

        // Optionally forward to another webhook
        $this->forwardWebhookData($payload);

        // Handle the main data
        $this->handleData($payload);

        // If there are additional changes in the payload
        collect($payload['entry'] ?? [])
            ->pluck('changes')
            ->flatten(1)
            ->each(function ($change) {
                if (!empty($change['field']) && method_exists($this, $change['field'])) {
                    $this->{$change['field']}($change['value']);
                }
            });

        http_response_code(200);
        exit();
    }

    /**
     * Optionally forward the received webhook payload to another URL (if enabled).
     */
    private function forwardWebhookData(array $payload)
    {
        if (get_option('enable_webhooks') !== '1') {
            return;
        }
        $webhookUrl = get_option('webhooks_url');
        if (!filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
            log_message('error', 'Invalid webhook URL');
            return;
        }
        $maxRetries = 3;
        $timeout = 10;
        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            try {
                $response = WhatsappRequests::request(
                    $webhookUrl,
                    [],
                    json_encode($payload),
                    'POST',
                    ['timeout' => $timeout]
                );
                update_option('whatsapp_webhook_code', $response->status_code);
                update_option('whatsapp_webhook_data', htmlentities($response->body));

                if ($response->status_code >= 200 && $response->status_code < 300) {
                    break;
                }
            } catch (Exception $e) {
                update_option('whatsapp_webhook_code', 'EXCEPTION');
                update_option('whatsapp_webhook_data', $e->getMessage());
                log_message('error', 'Webhook forwarding failed: ' . $e->getMessage());
            }
            sleep(2);
        }
    }

    /**
     * The main logic for parsing the relevant changes from the payload.
     */
    private function handleData($payload)
    {
        if (empty($payload['entry']) || !is_array($payload['entry'])) {
            $this->sendErrorResponse('Invalid payload structure', $payload);
            return;
        }
        $entry = array_shift($payload['entry']);
        if (empty($entry['changes']) || !is_array($entry['changes'])) {
            $this->sendErrorResponse('Invalid changes structure', $payload);
            return;
        }
        $changes = array_shift($entry['changes']);
        $value   = $changes['value'] ?? null;

        if (empty($value)) {
            $this->sendErrorResponse('Invalid value structure', $payload);
            return;
        }

        if ($changes['field'] === 'business_capability_update') {
            update_option('max_daily_conversation_per_phone', $value['max_daily_conversation_per_phone']);
            update_option('max_phone_numbers_per_business', $value['max_phone_numbers_per_business']);
        } elseif (isset($value['messages']) && is_array($value['messages'])) {
            $this->processMessages($value);
        } elseif (isset($value['statuses']) && is_array($value['statuses'])) {
            $this->processStatuses($value);
        } else {
            $this->sendErrorResponse('Invalid payload structure', $payload);
        }
    }

    /**
     * Process "statuses" changes from the webhook.
     */
    private function processStatuses($value)
    {
        if (!empty($value['statuses'])) {
            $statusEntry = array_shift($value['statuses']);
            $id          = $statusEntry['id'];
            $status      = $statusEntry['status'];
            $status_message = '';

            if (isset($statusEntry['errors']) && !empty($statusEntry['errors'])) {
                foreach ($statusEntry['errors'] as $error) {
                    $status_message .= "Error Code: {$error['code']}<br>"
                                     . "Title: {$error['title']}<br>"
                                     . "Message: {$error['message']}<br>"
                                     . "Details: {$error['error_data']['details']}<br>";
                }
            }
            // Update the DB record
            $this->whatsapp_interaction_model->update_message_status($id, $status, $status_message);

            // Trigger a Pusher event for status update
            $this->triggerPusherEvent('status_update', [
                'id' => $id,
                'status' => $status,
                'status_message' => $status_message
            ]);
        } else {
            log_message('error', "No statuses found in the payload.");
        }
    }
/**
 * Process "messages" changes from the webhook.
 */
private function processMessages($value)
{
    $messageEntry = array_shift($value['messages']);
    $contact      = array_shift($value['contacts']);
    $metadata     = $value['metadata'];

    if (empty($messageEntry) || empty($contact) || empty($metadata)) {
        $this->sendErrorResponse('Missing required message or contact data', $value);
        return;
    }

    // ─── 0) Prevent duplicate processing ─────────────────────────────────────────
    $existing = $this->whatsapp_interaction_model
                     ->get_message_by_message_id($messageEntry['id']);
    if ($existing) {
        // We’ve already saved this message, so just acknowledge the webhook
        http_response_code(200);
        return;
    }

    // 1) Save the incoming message (creates or updates interaction + stores message)
    $interaction_id = save_interaction_and_message($messageEntry, $contact, $metadata, $value);

    // 2) Extract user text
    $trigger_msg = $messageEntry['text']['body']
        ?? $messageEntry['interactive']['button_reply']['id']
        ?? "";

    // 3) Bot triggers
    $this->BotHandler->processBots($interaction_id, $trigger_msg, $messageEntry, $contact, $metadata);

    // 4) AI auto‑reply (include original message ID)
    if ($this->isAIReplyEnabled()) {
        $this->aiMessageReply(
            $interaction_id,
            $messageEntry['text']['body'] ?? '',
            $messageEntry['id']
        );
    }

    // 5) Fire Pusher event
    $this->triggerPusherEvent('message_update', [
        'interaction_id' => $interaction_id,
        'message'        => $messageEntry,
    ]);

    http_response_code(200);
}


/**
 * If AI is enabled, generate an AI‑based reply to user’s message,
 * and include the incoming message_id in the context so WhatsApp threads it.
 */
private function aiMessageReply(int $interaction_id, string $usermessage, string $orig_message_id)
{
    $this->load->model('whatsapp_interaction_model');

    // 🧠 Optional: Extra system-level instruction to guide AI for ticket handling
    $custom_instruction = "NOTE: If the user is trying to create a support ticket, collect: subject, message, priority (low/medium/high), and department. "
        . "ONLY reply with raw JSON once all values are available. Do not explain anything. "
        . "Reply with JSON like: { \"subject\": \"Login Issue\", \"message\": \"Cannot access account\", \"priority\": \"high\", \"department\": \"Support\" }";

    // 1) Generate AI reply with user input and internal instruction
    $ai_reply_content = getAIReply($interaction_id, $usermessage, $custom_instruction);

    // 2) Fetch interaction details
    $interaction = $this->db
        ->where('id', $interaction_id)
        ->get(db_prefix() . 'whatsapp_interactions')
        ->row_array();

    if (!$interaction) {
        log_message('error', "Interaction not found for ID: {$interaction_id}");
        return;
    }

    // 3) Build WhatsApp message payload
    $messagePayload = [
        'type' => 'text',
        'text' => [
            'preview_url' => false,
            'body' => $ai_reply_content,
        ],
        'context' => [
            'message_id' => $orig_message_id,
        ],
    ];

    // 4) Send the AI response back via WhatsApp API
    $sendResult = $this->WhatsappLibrary->send_message(
        $interaction['wa_no_id'],
        $interaction['receiver_id'],
        $messagePayload
    );

    // 5) Extract message_id from WhatsApp response
    $message_id = $sendResult['id']
        ?? ($sendResult['response_data']['messages'][0]['id'] ?? null);

    if (! $message_id) {
        log_message('error', "Failed to send WhatsApp reply for interaction #{$interaction_id}: " . json_encode($sendResult));
    }

    // 6) Save AI reply in message history
    $this->whatsapp_interaction_model->insert_interaction_message([
        'interaction_id' => $interaction_id,
        'sender_id'      => $interaction['wa_no_id'],
        'message_id'     => $message_id,
        'ref_message_id' => $orig_message_id,
        'message'        => $ai_reply_content,
        'type'           => 'text',
        'nature'         => 'sent',
        'staff_id'       => get_staff_user_id() ?? null,
        'status'         => $message_id ? 'sent' : 'failed',
        'time_sent'      => date("Y-m-d H:i:s"),
    ]);

    // 7) Update last message and timestamp in interaction
    $this->whatsapp_interaction_model->update_interaction($interaction_id, [
        'last_message'  => $ai_reply_content,
        'last_msg_time' => date("Y-m-d H:i:s"),
    ]);
}


    /**
     * Check if AI auto-reply is enabled in settings.
     */
    private function isAIReplyEnabled(): bool
    {
        return get_option('whatsapp_openai_token')
            && get_option('whatsapp_openai_status') === "auto";
    }


    /* ================= Outgoing Message Handler ================= */

    public function send_message()
    {
        $id = $this->input->post('id', true) ?? '';
        $existing_interaction = $this->db->where('id', $id)->get(db_prefix() . 'whatsapp_interactions')->row_array();
        if (!$existing_interaction) {
            echo json_encode(['success' => false, 'error' => 'Interaction not found']);
            return;
        }
        $to = $this->input->post('to', true) ?? '';
        $template_id = $this->input->post('template_id', true) ?? null;
        $parameters = $this->input->post('parameters', true) ?? [];
        $message = strip_tags($this->input->post('message', true) ?? '');
        $attachments = [
            'image' => $_FILES['image'] ?? null,
            'video' => $_FILES['video'] ?? null,
            'document' => $_FILES['document'] ?? null,
            'audio' => $_FILES['audio'] ?? null,
        ];
        $reaction_emoji = $this->input->post('reaction_emoji', true) ?? '';
        $ref_message_id = $this->input->post('ref_message_id', true) ?? '';
        $carousel_data = $this->input->post('carousel_data', true) ?? null;
        $list_data = $this->input->post('list_data', true) ?? null;
        $message_data = [];

        if ($template_id) {
            $template_data = get_whatsapp_template($template_id);
            if ($template_data) {
                $existing_interaction = array_merge($existing_interaction, $template_data);
                $message_data = $this->WhatsappLibrary->prepare_template_message_data($existing_interaction['wa_no_id'], $existing_interaction);
                log_message('error', 'prepare_template_message_data received: ' . print_r($message_data, true));
            }
        } elseif ($reaction_emoji && $ref_message_id) {
            $message_data = [
                'type' => 'reaction',
                'reaction' => [
                    'emoji' => $reaction_emoji,
                    'message_id' => $ref_message_id,
                ],
            ];
        } elseif ($message) {
            $message_data = [
                'type' => 'text',
                'text' => [
                    'preview_url' => true,
                    'body' => $message,
                ],
            ];
        } else {
            foreach ($attachments as $type => $file) {
                if ($file) {
                    $file_url = $this->WhatsappLibrary->handle_attachment_upload($file,$id);
                    $message_data = [
                        'type' => $type,
                        $type => [
                            'link' => WHATSAPP_MODULE_UPLOAD_URL ."/interactions/".$id."/". $file_url,
                        ],
                    ];
                    break;
                }
            }
            if (!$message_data && $carousel_data) {
                $message_data = [
                    'type' => 'interactive',
                    'interactive' => [
                        'type' => 'carousel',
                        'header' => $carousel_data['header'],
                        'body' => $carousel_data['body'],
                        'footer' => $carousel_data['footer'],
                        'action' => [
                            'button' => $carousel_data['button'],
                            'sections' => $carousel_data['sections'],
                        ],
                    ],
                ];
            }
            if (!$message_data && $list_data) {
                $message_data = [
                    'type' => 'interactive',
                    'interactive' => [
                        'type' => 'list',
                        'header' => $list_data['header'],
                        'body' => $list_data['body'],
                        'footer' => $list_data['footer'],
                        'action' => [
                            'button' => $list_data['button'],
                            'sections' => $list_data['sections'],
                        ],
                    ],
                ];
            }
        }

        if ($ref_message_id) {
            $message_data['context'] = ['message_id' => $ref_message_id];
        }
            $existing_interaction['message_data'] = $message_data;
        if (empty($message_data)) {
            echo json_encode(['success' => false, 'error' => 'No valid message data found']);
            return;
        }

        $response = $this->WhatsappLibrary->send_message($existing_interaction['wa_no_id'], $to, $message_data);
        if (!isset($response['response_data'])) {
            $response['response_data'] = [];
        }

        $status_message = '';
        if (isset($response['response_data']['error'])) {
            $main_message = $response['response_data']['error']['message'] ?? 'Failed to send message';
            $error_details = isset($response['response_data']['error']['error_data']['details'])
                ? 'Details: ' . $response['response_data']['error']['error_data']['details']
                : '';
            $status_message = $main_message . ($error_details ? ' - ' . $error_details : '');
        }
        $type = isset($template_id) && $template_id ? 'template' : 'interactions';
        $response['ref_message_id'] = $ref_message_id;
        $this->whatsapp_interaction_model->prepare_chat_message($response, $existing_interaction, $type);
        echo json_encode(['success' => empty($response['response_data']['error']), 'status_message' => $status_message]);
    }
}
