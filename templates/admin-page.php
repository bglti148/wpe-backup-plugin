<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors('wpengine_messages'); ?>

    <form method="post" action="options.php">
        <?php
        settings_fields('wpengine_backup_options');
        do_settings_sections('wpengine_backup_options');
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('WP Engine User ID', 'wpengine-backup-plugin'); ?></th>
                <td><input type="text" name="wpengine_user_id" value="<?php echo esc_attr(get_option('wpengine_user_id')); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('WP Engine Password', 'wpengine-backup-plugin'); ?></th>
                <td><input type="password" name="wpengine_password" value="<?php echo esc_attr(get_option('wpengine_password')); ?>" /></td>
            </tr>
        </table>
        <?php submit_button(__('Save Credentials', 'wpengine-backup-plugin')); ?>
    </form>

    <?php if ($this->backup->validate_credentials()): ?>
        <?php if ($install_id): ?>
            <h2><?php _e('Trigger Backup', 'wpengine-backup-plugin'); ?></h2>
            <p><?php printf(__('Create WP Engine backup for %s', 'wpengine-backup-plugin'), $site_url); ?></p>
            <form method="post" action="">
                <?php wp_nonce_field('wpengine_backup_trigger', 'wpengine_backup_nonce'); ?>
                <p><input type="submit" name="trigger_backup" class="button button-primary" value="<?php _e('Trigger Backup', 'wpengine-backup-plugin'); ?>" /></p>
            </form>
        <?php else: ?>
            <div class="notice notice-error">
                <p><?php _e('Unable to determine the current install ID. Please check your WP Engine API credentials.', 'wpengine-backup-plugin'); ?></p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="notice notice-error">
            <p><?php _e('Please enter valid WP Engine API credentials.', 'wpengine-backup-plugin'); ?></p>
        </div>
    <?php endif; ?>
</div>