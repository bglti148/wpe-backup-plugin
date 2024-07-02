<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors('wpengine_messages'); ?>

    <form method="post" action="options.php" id="wpengine-credentials-form">
    <?php
    settings_fields('wpengine_backup_options');
    do_settings_sections('wpengine_backup_options');
    ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><?php _e('WP Engine User ID', 'wpengine-backup-plugin'); ?></th>
            <td>
                <input type="text" name="wpengine_user_id" id="wpengine_user_id" value="<?php echo esc_attr(get_option('wpengine_user_id')); ?>" style="width: 375px;" />
                <span id="credentials-status"></span>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php _e('WP Engine Password', 'wpengine-backup-plugin'); ?></th>
            <td><input type="password" name="wpengine_password" id="wpengine_password" value="<?php echo esc_attr(get_option('wpengine_password')); ?>" style="width: 375px;" /></td>
        </tr>
    </table>
    <?php submit_button(__('Save Credentials', 'wpengine-backup-plugin'), 'primary', 'submit', true, array('id' => 'save-credentials')); ?>
</form>

<script>
jQuery(document).ready(function($) {
    $('#wpengine-credentials-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: form.serialize() + '&action=validate_wpengine_credentials',
            success: function(response) {
                if (response.success) {
                    $('#credentials-status').html('&#9989;').css('color', 'green');
                    form.off('submit').submit();
                } else {
                    alert('Invalid credentials. Please check and try again.');
                }
            }
        });
    });
});
</script>

    <?php if ($this->backup->validate_credentials()): ?>
    <?php if ($install_id): ?>
        <h2><?php _e('Trigger Backup', 'wpengine-backup-plugin'); ?></h2>
        <p><?php printf(__('Create WP Engine backup for %s', 'wpengine-backup-plugin'), $site_url); ?></p>
        <form method="post" action="">
            <?php wp_nonce_field('wpengine_backup_trigger', 'wpengine_backup_nonce'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('Backup Description', 'wpengine-backup-plugin'); ?></th>
                    <td>
                        <input type="text" name="backup_description" value="" class="regular-text" />
                        <p class="description"><?php _e('Enter a description for this backup (optional)', 'wpengine-backup-plugin'); ?></p>
                    </td>
                </tr>
            </table>
            <p><input type="submit" name="trigger_backup" class="button button-primary" value="<?php _e('Create Backup', 'wpengine-backup-plugin'); ?>" /></p>        </form>
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