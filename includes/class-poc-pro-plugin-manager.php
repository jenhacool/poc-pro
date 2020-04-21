<?php

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

class POC_Pro_Plugin_Manager
{
    protected $api;

    public function __construct()
    {
        $this->api = new POC_Pro_API();
    }

    public function installs( $plugins )
    {
        foreach ( $plugins as $plugin ) {
            $this->install_plugin( $plugin['download_link'] );
            $this->activate_plugin( $plugin['main_file_path'] );
        }
    }

    public function get_missing_plugins()
    {
        $missing_plugins = array();

        foreach ( $this->get_required_plugins() as $plugin ) {
            if( ! is_plugin_active( $plugin['main_file_path'] ) ) {
                $missing_plugins[] = $plugin;
            }
        }

        return $missing_plugins;
    }

    public function get_suggested_plugins()
    {
        $suggest_plugins = get_transient( 'poc_pro_suggested_plugins' );

        if( $suggest_plugins === false ) {
            $suggest_plugins = $this->api->get_suggested_plugins();
            set_transient( 'poc_pro_suggested_plugins', $suggest_plugins, 12 * HOUR_IN_SECONDS );
        }

        if( is_null( $suggest_plugins ) || empty( $suggest_plugins ) ) {
            return array();
        }

        $plugins_to_install = array();

        foreach ( $suggest_plugins as $plugin ) {
            if( ! is_plugin_active( $plugin['main_file'] ) ) {
                $plugins_to_install[] = $plugin;
            }
        }

        return $plugins_to_install;
    }

    protected function get_required_plugins()
    {
        return array(
            array(
                'main_file_path' => 'elementor/elementor.php',
                'name' => 'Elementor',
                'slug' => 'elementor',
                'download_link' => 'https://downloads.wordpress.org/plugin/elementor.2.9.7.zip'
            ),
            array(
                'main_file_path' => 'classic-editor/classic-editor.php',
                'name' => 'Classic Editor',
                'slug' => 'classic-editor',
                'download_link' => 'https://downloads.wordpress.org/plugin/classic-editor.1.5.zip'
            ),
        );
    }

    protected function get_upgrader()
    {
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        include_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
        include_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';

        $skin = new WP_Ajax_Upgrader_Skin();

        return new Plugin_Upgrader( $skin );
    }

    protected function install_plugin( $download_link )
    {
        $this->get_upgrader()->install( $download_link );
    }

    protected function activate_plugin( $main_file )
    {
        activate_plugin( $main_file, '', true );
    }
}