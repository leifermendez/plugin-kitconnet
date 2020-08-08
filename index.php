<?php
/**
 * Plugin Name: Kitagil (Inventario Gratis) 
 * Plugin URI: https://kitagil.com/
 * Description: 🚀 (Gratis) Sincroniza con tu herramienta online que facilitara la gestión de inventarios, facturación, logística y más opciones que puedes activar con un click.
 * Version: 1.0
 * Author: Leifer Mendez
 * Author URI: https://github.com/leifermendez
 * License: A "Slug" license name e.g. GPL12
 */

require_once(__DIR__ . '/class/ConnectSettings.php');

class ConnectMainClass extends ConnectSettings{
    public function __construct(){
        global $wpdb;

        parent::__construct(
            $wpdb
        );

        if (is_admin()) {
            register_activation_hook(__FILE__, array($this, 'registerActivation'));
            register_deactivation_hook(__FILE__, array($this, 'dropSettingTable'));
        }

        add_action('admin_enqueue_scripts', array($this, 'admin_assets'));

        add_action('admin_menu', array($this, 'AdminPage'));

        add_action('wp_ajax_save_token_api', array($this, 'saveTokenApi'));

        add_action('wp_ajax_sync_all_products_kitalig', array($this, 'syncAllProducts'));

        add_action('admin_notices', array( $this, 'pluginActivation')); 
        
    }

    /**
     * *************************
     * ** Activation Plugin ****
     * *************************
     */

    public function registerActivation()
    {
        parent::checkWC();
        parent::installDB();
    }

    public function dropSettingTable()
    {
        parent::dropTableSetting();
    }

    /**
     * *************************
     * ** Admin Panel       ****
     * *************************
     */

    public function AdminPage()
    {
        add_menu_page(
            null,
            '🚀 Kitagil',
            'manage_options',
            'page-admin',
            function () {
                parent::adminPage();
            },
            'dashicons-update-alt'
        );
    }

    public function pluginActivation(){

        $html = '<div class="updated">';
            $html .= '<p>';
                $html .= __( '🚀 <a href="https://kitagil.com/" target="_blank"><b>KITAGIL.com</b></a> Recuerda puedes obtener un token de acceso, <a target="_blank" href="https://help.kitagil.com/"><b>click</b></a>. Si no tienes cuenta regístrate <b>GRATIS!</b>' );
            $html .= '</p>';
        $html .= '</div><!-- /.updated -->';

        echo $html;
    }

    /**
     * **************************
     * ** General functions *****
     * **************************
    */

    public function saveTokenApi($data = array())
    {
        parent::saveTokenApi($_POST);
    }

    public function syncAllProducts()
    {
        parent::syncAllProductsKitagil();
    }
    

    public function admin_assets()
    {
        wp_register_style('style_css_connect_bootstrap', plugin_dir_url(__FILE__) . 'assets/css/bootstrap.min.css');
        wp_enqueue_style('style_css_connect_bootstrap');

        wp_register_style('style_css_kitagil_connect', plugin_dir_url(__FILE__) . 'assets/css/kitagil_connect.css');
        wp_enqueue_style('style_css_kitagil_connect');

        wp_enqueue_script('js_kitagil_connect', plugin_dir_url(__FILE__) . 'assets/js/connect.js', array('jquery'));

    }
}

new ConnectMainClass();
?>