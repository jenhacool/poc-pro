<?php

class POC_PRO
{
    protected static $instance = null;

    protected $version = '1.0.0';

    protected $plugin_manager;

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
    }

    public function on_plugins_loaded()
    {
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
        $version = $this->api->check_version();

        if( is_null( $version ) ) {
            return;
        }

        if( version_compare( POC_PRO_PLUGIN_VERSION, $version['version'] ) != 0 ) {
            $plugins = array(
                array(
                    'download_link' => $version['download_link'],
                    'main_file_path' => 'poc-pro/poc-pro.php'
                )
            );

            add_action( 'admin_notices', function() use ( $plugins ) {
                ob_start(); ?>
                <div class="error is-dismissible">
                    <p>
                        <strong>
                            <?php echo __( 'POC Pro have new version. Please update to use new features' ); ?>
                        </strong>
                    </p>
                    <form action="" method="POST" id="poc_pro_install_plugins_table">
                        <?php wp_nonce_field( 'poc_pro_install_plugins', 'poc_pro_install_plugins' ); ?>
                        <?php foreach ( $plugins as $index => $plugin ) : ?>
                            <input type="hidden" name="<?php echo 'plugins['.$index.'][download_link]'; ?>" value="<?php echo $plugin['download_link']; ?>" />
                            <input type="hidden" name="<?php echo 'plugins['.$index.'][main_file_path]'; ?>" value="<?php echo $plugin['main_file_path']; ?>" />
                        <?php endforeach; ?>
                        <p><input class="button button-primary" type="submit" value="Update"></p>
                    </form>
                </div>
                <?php echo ob_get_clean();
            });
        }
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