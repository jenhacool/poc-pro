<?php

class POC_Pro_Admin_Menu
{
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }

    public function admin_menu()
    {
        // Only super admin can see these links
        if ( ! current_user_can( 'manage_network ' ) ) {
            return;
        }

        add_menu_page(
            'POC Pro',
            'POC Pro',
            'manage_network',
            'poc_pro',
            array( $this, 'poc_pro_page' )
        );

        add_submenu_page(
            'poc_pro',
            'Create site',
            'Create site',
            'manage_network',
            'poc_pro_create_site',
            array( $this, 'poc_pro_create_site_page' )
        );
    }

    public function poc_pro_page()
    {
        echo 'POC Pro';
    }

    public function poc_pro_create_site_page()
    {
        $site_new = new POC_Pro_Admin_Site_New();
        return $site_new->output();
    }
}

new POC_Pro_Admin_Menu();