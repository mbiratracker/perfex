<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BotHandler Library
 *
 * This library handles WhatsApp bot-related operations including
 * message processing, sending template messages, and managing
 * bot actions.
 */
class BotHandler
{
    protected $CI;

    // Constructor to initialize the class
    public function __construct()
    {

        $this->CI =& get_instance();


    }
    /**
     * Finds all matching bots for this interaction, then processes them.
     */
    public function processBots($interaction_id, $trigger_msg, $messageEntry, $contact, $metadata)
    {
        $bots = whatsapp_new_bots($interaction_id);

        if (empty($bots)) {
            return;
        }
        $logBatch = [];

        foreach ($bots as $bot) {
            $this->processBot($bot, $interaction_id, $trigger_msg, $messageEntry, $contact, $metadata, $logBatch);
        }

        if (!empty($logBatch)) {
            $this->addWhatsbotLog($logBatch);
        }
    }

private function processBot($bot, $interaction_id, $trigger_msg, $messageEntry, $contact, $metadata, &$logBatch)
{
    $CI = &get_instance();

    // 1) Default session timeout: 24 hours (in seconds)
    $session_timeout = 86400;

    // 2) If this bot’s reply_type = 7, and the user’s trigger_msg is numeric,
    //    interpret trigger_msg as “hours” and convert to seconds:
    if ($bot['reply_type'] == 7 && is_numeric($trigger_msg)) {
        // e.g. if trigger_msg = “3”, then timeout = 3 * 3600 = 10800 seconds
        $session_timeout = ((int)$trigger_msg) * 3600;
    }

    $interaction = $CI->whatsapp_interaction_model->get_interaction($interaction_id);
    if (!$interaction) {
        return;
    }

    // 3) Determine whether this incoming message should be treated as “first message in a new session”
    //    based on the last time_sent plus our $session_timeout.
    $is_first_message_in_session = $this->isSessionExpired($interaction['time_sent'], $session_timeout);

    // 4) “request_welcome” is only true when WhatsApp itself kicked off the flow
    //    (e.g. user clicked “Get Started” or similar).
    $is_first_time = isset($messageEntry['type']) && $messageEntry['type'] === 'request_welcome';

    if (!$this->shouldTriggerBot($bot, $trigger_msg, $is_first_message_in_session, $is_first_time)) {
        return;
    }

    $log_data = $this->prepareBotLogData($bot, $metadata);
    $receiver = $messageEntry['from'];

    switch ($bot['bot_type']) {
        case 4:   // Flow Bot
            $message_data = $this->processFlowBot($bot, $metadata, $receiver);
            break;

        case 12:  // AI Bot
            $ai_reply_content = getAIReply($interaction_id, $trigger_msg, null);
            $message_data = [
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body'        => $ai_reply_content,
                ],
            ];
            break;

        case 13:  // Review Bot
            $message_data = $this->prepareReviewMessage($bot, $receiver);
            break;

        default:  // All other bot types (template, media, buttons, etc.)
            $message_data = $this->prepareMessageData($bot, $receiver);
            if (!$message_data) {
                log_message('error', 'Bot type not supported or no message data. Bot type: ' . $bot['bot_type']);
                return;
            }
            break;
    }

    // 5) Send out the reply
    $response = $CI->WhatsappLibrary->send_message($metadata['phone_number_id'], $receiver, $message_data, $log_data);

    // 6) Log the outgoing message in our DB
    $CI->whatsapp_interaction_model->prepare_chat_message($response, $bot, "bot");

    // 7) Increment bot’s usage counter
    $CI->bots_model->update_sending_count($bot);

    $logBatch[] = $response['log_data'] ?? [];
}

    /**
     * Prepare a message for standard bot types.
     */
    private function prepareMessageData($bot, $contact_number)
    {
        switch ($bot['bot_type']) {
            case 1: // Basic "Message Bot"
                return $this->prepareTextMessage($bot, $contact_number);

            case 2: // Template Bot
                return $this->CI->WhatsappLibrary->prepare_template_message_data($contact_number, $bot);

            case 3: // Menu Bot
                return $this->prepareMenuMessage($bot, $contact_number);

            case 5: // Media Bot
                return $this->prepareMediaMessage($bot, $contact_number);

            case 6: // Location Bot
                return $this->prepareLocationMessage($bot, $contact_number);

            case 7: // Interactive Buttons Bot
                return $this->prepareInteractiveButtonsMessage($bot, $contact_number);

            case 11: // Contact Bot
                return $this->prepareContactMessage($bot, $contact_number);

            default:
                log_message('error', 'Unsupported bot type in prepareMessageData: ' . $bot['bot_type']);
                return null;
        }
    }

    /* ========== Example placeholders for each specialized message ========== */

    private function prepareTextMessage($bot, $contact_number)
    {
        $text = $bot['reply_text'] ?? 'Hello from Message Bot!';
        return [
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $text,
            ],
        ];
    }

    private function prepareMenuMessage($bot, $contact_number)
    {
        $message_data = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $contact_number,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'list',
                'header' => [
                    'type' => 'text',
                    'text' => !empty($bot['bot_header']) ? $bot['bot_header'] : 'Menu Header',
                ],
                'body' => [
                    'text' => !empty($bot['reply_text']) ? $bot['reply_text'] : 'Choose an option',
                ],
                'footer' => [
                    'text' => !empty($bot['bot_footer']) ? $bot['bot_footer'] : 'Footer text',
                ],
                'action' => [
                    'button' => !empty($bot['button_name']) ? $bot['button_name'] : 'Select',
                    'sections' => $this->prepareMenuSections(json_decode($bot['menu_items'], true)),
                ],
            ],
        ];

        return $message_data;
    }

    private function prepareMenuSections($menu_items)
    {
        $sections = [];
        $grouped_items = [];

        foreach ($menu_items as $item) {
            $parent_id = $item['menu_item_parent_id'] ?? '0';
            $grouped_items[$parent_id][] = $item;
        }

        return $this->generateMenuSection($grouped_items, '0');
    }

    private function generateMenuSection($grouped_items, $parent_id)
    {
        $sections = [];

        if (isset($grouped_items[$parent_id])) {
            $rows = [];
            foreach ($grouped_items[$parent_id] as $item) {
                $rows[] = [
                    'id' => $item['menu_item_id'],
                    'title' => substr($item['menu_item'], 0, 24),
                    'description' => $item['message'] ?? '',
                ];

                if (isset($grouped_items[$item['menu_item_id']])) {
                    $sub_section = $this->generateMenuSection($grouped_items, $item['menu_item_id']);
                    $sections = array_merge($sections, $sub_section);
                }
            }

            if (!empty($rows)) {
                $sections[] = [
                    'title' => $bot['bot_header'],
                    'rows' => $rows,
                ];
            }
        }

        return $sections;
    }

    // Save the user's menu interaction state.
    private function saveMenuState($interaction_id, $menu_path)
    {
        $data = [
            'interaction_id' => $interaction_id,
            'menu_path' => $menu_path,
            'bot_id' => $this->getCurrentBotId(),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    
        // Insert or update the interaction_menu_state table
        $existing_state = $this->db->get_where($this->interaction_menu_state_table, ['interaction_id' => $interaction_id])->row();
        if ($existing_state) {
            $this->db->where('interaction_id', $interaction_id)->update($this->interaction_menu_state_table, $data);
        } else {
            $this->db->insert($this->interaction_menu_state_table, $data);
        }
    }

    // Get the user's current menu state to determine the next action.
    private function getMenuState($interaction_id)
    {
        $menu_state = $this->db->get_where($this->interaction_menu_state_table, ['interaction_id' => $interaction_id])->row();
        return $menu_state ? $menu_state->menu_path : null;
    }

    // Update menu navigation based on user input
    private function updateMenuState($interaction_id, $selected_option)
    {
        $current_menu_path = $this->getMenuState($interaction_id);
        $new_menu_path = $this->determineNextMenuPath($current_menu_path, $selected_option);
        $this->saveMenuState($interaction_id, $new_menu_path);
    }

    private function determineNextMenuPath($current_menu_path, $selected_option)
    {
        // Logic to decide the next menu path based on the current path and the selected option
        if ($selected_option === 'back') {
            // Go back to the previous menu
            return $this->getPreviousMenuPath($current_menu_path);
        } elseif ($selected_option === 'main') {
            // Go back to the main menu
            return 'main_menu';
        } else {
            // Move forward based on the selected option
            return $current_menu_path . '/' . $selected_option;
        }
    }

    private function getPreviousMenuPath($menu_path)
    {
        $path_segments = explode('/', $menu_path);
        array_pop($path_segments); // Remove the last segment to go back
        return implode('/', $path_segments);
    }


    private function prepareMediaMessage($bot, $contact_number)
    {
        log_message("error",print_r($bot,true));
        $mediaType = $bot['media_type'];
        $mediaUrl  = WHATSAPP_MODULE_UPLOAD_URL.'/bot_files/'.$bot['filename'];
        return [
            'type' => $mediaType,
            $mediaType => [
                'link' => $mediaUrl,
                'caption' => $bot['caption'] ?? '',
            ],
        ];
    }

    private function prepareLocationMessage($bot, $contact_number)
    {
        return [
            'type' => 'location',
            'location' => [
                'latitude' => $bot['latitude']  ?? '0',
                'longitude' => $bot['longitude'] ?? '0',
                'name' => $bot['location_name'] ?? 'Unknown',
                'address' => $bot['location_address'] ?? 'Unknown Address',
            ],
        ];
    }

    private function prepareInteractiveButtonsMessage($bot, $contact_number): array
    {
        $buttons = [];
        for ($i = 1; $i <= 3; $i++) {
            $nameKey  = "btn{$i}_name";
            $typeKey  = "btn{$i}_type";
            $linkKey  = "btn{$i}_link";
            $phoneKey = "btn{$i}_number";

            if (empty($bot[$nameKey])) {
                continue;
            }

            $buttonName = $bot[$nameKey];
            $buttonType = strtolower($bot[$typeKey] ?? 'reply');

            switch ($buttonType) {
                case 'reply':
                    $buttons[] = [
                        'type' => 'reply',
                        'reply' => [
                            'id'    => 'btn_' . $i . '_' . uniqid(),
                            'title' => $buttonName,
                        ],
                    ];
                    break;

                case 'url':
                    if (!empty($bot[$linkKey])) {
                        $buttons[] = [
                            'type' => 'url',
                            'url'  => [
                                'link'         => $bot[$linkKey],
                                'display_text' => $buttonName,
                            ],
                        ];
                    }
                    break;
                default:
                    break;
            }
        }

        return [
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'header' => [
                    'type' => 'text',
                    'text' => $bot['bot_header'] ?? 'Header Header',
                ],
                'body' => [
                    'text' => $bot['reply_text'] ?? 'Choose an option:',
                ],
                'footer' => [
                    'text' => $bot['bot_footer'] ?? '',
                ],
                'action' => [
                    'buttons' => $buttons,
                ],
            ],
        ];
    }




    private function prepareContactMessage($bot, $contact_number)
    {
        return [
            'type' => 'contacts',
            'contacts' => [
                [
                    'name' => [
                        'formatted_name' => $bot['contact_name'] ?? 'John Doe',
                        'first_name' => $bot['contact_first_name'] ?? 'John',
                        'last_name' => $bot['contact_last_name'] ?? 'Doe',
                    ],
                    'phones' => [
                        [ 'phone' => $bot['contact_number'] ?? '+1234567890' ],
                    ],
                    'emails' => [
                        [ 'email' => $bot['contact_email'] ?? 'john@example.com' ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Check if the last interaction was older than $session_timeout => session expired.
     */
    private function isSessionExpired($last_interaction_time, $session_timeout): bool
    {
        if (!$last_interaction_time) {
            return true;
        }
        $current_time = time();
        $time_since_last_interaction = $current_time - strtotime($last_interaction_time);
        return $time_since_last_interaction > $session_timeout;
    }

    /**
     * Flow Bot: runs the entire flow ignoring condition nodes, from Start => End.
     */
    private function processFlowBot($bot, $metadata, $receiver)
    {
                         $CI = &get_instance();
   if (empty($bot['flow_data'])) {
            log_message('error', 'Flow data is empty for Flow Bot.');
            return ['response_data' => ['error' => ['message' => 'Flow data is missing']]];
        }
        $flow = json_decode($bot['flow_data'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('error', 'Invalid flow data JSON for Flow Bot.');
            return ['response_data' => ['error' => ['message' => 'Invalid flow data JSON']]];
        }

        // Build node + adjacency
        $nodes = [];
        foreach ($flow['nodes'] as $node) {
            $nodes[$node['id']] = $node;
        }
        $adjacency = [];
        foreach ($flow['connections'] as $conn) {
            $from = $conn['from'];
            $to   = $conn['to'];
            if (!isset($adjacency[$from])) {
                $adjacency[$from] = [];
            }
            $adjacency[$from][] = $to;
        }

        // Find the Start node
        $startNodeId = null;
        foreach ($nodes as $nodeId => $n) {
            if ($n['type'] === 'start') {
                $startNodeId = $nodeId;
                break;
            }
        }
        if (!$startNodeId) {
            log_message('error', 'No start node found in flow data.');
            return ['response_data' => ['error' => ['message' => 'No start node in flow data']]];
        }
        if (empty($adjacency[$startNodeId])) {
            log_message('error', 'No next node from the start node.');
            return ['response_data' => ['error' => ['message' => 'No adjacency from start node']]];
        }

        // The first child of Start is our entry point
        $currentNodeId = $adjacency[$startNodeId][0];
        $response = null;

        // Traverse
        while ($currentNodeId) {
            if (!isset($nodes[$currentNodeId])) {
                log_message('error', "Flow node $currentNodeId not found; possibly removed.");
                break;
            }
            $currentNode = $nodes[$currentNodeId];
            $nodeType    = $currentNode['type'];

            // skip condition
            if ($nodeType === 'condition') {
                log_message('error', "Skipping condition node: $currentNodeId");
                if (!empty($adjacency[$currentNodeId])) {
                    $currentNodeId = $adjacency[$currentNodeId][0];
                    continue;
                } else {
                    break;
                }
            }

            // If end => final message
            if ($nodeType === 'end') {
                $endText = $currentNode['data']['message'] ?? 'Flow ended.';
                $msg_data = [
                    'type' => 'text',
                    'text' => [
                        'body' => "End: " . $endText,
                        'preview_url' => false,
                    ],
                ];
                $response = $this->CI->WhatsappLibrary->send_message($metadata['phone_number_id'], $receiver, $msg_data);
                $CI->whatsapp_interaction_model->prepare_chat_message($response, $bot, "bots");
                break;
            }

            // otherwise => build + send
            $msg_data = $this->buildFlowMessageData($nodeType, $currentNode['data']);
            if ($msg_data) {
                $response = $this->CI->WhatsappLibrary->send_message($metadata['phone_number_id'], $receiver, $msg_data);
                $CI->whatsapp_interaction_model->prepare_chat_message($response, $bot, "bots");
            }

            // optional delay between nodes
            sleep(2);

            // next adjacency
            if (!empty($adjacency[$currentNodeId])) {
                $currentNodeId = $adjacency[$currentNodeId][0];
            } else {
                break;
            }
        }

        return $response ?: [];
    }

    /**
     * Build the message data for each node type in the flow (text, image, etc.).
     */
    private function buildFlowMessageData(string $nodeType, array $nodeData): ?array
    {
        switch ($nodeType) {
            case 'text':
                return [
                    'type' => 'text',
                    'text' => [
                        'body' => $nodeData['text'] ?? '(no text)',
                        'preview_url' => false,
                    ],
                ];
            case 'image':
                return [
                    'type' => 'image',
                    'image' => [
                        'link' => $nodeData['url'] ?? '',
                        'caption' => $nodeData['caption'] ?? '',
                    ],
                ];
            case 'audio':
                return [
                    'type' => 'audio',
                    'audio' => [
                        'link' => $nodeData['url'] ?? '',
                    ],
                ];
            case 'video':
                return [
                    'type' => 'video',
                    'video' => [
                        'link' => $nodeData['url'] ?? '',
                        'caption' => $nodeData['caption'] ?? '',
                    ],
                ];
            case 'document':
                return [
                    'type' => 'document',
                    'document' => [
                        'link' => $nodeData['url'] ?? '',
                        'filename' => $nodeData['filename'] ?? '',
                    ],
                ];
            case 'location':
                return [
                    'type' => 'location',
                    'location' => [
                        'latitude'  => $nodeData['latitude']  ?? '0',
                        'longitude' => $nodeData['longitude'] ?? '0',
                        'name'      => $nodeData['name']      ?? '',
                        'address'   => $nodeData['address']   ?? '',
                    ],
                ];
            case 'interactive_button':
                return [
                    'type' => 'interactive',
                    'interactive' => [
                        'type' => 'button',
                        'body' => [
                            'text' => $nodeData['body'] ?? '(no text)',
                        ],
                        'action' => [
                            'buttons' => $this->buildFlowButtons($nodeData),
                        ],
                    ],
                ];
            case 'interactive_list':
                return [
                    'type' => 'interactive',
                    'interactive' => [
                        'type' => 'list',
                        'header' => [
                            'type' => 'text',
                            'text' => $nodeData['header'] ?? 'List Header',
                        ],
                        'body' => [
                            'text' => $nodeData['body'] ?? '(no text)',
                        ],
                        'footer' => [
                            'text' => $nodeData['footer'] ?? '',
                        ],
                        'action' => [
                            'button' => $nodeData['actionButton'] ?? 'Choose',
                            'sections' => $this->buildFlowListSections($nodeData),
                        ],
                    ],
                ];
            case 'template':
                $templateName = $nodeData['template_name'] ?? 'My_Approved_Template';
                $templateLang = $nodeData['language'] ?? 'en_US';
                $templateComponents = $nodeData['components'] ?? [];
                return [
                    'type' => 'template',
                    'template' => [
                        'name' => $templateName,
                        'language' => [ 'code' => $templateLang ],
                        'components' => $templateComponents,
                    ],
                ];
            case 'cta':
                return [
                    'type' => 'interactive',
                    'interactive' => [
                        'type' => 'button',
                        'body' => [
                            'text' => 'Choose an option:',
                        ],
                        'action' => [
                            'buttons' => $this->buildCtaButtons($nodeData),
                        ],
                    ],
                ];
            default:
                return [
                    'type' => 'text',
                    'text' => [
                        'body' => 'Flow Node: ' . ucfirst($nodeType),
                        'preview_url' => false,
                    ],
                ];
        }
    }

    private function buildFlowButtons(array $nodeData): array
    {
        $buttons = [];
        if (!empty($nodeData['buttons']) && is_array($nodeData['buttons'])) {
            foreach ($nodeData['buttons'] as $b) {
                $buttons[] = [
                    'type' => 'reply',
                    'reply' => [
                        'id' => $b['id'] ?? ('btn_' . uniqid()),
                        'title' => $b['title'] ?? 'Button',
                    ],
                ];
            }
        }
        return $buttons;
    }

    private function buildFlowListSections(array $nodeData): array
    {
        if (!empty($nodeData['sections']) && is_array($nodeData['sections'])) {
            return $nodeData['sections'];
        }
        return [
            [
                'title' => 'Default Section',
                'rows' => [
                    [
                        'id' => 'row1',
                        'title' => 'Row 1',
                        'description' => 'Description 1',
                    ],
                ],
            ],
        ];
    }

    private function buildCtaButtons(array $nodeData): array
    {
        $buttons = [];
        if (!empty($nodeData['buttons']) && is_array($nodeData['buttons'])) {
            foreach ($nodeData['buttons'] as $b) {
                $buttons[] = [
                    'type' => 'reply',
                    'reply' => [
                        'id' => $b['id'] ?? ('cta_' . uniqid()),
                        'title' => $b['title'] ?? 'CTA',
                    ],
                ];
            }
        }
        return $buttons;
    }
        private function prepareBotLogData($bot, $metadata)
    {
        $botTypeInfo = get_whatsapp_bot_types($bot['bot_type']);
        return [
            'phone_number_id' => $metadata['phone_number_id'],
            'category'        => $botTypeInfo['label'] ?? 'Unknown Bot',
            'category_id'     => $bot['bot_type'] ?? "-",
            'rel_type'        => 'Bots',
            'rel_id'          => $bot['id'],
            'recorded_at'     => date('Y-m-d H:i:s'),
        ];
    }

    private function addWhatsbotLog($logBatch)
    {
        if (!empty($logBatch)) {
            $this->CI->db->insert_batch(db_prefix() . 'whatsapp_activity_log', $logBatch);
        }
    }
        /* ================= Bot Processing ================= */
    private function sendErrorResponse($message, $data = [])
    {
        log_message('error', $message . ': ' . print_r($data, true));
        http_response_code(400);
        echo json_encode(['error' => $message]);
        exit();
    }

    private function updateFlowSessionOptions($keyNode, $keyExpire, $nodeId, $timeoutSeconds)
    {
        $expiresAt = date('Y-m-d H:i:s', time() + $timeoutSeconds);
        update_option($keyNode, $nodeId);
        update_option($keyExpire, $expiresAt);
    }

    private function resetFlowSessionOptions($keyNode, $keyExpire)
    {
        update_option($keyNode, null);
        update_option($keyExpire, null);
    }

    public function get_user_last_incoming_time($interaction_id)
    {
        $row = $this->db
            ->select('time_sent')
            ->from(db_prefix().'whatsapp_interaction_messages')
            ->where('interaction_id', $interaction_id)
            ->where('nature','received')
            ->order_by('id','DESC')
            ->limit(1)
            ->get()
            ->row();
        return $row ? $row->time_sent : null;
    }

    public function get_last_activity_time($interaction_id)
    {
        $row = $this->db
            ->select_max('time_sent')
            ->from(db_prefix().'whatsapp_interaction_messages')
            ->where('interaction_id', $interaction_id)
            ->get()
            ->row();
        return $row ? $row->time_sent : null;
    }

    public function get_reminder_status($interaction_id)
    {
        return false;
    }

    /* ================= Bot Trigger Conditions ================= */

    private function shouldTriggerBot(
        array $bot,
        string $trigger_msg,
        bool $is_first_message_in_session,
        bool $is_first_time
    ): bool {
        $trigger_type = $bot['reply_type'] ?? null;
        $trigger_msg = strtolower(trim($trigger_msg));
        log_message('error', 'Evaluating bot trigger with message: ' . $trigger_msg);
        $conditions = $this->extractConditions($bot['trigger'] ?? '');

        foreach ($conditions as $condition) {
            switch ($trigger_type) {
                case 1:  // on_exact_match
                    if ($this->isExactMatch($trigger_msg, $condition)) {
                        return true;
                    }
                    break;
                case 2:  // when_message_contains
                    if ($this->containsWord($trigger_msg, $condition)) {
                        return true;
                    }
                    break;
                case 3:  // when_client_send_the_first_message
                    if ($is_first_time) {
                        return true;
                    }
                    break;
                case 4:  // on_keyword_match
                    if ($this->isKeywordMatch($trigger_msg, $condition)) {
                        return true;
                    }
                    break;
                case 5:  // within_office_time_range
                    if ($this->isWithinOfficeTimings($condition)) {
                        return true;
                    }
                    break;
                case 6:  // outof_office_time_range
                    if ($this->isOutsideOfficeTimings($condition)) {
                        return true;
                    }
                    break;
                case 7:  // first_message_in_session
                    if ($is_first_message_in_session) {
                        return true;
                    }
                    break;
                case 8:  // on_user_idle_timeout
                    $idleMinutes = (int)$condition;
                    if ($this->userHasBeenIdleLongerThan($idleMinutes, $bot)) {
                        return true;
                    }
                    break;
                case 9:  // on_user_requests_human
                    if (strpos($trigger_msg, $condition) !== false) {
                        return true;
                    }
                    break;
                case 10: // on_synonym_match
                    if ($this->synonymCheck($trigger_msg, $condition)) {
                        return true;
                    }
                    break;
                case 11: // on_chat_timeout_reminder
                    if ($this->chatNeedsReminder($condition, $bot)) {
                        return true;
                    }
                    break;
                case 12: // on_no_response_inform_staff
                    if ($this->isNoResponseScenario($bot, $condition)) {
                        return true;
                    }
                    break;
                case 13: // on_chat_close_request_star_rating
                    if (strpos($trigger_msg, 'bye') !== false) {
                        return true;
                    }
                    break;
                default:
                    log_message('error', 'Unsupported or unknown trigger type: ' . $trigger_type);
                    break;
            }
        }

        log_message('error', 'No trigger conditions met for message: ' . $trigger_msg);
        return false;
    }

    private function extractConditions(string $trigger): array
    {
        if (empty($trigger)) {
            return [];
        }
        $conditions = preg_split("/[,\|]+/", $trigger);
        $conditions = array_map('trim', $conditions);
        $conditions = array_filter($conditions, function ($cond) {
            return $cond !== '';
        });
        return array_map('strtolower', $conditions);
    }

/**
 * Return true when the user-supplied message matches any of the supplied
 * condition strings after optional normalisation steps.
 *
 * @param string          $userMsg        Raw user input.
 * @param string|string[] $condition      A single phrase or an array of phrases to test.
 * @param bool            $ignoreCase     Fold case before comparing? (default: yes)
 * @param bool            $ignoreAccents  Strip accents/diacritics first? (default: yes)
 * @param bool            $trim           Trim leading/trailing whitespace? (default: yes)
 *
 * @return bool
 */
private function isExactMatch(
     $userMsg,
     $condition,
    bool $ignoreCase    = true,
    bool $ignoreAccents = true,
    bool $trim          = true
): bool {
    // Normalise user message once
    $userMsg = $this->normalise($userMsg, $ignoreCase, $ignoreAccents, $trim);

    // Make the RHS iterable
    foreach ((array) $condition as $candidate) {
        if (hash_equals($userMsg, $this->normalise($candidate, $ignoreCase, $ignoreAccents, $trim))) {
            return true;                              // early-out on first hit
        }
    }
    return false;
}

/**
 * Apply the chosen normalisation steps in a single place.
 */
private function normalise(
    string $str,
    bool $ignoreCase,
    bool $ignoreAccents,
    bool $trim
): string {
    if ($trim) {
        $str = trim($str);
    }
    if ($ignoreAccents) {
        // iconv transliterates UTF-8 to ASCII, dropping/flattening accents
        $tmp = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        if ($tmp !== false) {
            $str = $tmp;
        }
    }
    if ($ignoreCase) {
        $str = mb_strtolower($str, 'UTF-8');
    }
    return $str;
}


    private function containsWord(string $userMsg, string $word): bool
    {
        return (strpos($userMsg, $word) !== false);
    }

    private function isKeywordMatch(string $message, string $keyword): bool
    {
        $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
        return (bool) preg_match($pattern, $message);
    }

    private function isWithinOfficeTimings(string $time_range): bool
    {
        list($start_time, $end_time) = explode('-', $time_range);
        $current_time = date('H:i');
        return ($current_time >= trim($start_time) && $current_time <= trim($end_time));
    }

    private function isOutsideOfficeTimings(string $time_range): bool
    {
        list($start_time, $end_time) = explode('-', $time_range);
        $current_time = date('H:i');
        return ($current_time < trim($start_time) || $current_time > trim($end_time));
    }

    private function userHasBeenIdleLongerThan(int $minutes, array $bot): bool
    {
        $last_activity = strtotime($this->get_last_activity_time($bot['interaction_id'] ?? 0));
        if (!$last_activity) {
            return false;
        }
        return (time() - $last_activity) > ($minutes * 60);
    }

    private function synonymCheck(string $msg, string $condition): bool
    {
        $synonyms = [
            'help' => ['assist', 'support', 'aid'],
        ];
        if (isset($synonyms[$condition])) {
            foreach ($synonyms[$condition] as $syn) {
                if (strpos($msg, $syn) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    private function chatNeedsReminder(string $condition, array $bot): bool
    {
        $minutes = (int)$condition;
        $last_incoming = strtotime($this->get_user_last_incoming_time($bot['interaction_id'] ?? 0));
        if (!$last_incoming) {
            return false;
        }
        return (time() - $last_incoming) > ($minutes * 60);
    }

    private function isNoResponseScenario(array $bot, string $condition): bool
    {
        $minutes = (int)$condition;
        $last_user_message = strtotime($this->get_user_last_incoming_time($bot['interaction_id'] ?? 0));
        if (!$last_user_message) {
            return false;
        }
        return (time() - $last_user_message) > ($minutes * 60);
    }

    /**
     * Prepare message data for a Review Bot with star rating selection.
     */
    private function prepareReviewMessage($bot, $contact_number)
    {
        $review_prompt = $bot['review_prompt'] ?? "Please rate our service by selecting a star rating:";
        $rows = [];
        for ($i = 1; $i <= 5; $i++) {
            $stars = str_repeat('⭐', $i);
            $rows[] = [
                'id'          => "review_{$i}",
                'title'       => $stars,
                'description' => "Rate us with {$i} star" . ($i > 1 ? "s" : ""),
            ];
        }
        $sections = [
            [
                'title' => 'Star Ratings',
                'rows'  => $rows,
            ],
        ];
        return [
            'type' => 'interactive',
            'interactive' => [
                'type'   => 'list',
                'header' => [
                    'type' => 'text',
                    'text' => $review_prompt,
                ],
                'body' => [
                    'text' => "Select your rating below:",
                ],
                'footer' => [
                    'text' => "",
                ],
                'action' => [
                    'button'   => "Select Rating",
                    'sections' => $sections,
                ],
            ],
        ];
    }

}
