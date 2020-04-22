<?php

class POC_Pro_Admin_Site_New
{
    public function output()
    {
        wp_enqueue_media();
        wp_enqueue_script( 'poc-site-new', POC_PRO_PLUGIN_URL . '/assets/js/admin/site-new.js', array( 'jquery' ) );

        $reach_limit = $this->reach_multisite_limit();

        if( $this->is_create_site_request() && $this->has_valid_nonce() && $this->has_permission() ) {
            $this->create_site();
        }

        include_once POC_PRO_ABSPATH . 'includes/admin/views/html-admin-page-site-new.php';
    }

    public function create_site()
    {
        if( ! is_array( $_POST['user'] ) || ! is_array( $_POST['site'] ) ) {
            wp_die( __( 'Can not create site' ) );
        }

        $site_data = $_POST['site'];

        $domain = '';

        $site_data['domain'] = trim( $site_data['domain'] );

        if ( preg_match( '|^([a-zA-Z0-9-])+$|', $site_data['domain'] ) ) {
            $domain = strtolower( $site_data['domain'] );
        }

        if ( empty( $domain ) ) {
            wp_die( __( 'Missing or invalid site address.' ) );
        }

        $title = $site_data['title'];

        $meta = array(
            'public' => ( $site_data['status'] === 'active' && ! $this->reach_multisite_limit() ) ? 1 : 0,
        );

        $newdomain = $domain . '.' . preg_replace( '|^www\.|', '', get_network()->domain );

        $path = get_network()->path;

        $user_data = $_POST['user'];

        if ( isset( $user_data['email'] ) && '' === trim( $user_data['email'] ) ) {
            wp_die( __( 'Missing email address.' ) );
        }

        $email = sanitize_email( $user_data['email'] );

        if ( ! is_email( $email ) ) {
            wp_die( __( 'Invalid email address.' ) );
        }

        $password = 'N/A';
        $user_id  = email_exists( $email );

        if( ! $user_id ) {
            $user_id = username_exists( $domain );

            if ( $user_id ) {
                wp_die( __( 'The domain or path entered conflicts with an existing username.' ) );
            }

            $password = wp_generate_password( 12, false );

            $user_id  = wpmu_create_user( $domain, $password, $email );

            if ( false === $user_id ) {
                wp_die( __( 'There was an error creating the user.' ) );
            }

            add_user_meta( $user_id, 'poc_user_phone_number', $user_data['phone_number'] );
            add_user_meta( $user_id, 'poc_user_avatar', $user_data['avatar'] );
        }

        $id = wp_insert_site( array(
            'domain' => $newdomain,
            'path' => $path,
            'title' => $title,
            'user_id' => $user_id,
            'meta' => $meta,
            'network_id' => get_current_network_id()
        ) );

        if ( ! is_wp_error( $id ) ) {
            if ( ! is_super_admin( $user_id ) && ! get_user_option( 'primary_blog', $user_id ) ) {
                update_user_option( $user_id, 'primary_blog', $id, true );
            }

            $user = new WP_User( $user_id );
            $user->set_role( 'editor' );

            switch_to_blog( $id );

            $page_id = $this->create_landing_page();

            update_blog_option( $id, 'page_on_front', $page_id );

            update_blog_option( $id, 'show_on_front', 'page' );

            restore_current_blog();

            wpmu_welcome_notification( $id, $user_id, $password, $title, array( 'public' => 1 ) );
        }

        add_settings_error(
            'poc-pro-create-site-success',
            esc_attr( 'poc-pro-create-site-success' ),
            __( 'Success', 'poc-pro' ),
            'success'
        );
    }

    private function create_landing_page()
    {
        include_once ABSPATH . 'wp-content/plugins/elementor/includes/plugin.php';

        $plugin = Elementor\Plugin::instance();

        $poc_api   = new POC_Pro_API();
        $templates = $poc_api->get_templates();

        foreach ($templates as $template) {
            $file_content = file_get_contents($template['download_link']);
            $plugin->templates_manager->import_template([
                    'fileData' => base64_encode($file_content),
                    'fileName' => $template['name'].'.json',
                ]
            );
        }

        $templates = $plugin->templates_manager->get_source( 'local' )->get_items();
        $elementor_template = null;

        foreach ( $templates as $template ) {
            if( $template['title'] === 'typhu.online1' ) {
                $elementor_template = $template;
                break;
            }
        }

        if( is_null( $elementor_template ) ) {
            wp_die( __( 'Can not create site' ) );
        }

        $elementor_data = $plugin->templates_manager->get_template_data( array(
            'source' => 'local',
            'template_id' => $elementor_template['template_id'],
            'display' => true,
        ) );

        if( is_wp_error( $elementor_data ) ) {
            wp_die( __( 'Can not create site' ) );
        }

        $document = $plugin->documents->create(
            'wp-page',
            [
                'post_title' => 'Homepage',
                'post_status' => 'publish',
                'post_type' => 'page',
            ]
        );

        $document->save( [
            'elements' => $elementor_data['content'],
        ] );

        $page_id = $document->get_post()->ID;

        add_post_meta( $page_id, '_wp_page_template', 'elementor_canvas' );

        \Elementor\Core\Files\CSS\Post::create( $page_id )->enqueue();

        return $page_id;
    }

    private function is_create_site_request()
    {
        return isset( $_POST['action'] ) && $_POST['action'] === 'poc-pro-create-site';
    }

    private function has_valid_nonce()
    {
        return check_admin_referer( 'create-site', '_wpnonce-create-site' );
    }

    private function has_permission()
    {
        return current_user_can( 'manage_network' );
    }

    private function reach_multisite_limit()
    {
        return ( $this->get_multisite_current_number() >= $this->get_multisite_limit() );
    }

    private function get_multisite_limit()
    {
        $api = new POC_Pro_API();

        $data = $api->get_multisite_limit();

        if( is_null( $data ) || ( isset( $data['message'] ) && $data['message'] === 'Cannot find server' ) ) {
            wp_die( 'Something wrong happen. Please contact supporters' );
        }

        return (int) $data['multisite_limit'];
    }

    private function get_multisite_current_number()
    {
        return count( get_sites() );
    }
}