<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * App_Module - Base class for Perfex modules
 * Compatibilité pour les modules qui étendent App_Module
 */
class App_Module extends CI_Controller
{
    protected $module_name;
    protected $module_version;
    
    public function __construct()
    {
        parent::__construct();
        $this->load->library('migration');
    }
    
    public function install()
    {
        // Méthode d'installation par défaut
        log_message('info', 'Module ' . $this->module_name . ' installed');
        return true;
    }
    
    public function uninstall()
    {
        // Méthode de désinstallation par défaut
        log_message('info', 'Module ' . $this->module_name . ' uninstalled');
        return true;
    }
    
    public function upgrade_database()
    {
        // Méthode de mise à jour par défaut
        if (method_exists($this->migration, 'set_module')) {
            $this->migration->set_module($this->module_name);
            return $this->migration->current();
        }
        return true;
    }
    
    public function activate()
    {
        return true;
    }
    
    public function deactivate()
    {
        return true;
    }
}
