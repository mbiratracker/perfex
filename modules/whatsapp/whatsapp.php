<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: WhatsApp Official Cloud API Chat & Marketing module for Perfex CRM
Description: Schedule programmatic marketing actions and  interact with your clients in realtime, through admin area of Perfex CRM.
Version: 1.6.1
Requires at least: 3.2.*
Module URI: https://codecanyon.net/item/whatsapp-cloud-api-interaction-module-for-perfex-crm/52004114
*/

define('WHATSAPP_MODULE_NAME', 'whatsapp');
define('WHATSAPP_MODULE', 'whatsapp');
define('WHATSAPP_UPDATE_URI', 'whatsapp');
define('WHATSAPP_VERSION', '1.6.1');

include(__DIR__ . '/vendor/autoload.php');

$CI = &get_instance();

// Register language files
register_language_files(WHATSAPP_MODULE_NAME, [WHATSAPP_MODULE_NAME]);

$CI->load->library(WHATSAPP_MODULE_NAME . '/WhatsappLibrary');
$CI->load->library(WHATSAPP_MODULE_NAME . '/BotHandler');

get_instance()->load->helper(WHATSAPP_MODULE_NAME . '/' . WHATSAPP_MODULE_NAME);
// Define constants for upload folders
define('WHATSAPP_MODULE_UPLOAD_FOLDER', 'uploads/' . WHATSAPP_MODULE_NAME);
define('WHATSAPP_MODULE_UPLOAD_URL', 'https://' . $_SERVER['HTTP_HOST'] . '/' . WHATSAPP_MODULE_UPLOAD_FOLDER . '/');
modules\whatsapp\core\Apiinit::the_da_vinci_code(WHATSAPP_MODULE_NAME);
modules\whatsapp\core\Apiinit::ease_of_mind(WHATSAPP_MODULE_NAME);
// Create upload directories if they don't exist
// Get the absolute path for the upload folder
$uploadFolderPath = FCPATH . WHATSAPP_MODULE_UPLOAD_FOLDER;

// Create necessary directories
create_directory_if_not_exists($uploadFolderPath);
create_directory_if_not_exists($uploadFolderPath . '/bot_files/');
create_directory_if_not_exists($uploadFolderPath . '/campaign/');
create_directory_if_not_exists($uploadFolderPath . '/template/');
create_directory_if_not_exists($uploadFolderPath . '/numbers/');
create_directory_if_not_exists($uploadFolderPath . '/interactions/');

// Function to create a directory if it does not exist
function create_directory_if_not_exists($path)
{
    if (!is_dir($path)) {
        if (!mkdir($path, 0755, true)) {
            // Log the error for debugging
            log_message('error', 'Failed to create directory: ' . $path);
            die('Failed to create directory: ' . $path);
        } else {
            // Create an index.html file to prevent directory listing
            $fp = fopen($path . '/index.html', 'w');
            if ($fp) {
                fclose($fp);
            } else {
                log_message('error', 'Failed to create index.html in directory: ' . $path);
                die('Failed to create index.html in directory: ' . $path);
            }
        }
    }
}
hooks()->add_action('after_cron_run', 'daily_whatsapp_activation_check');
function daily_whatsapp_activation_check() {
    $CI =& get_instance();
    $last_run = get_option('last_whatsapp_activation_run');

    if (!$last_run || strtotime($last_run) < strtotime('today')) {
        whatsapp_activation_hook(); // Your actual function
        update_option('last_whatsapp_activation_run', date('Y-m-d'));
    }
}


// Function to handle module activation
register_activation_hook(WHATSAPP_MODULE_NAME, 'whatsapp_activation_hook');

function whatsapp_activation_hook()
{
    $CI = &get_instance();
    require_once __DIR__ . '/install.php';
    require_once __DIR__ . '/updates.php';
}

// Initialize permissions
hooks()->add_filter('staff_permissions', function ($permissions) {
    $viewGlobalName = _l('permission_view');

    // Define permission templates for easier reuse
    $allPermissionsArray = [
        'view'   => $viewGlobalName,
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];
    
    // Define WhatsApp module permissions
    $permissions['whatsapp_contacts'] = [
        'name'         => _l('whatsapp_contacts'),
        'capabilities' => [
            'view'          => $viewGlobalName,
            'load_template' => _l('whatsapp_contacts'),
        ],
    ];
    $permissions['whatsapp_numbers'] = [
        'name'         => _l('whatsapp_numbers'),
        'capabilities' => [
            'view'          => $viewGlobalName,
            'load_template' => _l('whatsapp_numbers'),
        ],
    ];
    $permissions['whatsapp_bot'] = [
        'name'         => _l('whatsapp_bot'),
        'capabilities' => $allPermissionsArray,
    ];
    $permissions['whatsapp_template'] = [
        'name'         => _l('template'),
        'capabilities' => [
            'view'          => $viewGlobalName,
            'load_template' => _l('load_template'),
        ],
    ];
    $permissions['whatsapp_campaign'] = [
        'name'         => _l('campaigns'),
        'capabilities' => array_merge($allPermissionsArray, [
            'show' => _l('show_campaign'),
        ]),
    ];
    $permissions['whatsapp_chat'] = [
        'name'         => _l('chat'),
        'capabilities' => ['view' => $viewGlobalName],
    ];
    $permissions['whatsapp_log_activity'] = [
        'name'         => _l('log_activity'),
        'capabilities' => [
            'view'      => $viewGlobalName,
            'clear_log' => _l('clear_log'),
        ],
    ];
    $permissions['whatsapp_settings'] = [
        'name'         => _l('whatsapp_settings'),
        'capabilities' => ['view' => _l('view')],
    ];
    $permissions['quickreplies'] = [
        'name'         => _l('quick_replies'),
        'capabilities' => $allPermissionsArray,
    ];
    $permissions['whatsapp_groups'] = [
        'name'         => _l('whatsapp_groups'),
        'capabilities' => ['view' => $viewGlobalName],
    ];
    $permissions['whatsapp_docs'] = [
        'name'         => _l('documentation'),
        'capabilities' => ['view' => $viewGlobalName],
    ];
    
    return $permissions;
});

// Menu Items
hooks()->add_action('admin_init', function () {
    $CI = get_instance();

    // Define all possible permissions for WhatsApp modules
    $permissions = [
        'whatsapp_bots', 'whatsapp_template', 'whatsapp_campaign',
        'whatsapp_chat', 'whatsapp_log_activity', 'whatsapp_settings',
        'whatsapp_numbers', 'whatsapp_contacts', 'whatsapp_groups',
        'whatsapp_docs', 'quickreplies'
    ];

    // Check if the user has access to any WhatsApp section
    $hasAccess = false;
    foreach ($permissions as $permission) {
        if (staff_can('view', $permission)) {
            $hasAccess = true;
            break;
        }
    }

    // Menu badge with unread messages count
    $unreadMessages = whatsapp_unreadmessages();
    $whatsappName = _l('WhatsApp');
    if ($unreadMessages > 0) {
        $whatsappName .= ' (' . $unreadMessages . ')';
    }

    // Add main WhatsApp menu item if the user has access
    if ($hasAccess) {
        $CI->app_menu->add_sidebar_menu_item('whatsapp', [
            'slug'     => 'whatsapp',
            'name'     => $whatsappName,
            'icon'     => 'fa-brands fa-whatsapp',
            'href'     => '#',
            'position' => 1,
        ]);
    }

    // Define child menu items with Tailwind "Beta" and "New & Beta" badges
    $menuItems = [
        'whatsapp_connection' => [
            'slug'     => 'connection',
            'name'     => _l('whatsapp_connection'),
            'icon'     => 'fa-solid fa-link',
            'href'     => admin_url(WHATSAPP_MODULE . '/connection'),
            'position' => 1,
        ],

        'whatsapp_chat' => [
            'slug'     => 'whatsapp_chat_integration',
            'name'     => _l('chat'),
            'icon'     => 'fa-solid fa-comment-dots',
            'href'     => admin_url(WHATSAPP_MODULE . '/interaction'),
            'position' => 1,
        ],
        'whatsapp_numbers' => [
            'slug'     => 'whatsapp_numbers',
            'name'     => _l('whatsapp_numbers'),
            'icon'     => 'fa-solid fa-phone',
            'href'     => admin_url(WHATSAPP_MODULE . '/numbers'),
            'position' => 2,
        ],
        'whatsapp_contacts' => [
            'slug'     => 'bulk_contacts',
            'name'     => _l('bulk_contacts'),
            'icon'     => 'fa-solid fa-address-book',
            'href'     => admin_url(WHATSAPP_MODULE . '/BulkContacts'),
            'position' => 2,
        ],
        'whatsapp_groups' => [
            'slug'     => 'bulk_groups',
            'name'     => _l('bulk_groups'),
            'icon'     => 'fa-solid fa-users',
            'href'     => admin_url(WHATSAPP_MODULE . '/groups'),
            'position' => 3,
        ],
        'whatsapp_bots' => [
            'slug'     => 'whatsapp_bots',
            'name'     => _l('bots'),
            'icon'     => 'fa-solid fa-robot',
            'href'     => admin_url(WHATSAPP_MODULE . '/bots'),
            'position' => 4,
        ],
        'whatsapp_template' => [
            'slug'     => 'whatsapp_interaction_templates',
            'name'     => _l('templates'),
            'icon'     => 'fa-solid fa-folder-open',
            'href'     => admin_url(WHATSAPP_MODULE . '/templates'),
            'position' => 5,
        ],
        'whatsapp_campaign' => [
            'slug'     => 'campaigns',
            'name'     => _l('campaigns'),
            'icon'     => 'fa-solid fa-bullhorn',
            'href'     => admin_url(WHATSAPP_MODULE . '/campaigns'),
            'position' => 6,
        ],
        'quickreplies' => [
            'slug'     => 'quickreplies',
            'name'     => _l('quick_replies'),
            'icon'     => 'fa-solid fa-reply',
            'href'     => admin_url(WHATSAPP_MODULE . '/QuickReplies'),
            'position' => 7,
        ],
        'whatsapp_log_activity' => [
            'slug'     => 'whatsapp_activity_log',
            'name'     => _l('activity_log'),
            'icon'     => 'fa-solid fa-clipboard-list',
            'href'     => admin_url(WHATSAPP_MODULE . '/activity_log'),
            'position' => 8,
        ],
		'whatsapp_docs' => [
            'slug'     => 'whatsapp_documentation',
            'name'     => _l('documentation'),
            'icon'     => 'fa-solid fa-book',
            'href'     => admin_url(WHATSAPP_MODULE . '/documentation'),
            'position' => 9,
            'target'   => '_blank',
        ],
    ];

    // Add child menu items based on specific permissions
    foreach ($menuItems as $permission => $item) {
        if (staff_can('view', $permission)) {
            $CI->app_menu->add_sidebar_children_item('whatsapp', $item);
        }
    }

});




function whatsapp_add_head_components()
{
    $CI = &get_instance();
    $uri = $_SERVER['REQUEST_URI'];

    // Load only on admin pages that include "whatsapp"
    if (strpos($uri, 'admin') !== false && strpos($uri, 'whatsapp') !== false) {
        echo '<link href="' . base_url('modules/whatsapp/assets/css/twailwind.css') . '" rel="stylesheet" type="text/css" />';
        echo '<link href="' . base_url('modules/whatsapp/assets/css/fa.css') . '" rel="stylesheet" type="text/css" />';
        echo '<link href="' . base_url('modules/whatsapp/assets/css/chat.css') . '" rel="stylesheet" type="text/css" />';
    }
}
hooks()->add_action('app_admin_head', 'whatsapp_add_head_components');
hooks()->add_action('app_admin_footer', function () {
    $CI = &get_instance();
    $uri = $_SERVER['REQUEST_URI'];

    // Load scripts only if in admin area and WhatsApp module URL
    if (strpos($uri, 'admin') !== false && strpos($uri, 'whatsapp') !== false) {
        if ($CI->app_modules->is_active(WHATSAPP_MODULE)) {
            $CI->load->library('App_merge_fields');
            $merge_fields = $CI->app_merge_fields->all();

            echo '<script>var merge_fields = ' . json_encode($merge_fields) . ';</script>';

            $scripts = [
                'assets/js/underscore-min.js',
                'assets/js/tribute.min.js',
                'assets/js/whatsapp.js',
                'assets/js/prism.js'
            ];

            foreach ($scripts as $script) {
                echo '<script src="' . module_dir_url(WHATSAPP_MODULE, $script) . '?v=' . $CI->app_scripts->core_version() . '"></script>';
            }
        }
    }
});

/**
 * Add additional settings for this module in the module list area
 * @param  array $actions current actions
 * @return array
 */



// delete campaign lead when lead deleted
hooks()->add_action('after_lead_deleted', function ($id) {
    get_instance()->db->delete(db_prefix() . 'whatsapp_campaign_data', ['rel_id' => $id, 'rel_type' => 'leads']);
});

// delete campaign contacts when contact deleted
hooks()->add_action('contact_deleted', function ($id, $result) {
    get_instance()->db->delete(db_prefix() . 'whatsapp_campaign_data', ['rel_id' => $id, 'rel_type' => 'contacts']);
}, 0, 2);


// add new created contact in campaign that is select all contacts
hooks()->add_action('contact_created', function ($id) {
    $campaigns = get_instance()->db->get_where(db_prefix() . 'whatsapp_campaigns', ['select_all' => '1', 'rel_type' => 'contacts'])->result_array();
    foreach ($campaigns as $campaign) {
        if (0 == $campaign['is_sent']) {
            $template = get_whatsapp_template($campaign['template_id']);
            get_instance()->db->insert(db_prefix() . 'whatsapp_campaign_data', [
                'campaign_id'       => $campaign['id'],
                'rel_id'            => $id,
                'rel_type'          => 'contacts',
                'header_data'    => $template['header_data_text'],
                'body_data'      => $template['body_data'],
                'footer_data'    => $template['footer_data'],
                'status'            => 1,
            ]);
        }
    }
});



// add widgets
hooks()->add_filter('get_dashboard_widgets', function ($widgets) {
    $new_widgets = [];
    $new_widgets[] = [
        'path'      => WHATSAPP_MODULE . '/widgets/whatsapp-widget',
        'container' => 'top-12',
    ];

    return array_merge($new_widgets, $widgets);
});
function check_whatsapp_cronjob_status()
{
    $last_run = get_option('whatsapp_campaign_cronjob_run_at');

    if (empty($last_run)) {
        return '<div class="alert alert-danger">
            <strong>Cronjob Status:</strong> The WhatsApp cronjob has <strong>never run</strong>.<br>
            This may prevent scheduled campaigns and automation from working properly.<br>
            <a href="' . admin_url(WHATSAPP_MODULE . '/connection') . '" class="btn btn-sm btn-primary mt-2">
                Configure Cronjob
            </a>
        </div>';
    }

    $last_run_timestamp = strtotime($last_run);
    $current_time = time();
    $time_diff = $current_time - $last_run_timestamp;

    // Human-readable time
    $minutes = floor($time_diff / 60);
    $seconds = $time_diff % 60;
    $formatted_last_run = date("Y-m-d H:i:s", $last_run_timestamp);

    if ($time_diff > 600) {
        return '<div class="alert alert-warning">
            <strong>Cronjob Delay:</strong> The WhatsApp cronjob has <strong>not run in the last 10 minutes</strong>.<br>
            Last run was <strong>' . $minutes . ' minutes ' . $seconds . ' seconds</strong> ago at <code>' . $formatted_last_run . '</code>.<br>
            Please verify that your server cronjob is active.<br>
            <a href="' . admin_url('whatsapp/connection') . '" class="btn btn-sm btn-warning mt-2">
                Check WhatsApp Settings
            </a>
        </div>';
    }

}

// Add the alert to the dashboard rendering
hooks()->add_filter('before_start_render_dashboard_content', function ($content) {
    return check_whatsapp_cronjob_status() . $content;
});

hooks()->add_filter('get_upload_path_by_type', 'add_whatsapp_chat_files_upload_path', 0, 2);

function add_whatsapp_chat_files_upload_path($path, $type)
{
    $whatsapp_types = ['bot', 'campaign', 'template', 'whatsapp_numbers'];

    // Handle static WhatsApp types
    if (in_array($type, $whatsapp_types)) {
        switch ($type) {
            case 'bot':
                return WHATSAPP_MODULE_UPLOAD_FOLDER . '/bot_files/';
            case 'campaign':
                return WHATSAPP_MODULE_UPLOAD_FOLDER . '/campaign/';
            case 'template':
                return WHATSAPP_MODULE_UPLOAD_FOLDER . '/template/';
            case 'whatsapp_numbers':
                return WHATSAPP_MODULE_UPLOAD_FOLDER . '/numbers/';
        }
    }

    // Handle dynamic interaction files like 'interactions/45'
    if (strpos($type, 'interactions/') === 0) {
        return WHATSAPP_MODULE_UPLOAD_FOLDER . '/' . $type . '/';
    }

    // Return the original path for non-matching types
    return $path;
}


hooks()->add_action('app_init', WHATSAPP_MODULE_NAME . '_actLib');
function whatsapp_actLib()
{
    $CI = &get_instance();
    $CI->load->library(WHATSAPP_MODULE_NAME . '/Whatsapp_aeiou');
    $envato_res = $CI->whatsapp_aeiou->validatePurchase(WHATSAPP_MODULE_NAME);
    if (!$envato_res) {
        set_alert('danger', 'One of your modules failed its verification and got deactivated. Please reactivate or contact support.');
    }
}

hooks()->add_action('pre_activate_module', WHATSAPP_MODULE_NAME . '_sidecheck');
function whatsapp_sidecheck($module_name)
{
    if (WHATSAPP_MODULE_NAME == $module_name['system_name']) {
        modules\whatsapp\core\Apiinit::activate($module_name);
    }
}


hooks()->add_action('pre_deactivate_module', WHATSAPP_MODULE_NAME . '_deregister');
function whatsapp_deregister($module_name)
{
    if (WHATSAPP_MODULE_NAME == $module_name['system_name']) {
        delete_option(WHATSAPP_MODULE_NAME . '_verification_id');
        delete_option(WHATSAPP_MODULE_NAME . '_last_verification');
        delete_option(WHATSAPP_MODULE_NAME . '_product_token');
        delete_option(WHATSAPP_MODULE_NAME . '_heartbeat');
    }
}

hooks()->add_action('admin_init', function () {
    if (is_admin()) {
        $CI = &get_instance();
        $uri = $_SERVER['REQUEST_URI'];

        if (strpos($uri, '/admin') !== false) {
            $unread = whatsapp_unreadmessages();
            if ($unread > 0) {
                $message = "⚠️ Attention: You have $unread unread WhatsApp message" . ($unread > 1 ? 's' : '') . ". Please review them at your earliest convenience.";
                $CI->session->set_flashdata('message-warning', $message);
            }
        }
    }
});
run_fixingfiles_once();
/**
 * Run WhatsApp file fixer once and remember using `get_option`.
 */
function run_fixingfiles_once()
{
    $option_key = 'whatsapp_files_fixed';

    // Check if already executed
    if (get_option($option_key)) {
        return;
    }

    fixingfiles();

    // Mark as completed
    update_option($option_key, 1);
}

/**
 * Ensure WhatsApp upload folders exist and normalize file locations.
 */
function fixingfiles()
{
    $CI =& get_instance();
    $CI->load->database();

    $messages_table   = db_prefix() . 'whatsapp_interaction_messages';
    $basePath         = FCPATH . WHATSAPP_MODULE_UPLOAD_FOLDER . '/';
    $interactionsPath = $basePath . 'interactions/';

    // Βασικοί φάκελοι που πρέπει να υπάρχουν
    $coreFolders = ['bot_files', 'campaign', 'template', 'numbers', 'interactions'];

    foreach ($coreFolders as $folder) {
        create_directory_if_not_exists($basePath . $folder . '/');
    }

    // Έλεγξε αν υπάρχει η table
    if (!$CI->db->table_exists($messages_table)) {
        log_message('error', "Table $messages_table does not exist, skipping fixingfiles.");
        return; // Ασφαλής έξοδος
    }

    // Πάρε όλα τα αρχεία που έχουν URL
    $usedFilenames = array_column(
        $CI->db->select('url')
               ->where('url !=', '')
               ->get($messages_table)
               ->result_array(),
        'url'
    );

    // Πάρε όλα τα μηνύματα με URL
    $messages = $CI->db->select('id, interaction_id, url, message')
                       ->from($messages_table)
                       ->where('url IS NOT NULL')
                       ->where('url !=', '')
                       ->get()
                       ->result();

    foreach ($messages as $msg) {
        $interaction_id = (int)$msg->interaction_id;
        $message_id     = $msg->id;
        $original_file  = trim($msg->url);
        $message_body   = $msg->message;

        if ($interaction_id <= 0 || empty($original_file)) continue;

        // Φάκελος για κάθε interaction
        $interactionDir = $interactionsPath . $interaction_id . '/';
        create_directory_if_not_exists($interactionDir);

        $sourcePath = $basePath . $original_file;
        $destPath   = $interactionDir . basename($original_file);

        if (file_exists($sourcePath) && !file_exists($destPath)) {
            if (@copy($sourcePath, $destPath)) {
                $CI->db->where('id', $message_id)->update($messages_table, ['url' => basename($destPath)]);
            } else {
                log_message('error', "Failed to copy $sourcePath to $destPath");
            }
        }

        // Αντικατάσταση URLs μέσα στο μήνυμα
        preg_match_all(
            '/https?:\/\/[^\/]+\/uploads\/whatsapp\/(bot_files|campaign|template|numbers)\/([^\s\)"\']+)/i',
            $message_body,
            $matches
        );
        $replacements = [];

        foreach ($matches[2] as $index => $filename) {
            $folder = $matches[1][$index];
            $src = $basePath . $folder . '/' . $filename;

            if (!file_exists($src)) continue;

            $dest = $interactionDir . $filename;
            if (!file_exists($dest) && @copy($src, $dest)) {
                $new_url = base_url(WHATSAPP_MODULE_UPLOAD_FOLDER . '/interactions/' . $interaction_id . '/' . $filename);
                $replacements[$filename] = $new_url;
            }
        }

        if (!empty($replacements)) {
            foreach ($replacements as $old => $new) {
                $message_body = str_replace($old, $new, $message_body);
            }
            $CI->db->where('id', $message_id)->update($messages_table, ['message' => $message_body]);
        }

        // Δημιουργία index.html για προστασία φακέλου
        $index = $interactionDir . 'index.html';
        if (!file_exists($index)) {
            file_put_contents($index, '<!-- Protected -->');
        }
    }
}

