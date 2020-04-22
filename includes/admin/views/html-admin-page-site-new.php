<div class="wrap" id="poc-site-new">
    <h1>Create site</h1>
    <?php settings_errors(); ?>
    <?php if ( $reach_limit ) : ?>
        <div class="notice notice-error">
            <p><strong>You have reached multisite limit.</strong> The following site will be mark as <strong>INACTIVE</strong>. That's mean people won't be able to visit this site. Please upgrade your current plan so you can create more site.</p>
            <p>
                <a href="" class="button-primary">Upgrade now</a> <a href="" class="button">Learn more</a>
            </p>
        </div>
    <?php endif; ?>
    <form action="" method="POST">
        <h2>User information</h2>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="">Username</label>
                    </th>
                    <td>
                        <input type="text" class="regular-text" name="user[username]">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="">Password</label>
                    </th>
                    <td>
                        <input type="password" class="regular-text" name="user[password]">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="">Name</label>
                    </th>
                    <td>
                        <input type="text" class="regular-text" name="user[name]">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="">Phone number</label>
                    </th>
                    <td>
                        <input type="number" name="user[phone_number]">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="">Email</label>
                    </th>
                    <td>
                        <input type="text" class="regular-text" name="user[email]">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="">Avatar</label>
                    </th>
                    <td>
                        <input type="hidden" class="regular-text" name="user[avatar]" id="poc-user-avatar">
                        <a href="" class="button" id="poc-upload-avatar">Add Image</a>
                        <img src="" width="64" alt="" id="poc-user-avatar-preview" style="display: none; margin-top: 10px">
                    </td>
                </tr>
            </tbody>
        </table>
        <h2>Site information</h2>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="">Site name</label>
                    </th>
                    <td>
                        <input type="text" class="regular-text" name="site[title]">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="">Site domain</label>
                    </th>
                    <td>
                        <input type="text" class="regular-text" name="site[domain]">
                        <span class="nobreak"><span class="no-break">.<?php echo preg_replace( '|^www\.|', '', get_network()->domain ); ?></span>
                        <p class="description" id="site-address-desc"><?php echo __( 'Only lowercase letters (a-z), numbers, and hyphens are allowed.' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="">Status</label>
                    </th>
                    <td>
                        <select name="site[status]" id="">
                            <?php if ( ! $reach_limit ) : ?>
                                <option value="active">Active</option>
                            <?php endif; ?>
                            <option value="inactive">Inactive</option>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>
        <input type="hidden" name="action" value="poc-pro-create-site">
        <?php
            wp_nonce_field( 'create-site', '_wpnonce-create-site' );
            submit_button( 'Create Site' );
        ?>
    </form>
</div>