<?php
/**
 * Bootstrap pour les tests PHPUnit
 * Module Ebilling + GPSWOX pour Perfex CRM
 */

// Définir l'environnement de test
define('ENVIRONMENT', 'testing');
define('BASEPATH', TRUE);

// Chemin vers le répertoire racine de Perfex
// À adapter selon votre installation
$perfex_root = dirname(dirname(dirname(__DIR__)));

// Charger les fichiers de base de CodeIgniter/Perfex si nécessaire
// require_once $perfex_root . '/application/config/constants.php';

// Autoloader Composer (si utilisé)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

/**
 * Classe de base pour les tests CodeIgniter/Perfex
 */
class CI_TestCase extends PHPUnit\Framework\TestCase
{
    protected $CI;

    public function setUp(): void
    {
        parent::setUp();

        // Initialiser l'instance CodeIgniter mock
        $this->CI = new stdClass();
        $this->CI->load = new CI_Loader_Mock();
        $this->CI->db = new CI_DB_Mock();
        $this->CI->input = new CI_Input_Mock();
        $this->CI->output = new CI_Output_Mock();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        // Nettoyer les mocks
        if (class_exists('Mockery')) {
            Mockery::close();
        }
    }
}

/**
 * Mock basique du Loader CodeIgniter
 */
class CI_Loader_Mock
{
    public function model($model, $alias = '', $db_conn = FALSE)
    {
        // Mock de chargement de modèle
        return TRUE;
    }

    public function library($library, $params = NULL, $object_name = NULL)
    {
        return TRUE;
    }

    public function helper($helper)
    {
        return TRUE;
    }
}

/**
 * Mock basique de la base de données
 */
class CI_DB_Mock
{
    public function table_exists($table)
    {
        return FALSE;
    }

    public function query($sql)
    {
        return TRUE;
    }

    public function get_where($table, $where)
    {
        return $this;
    }

    public function row()
    {
        return new stdClass();
    }

    public function result()
    {
        return [];
    }

    public function count_all_results($table)
    {
        return 0;
    }

    public function where($key, $value = NULL)
    {
        return $this;
    }

    public function insert($table, $data)
    {
        return TRUE;
    }

    public function update($table, $data)
    {
        return TRUE;
    }

    public function insert_id()
    {
        return 1;
    }
}

/**
 * Mock basique de Input
 */
class CI_Input_Mock
{
    public function post($key = NULL, $xss_clean = NULL)
    {
        return $_POST[$key] ?? NULL;
    }

    public function get($key = NULL, $xss_clean = NULL)
    {
        return $_GET[$key] ?? NULL;
    }

    public function get_request_header($header, $xss_clean = NULL)
    {
        $header = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
        return $_SERVER[$header] ?? NULL;
    }

    public function ip_address()
    {
        return '127.0.0.1';
    }
}

/**
 * Mock basique de Output
 */
class CI_Output_Mock
{
    public function set_status_header($code)
    {
        http_response_code($code);
    }
}

/**
 * Fonctions helper globales pour les tests
 */
if (!function_exists('get_instance')) {
    function &get_instance()
    {
        static $instance;
        if (!$instance) {
            $instance = new stdClass();
        }
        return $instance;
    }
}

if (!function_exists('db_prefix')) {
    function db_prefix()
    {
        return 'tbl';
    }
}

if (!function_exists('get_option')) {
    function get_option($key)
    {
        // Mock - retourner des valeurs de test
        $options = [
            'eb_base_url' => 'https://lab.billing-easy.net',
            'eb_environment' => 'lab',
            'gpswox_base_url' => 'https://app.mbiratracker.com',
        ];
        return $options[$key] ?? '';
    }
}

if (!function_exists('ebg_log')) {
    function ebg_log($message, $context = [])
    {
        // Mock de logging
        return TRUE;
    }
}

if (!function_exists('log_activity')) {
    function log_activity($message)
    {
        return TRUE;
    }
}

if (!function_exists('site_url')) {
    function site_url($uri = '')
    {
        return 'https://www.mbiratracker.com/espaceclient/' . $uri;
    }
}
