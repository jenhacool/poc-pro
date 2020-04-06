<div class="wrap">
    <h1>Install Required Plugins</h1>
    <?php if ( $error ) : ?>
        <p>Something wrong happen. Please contact our support team.</p>
    <?php else : ?>
        <?php if( empty( $missing_plugins ) ) : ?>
            <div class="notice notice-success settings-error is-dismissible">
                <p>All required plugins are installed and active</p>
            </div>
        <?php else : ?>
            <form action="" method="POST" id="poc_pro_install_plugins_table">
                <?php wp_nonce_field( 'poc_pro_install_plugins', 'poc_pro_install_plugins' ); ?>
                <table class="widefat" style="margin-bottom: 15px;">
                    <tbody>
                        <?php foreach ( $missing_plugins as $index => $plugin ) : ?>
                            <tr>
                                <td>
                                    <input type="hidden" name="<?php echo 'plugins['.$index.'][download_link]'; ?>" value="<?php echo $plugin['plugin_information']->download_link; ?>">
                                    <input type="hidden" name="<?php echo 'plugins['.$index.'][main_file_path]'; ?>" value="<?php echo $plugin['main_file_path']; ?>">
                                    <img width="36" class="plugin_icon" style="float: left; margin-right: 10px; margin-top: 4px;" src="<?php echo $plugin['plugin_information']->icons['1x']; ?>" />
                                    <div class="plugin_info">
                                        <span><strong><?php echo $plugin['plugin_information']->name; ?></strong></span><br>
                                        <span><?php echo $plugin['plugin_information']->short_description; ?></span><br>
                                        <span>By <?php echo $plugin['plugin_information']->author; ?> | <a href="<?php echo 'https://wordpress.org/plugins/' . $plugin['plugin_information']->slug .  '/'; ?>" target="_blank">View details</a></span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <input class="button" type="submit" value="Install & Active Plugins">
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>