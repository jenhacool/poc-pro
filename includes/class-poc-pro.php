<?php

class POC_PRO
{
    protected static $instance = null;

    protected $version = '1.0.0';

    protected $plugin_manager;

    protected $plugin_info;

    protected $plugin_slug = 'poc-pro';

    protected $api;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->define_constants();
        $this->includes();

        $this->plugin_manager = new POC_Pro_Plugin_Manager();
        $this->api = new POC_Pro_API();

        $this->init_hooks();
    }

    private function define_constants()
    {
        $this->define( 'POC_PRO_ABSPATH', dirname( POC_PRO_PLUGIN_FILE ) . '/' );
        $this->define( 'POC_PRO_PLUGIN_URL', untrailingslashit( plugins_url( '/', POC_PRO_PLUGIN_FILE ) ) );
        $this->define( 'POC_PRO_PLUGIN_VERSION', $this->version );
        $this->define( 'POC_PRO_PLUGIN_BASENAME', plugin_basename( POC_PRO_PLUGIN_FILE ) );
    }

    private function includes()
    {
        include_once POC_PRO_ABSPATH . 'includes/class-poc-pro-api.php';
        include_once POC_PRO_ABSPATH . 'includes/class-poc-pro-plugin-manager.php';
        include_once POC_PRO_ABSPATH . 'includes/admin/sites/class-poc-pro-admin-site-new.php';
        include_once POC_PRO_ABSPATH . 'includes/admin/class-poc-pro-admin-menu.php';
    }

    private function init_hooks()
    {
        add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );
        add_filter( 'get_avatar', array( $this, 'get_custom_avatar' ), 10, 5 );
        add_action( 'admin_init', array( $this, 'on_admin_init' ) );
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_version' ) );
        add_filter( 'plugins_api', array( $this, 'set_plugin_info' ), 10, 3 );
    }

    public function check_version( $transient )
    {
        if( ! isset( $transient->response ) ) {
            return $transient;
        }

        $new_version = POC_PRO_PLUGIN_VERSION;

        if( ! empty( $transient->response[POC_PRO_PLUGIN_BASENAME]->new_version ) ) {
            $new_version = $transient->response[POC_PRO_PLUGIN_BASENAME]->new_version;
        }

        if( is_null( $this->get_plugin_info() ) ) {
            return $transient;
        }

        if( ! version_compare( $this->plugin_info['version'], $new_version, '>' ) ) {
            return $transient;
        }

        $obj = new stdClass();
        $obj->slug = $this->plugin_slug;
        $obj->new_version = $this->plugin_info['version'];
        $obj->url = 'http://poc.foundation';
        $obj->package = $this->plugin_info['download_link'];
        $obj->plugin = POC_PRO_PLUGIN_BASENAME;

        $transient->response[POC_PRO_PLUGIN_BASENAME] = $obj;

        return $transient;
    }

    public function set_plugin_info( $result, $action, $response )
    {
        if( empty( $response->slug ) || $response->slug != $this->plugin_slug ) {
            return false;
        }

//        $response->last_updated = $this->githubAPIResult->published_at;
        $response->slug = $this->plugin_slug;
        $response->name  = 'POC Pro';
        $response->version = $this->get_plugin_info()['version'];
        $response->author = 'POC Foundation';
        $response->homepage = 'http://poc.foundation';

        $response->download_link = $this->get_plugin_info()['download_link'];

        $response->sections = array(
            'description' => 'description',
        );

        return $response;
    }

    public function get_plugin_info()
    {
        if( ! empty( $this->plugin_info ) ) {
            return $this->plugin_info;
        }

        return $this->plugin_info = $this->api->check_version();
    }

    public function on_plugins_loaded()
    {
        if( ! is_user_logged_in() || ! current_user_can( 'manage_network' ) ) {
            return;
        }

        $this->check_required_plugins();

        $this->check_suggested_plugins();

        $this->check_plugin_version();
    }

    protected function check_required_plugins()
    {
        $messages = array();

        $missing_plugins = $this->plugin_manager->get_missing_plugins();

        if( empty( $missing_plugins ) ) {
            return;
        }

        foreach ( $missing_plugins as $missing_plugin ) {
            $messages[] = '<em><a href="https://wordpress.org/plugins/' . $missing_plugin['slug'] . '/" target="_blank">' . $missing_plugin['name'] . '</a></em>';
        }

        add_action( 'admin_notices', function() use ( $messages, $missing_plugins ) {
            ob_start(); ?>
            <div class="error is-dismissible">
                <p>
                    <strong>
                        <?php echo sprintf( esc_html__('POC Pro requires the following plugins to be installed and active: %s', 'poc-pro' ), implode( ', ', $messages ) ); ?>
                    </strong>
                </p>
                <form action="" method="POST">
                    <?php wp_nonce_field( 'poc_pro_install_plugins', 'poc_pro_install_plugins' ); ?>
                    <?php foreach ( $missing_plugins as $index => $plugin ) : ?>
                        <input type="hidden" name="<?php echo 'plugins['.$index.'][download_link]'; ?>" value="<?php echo $plugin['download_link']; ?>" />
                        <input type="hidden" name="<?php echo 'plugins['.$index.'][main_file_path]'; ?>" value="<?php echo $plugin['main_file_path']; ?>" />
                    <?php endforeach; ?>
                    <p><input class="button button-primary" type="submit" value="Install & Activate Plugins"></p>
                </form>
            </div>
            <?php echo ob_get_clean();
        });
    }

    protected function check_suggested_plugins()
    {
        $messages = array();

        $suggested_plugins = $this->plugin_manager->get_suggested_plugins();

        if( empty( $suggested_plugins ) ) {
            return;
        }

        foreach ( $suggested_plugins as $suggested_plugin ) {
            $messages[] = '<em>' . $suggested_plugin['name'] . '</em>';
        }

        add_action( 'admin_notices', function() use ( $messages, $suggested_plugins ) {
            ob_start(); ?>
            <div class="error is-dismissible">
                <p>
                    <strong>
                        <?php echo sprintf( esc_html__('POC Pro requires the following plugins to be installed and active: %s', 'poc-pro' ), implode( ', ', $messages ) ); ?>
                    </strong>
                </p>
                <form action="" method="POST">
                    <?php wp_nonce_field( 'poc_pro_install_plugins', 'poc_pro_install_plugins' ); ?>
                    <?php foreach ( $suggested_plugins as $index => $plugin ) : ?>
                        <input type="hidden" name="<?php echo 'plugins['.$index.'][download_link]'; ?>" value="<?php echo $plugin['download_link']; ?>" />
                        <input type="hidden" name="<?php echo 'plugins['.$index.'][main_file_path]'; ?>" value="<?php echo $plugin['main_file']; ?>" />
                    <?php endforeach; ?>
                    <p><input class="button button-primary" type="submit" value="Install & Activate Plugins"></p>
                </form>
            </div>
            <?php echo ob_get_clean();
        });
    }

    protected function check_plugin_version()
    {
        $transient = get_site_transient( 'update_plugins' );

        if( ! isset( $transient->response ) || empty( $transient->response ) || ! isset( $transient->response[POC_PRO_PLUGIN_BASENAME] ) ) {
            return;
        }

        add_action( 'admin_notices', function() {
            ob_start(); ?>
            <div class="error is-dismissible">
                <p>
                    <strong>
                        <?php echo __( 'POC Pro have new version. Please update to use new features' ); ?>
                    </strong>
                </p>
                <p><a href="<?php echo network_admin_url('update-core.php') ?>" class="button button-primary">Update</a></p>
            </div>
            <?php echo ob_get_clean();
        });
    }

    public function on_admin_init()
    {
        if ( isset( $_POST['poc_pro_install_plugins'] ) && wp_verify_nonce( $_POST['poc_pro_install_plugins'], 'poc_pro_install_plugins' ) ) {
            $plugin_manager = new POC_Pro_Plugin_Manager();

            if( ! isset( $_POST['plugins'] ) || empty( $_POST['plugins'] ) ) {
                return;
            }

            $plugin_manager->installs( $_POST['plugins'] );

            header("Location: http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
            exit;
        }

        if ( isset( $_POST['poc_pro_import_templates'] ) && wp_verify_nonce( $_POST['poc_pro_import_templates'], 'poc_pro_import_templates' ) ) {
            include_once ABSPATH . 'wp-content/plugins/elementor/includes/plugin.php';

            $plugin = Elementor\Plugin::instance();

            $poc_api   = new POC_Pro_API();
            $templates = $poc_api->get_templates();

            foreach ($templates as $template) {
                $file_content = file_get_contents( $template['download_link'] );
                $result = $plugin->templates_manager->import_template([
                        'fileData' => base64_encode($file_content),
                        'fileName' => $template['name'].'.json',
                    ]
                );
            }

            header("Location: http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
            exit;
        } else {
            $this->check_elementor_templates();
        }
    }

    protected function check_elementor_templates()
    {
        if( is_plugin_active( 'elementor/elementor.php' ) ) {
            include_once ABSPATH.'wp-content/plugins/elementor/includes/plugin.php';

            $plugin = Elementor\Plugin::instance();

            $templates = $plugin->templates_manager->get_source('local')->get_items();

            $index = array_search('typhu.online1', array_column($templates, 'title'));

            if ($index === false) {
                add_action('admin_notices', function () {
                    ob_start(); ?>
                    <div class="error is-dismissible">
                        <p>
                            <strong>
                                <?php echo __('POC Pro requires the template files to be imported', 'poc-pro'); ?>
                            </strong>
                        </p>
                        <form action="" method="POST">
                            <?php wp_nonce_field('poc_pro_import_templates', 'poc_pro_import_templates'); ?>
                            <p><input class="button button-primary" type="submit" value="Import templates"></p>
                        </form>
                    </div>
                    <?php echo ob_get_clean();
                });
            }
        }
    }

    public function get_custom_avatar( $avatar, $id_or_email, $size, $default, $alt )
    {
        if( ! is_numeric( $id_or_email ) && is_email( $id_or_email ) ) {
            $user = get_user_by( 'email', $id_or_email );
            if( $user ) {
                $id_or_email = $user->ID;
            }
        }

        if( ! is_numeric( $id_or_email ) ) {
            return $avatar;
        }

        $poc_user_avatar = get_user_meta( $id_or_email, 'poc_user_avatar', true );

        if( ! filter_var( $poc_user_avatar, FILTER_VALIDATE_URL ) ) {
            return $avatar;
        }

        return sprintf( '<img src="%1s" width="32" height="32" />', esc_url( $poc_user_avatar ) );
    }

    private function define($name, $value) {
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }
}