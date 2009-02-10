<?php

$mnw_options = array("omb_full_name", "omb_nickname", "omb_license", "omb_bio", "omb_location", "omb_avatar");
foreach ($mnw_options as $option_name) {
    add_action( "update_option_{$option_name}", 'mnw_upd_settings');
}

function mnw_upd_settings() {
}

add_action('admin_menu', 'mnw_admin_menu');

function mnw_admin_menu() {
  add_options_page('mnw Options', 'mnw', 8, __FILE__, 'mnw_admin_options');
}

function mnw_admin_options() {
    global $mnw_options;
?>
<div class="wrap">
    <h2>mnw</h2>
    <form method="post" action="options.php">
        <?php wp_nonce_field('update-options'); ?>
        <h3>OMB profile</h3>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Full name</th>
                <td><input type="text" name="omb_full_name" value="<?php echo get_option('omb_full_name'); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Nickname</th>
                <td><input type="text" name="omb_nickname" value="<?php echo get_option('omb_nickname'); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Bio</th>
                <td><textarea cols="40" rows="3" name="omb_bio"><?php echo get_option('omb_bio'); ?></textarea></td>
            </tr>
            <tr valign="top">
                <th scope="row">Location</th>
                <td><input type="text" name="omb_location" value="<?php echo get_option('omb_location'); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Avatar URL</th>
                <td><input type="text" name="omb_avatar" value="<?php echo get_option('omb_avatar'); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">License URL</th>
                <td><input type="text" name="omb_license" value="<?php echo get_option('omb_license'); ?>" /></td>
            </tr>
        </table>
        <input type="hidden" name="action" value="update" />
        <input type="hidden" name="page_options" value="<?php echo join(",", $mnw_options); ?>" />
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
        </p>
    </form>
</div>
<?php
}
?>
