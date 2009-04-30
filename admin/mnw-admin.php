<?php
/**
 * This file is part of mnw.
 *
 * mnw - an OpenMicroBlogging compatible Microblogging plugin for Wordpress
 * Copyright (C) 2009, Adrian Lang
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

add_action('admin_menu', 'mnw_admin_menu_setup');
function mnw_admin_menu_setup() {
    $dir = dirname(__FILE__) . '/';
    add_menu_page(__('Microblog', 'mnw'), __('Microblog', 'mnw'),
                        MNW_ACCESS_LEVEL, __FILE__);
    /* The first submenu entry must link to __FILE__. */
    foreach(array(array(__('General microblog settings', 'mnw'), __('Settings',
                        'mnw'), 'mnw-admin.php', 'mnw_plugin_options'),
                  array(__('Microblog profile settings', 'mnw'), __('Profile',
                        'mnw'), 'mnw-admin-profile.php'),
                  array(__('Remote microblog users', 'mnw'), __('Remote users',
                        'mnw'), 'mnw-admin-remote-users.php'),
                  array(__('Microblog notices', 'mnw'), __('Notices',
                        'mnw'), 'mnw-admin-notices.php'))
         as $submenu) {
        add_submenu_page(__FILE__, $submenu[0], $submenu[1], MNW_ACCESS_LEVEL,
                            $dir . $submenu[2], $submenu[3]);
    }

}

add_action('wp_dashboard_setup', 'mnw_dashboard_setup');
function mnw_dashboard_setup() {
    wp_add_dashboard_widget('mnw_dashboard', __('Microblog', 'mnw'),
                                                    'mnw_dashboard_wrap');
}

function mnw_dashboard_wrap() {
    require_once 'mnw-admin-dashboard.php';
}

/* Library functions for admin pages. */
function mnw_start_admin_page() {
?>
<div class="wrap">
    <h2><?php _e('Microblog', 'mnw'); ?></h2>
<?php
}

function mnw_finish_admin_page() {
?>
    <p style="color: grey; text-align: right;">
        <?php printf(__('mnw version %s', 'mnw'), MNW_VERSION); ?>
    </p>
</div>
<?php
}

function mnw_plugin_options() {
    global $mnw_options;
    mnw_start_admin_page();
?>
    <form method="post" action="options.php">
        <?php wp_nonce_field('update-options'); ?>

        <h3><?php _e('General settings', 'mnw'); ?></h3>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('Wordpress microblog page URL', 'mnw'); ?></th>
                <td>
                    <input type="text" class="regular-text" name="mnw_themepage_url" value="<?php echo get_option('mnw_themepage_url'); ?>" /><br />
                    <?php _e('URL of a wordpress page which uses mnw.php as template. All public URLs are based on this URL; you should never change it.', 'mnw'); ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Redirect target after successful subscription.', 'mnw'); ?></th>
                <td><input type="text" class="regular-text" name="mnw_after_subscribe" value="<?php echo get_option('mnw_after_subscribe'); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Post template', 'mnw'); ?></th>
                <td><input type="text" class="regular-text" name="mnw_post_template" value="<?php echo get_option('mnw_post_template'); ?>" /><br />
                    <?php _e('Template used to generate microblog posts on blog post or page publication.<br />
                    You may use the following placeholders:<br />
                    %n: title of the post or page<br />
                    %u: url of the post or page<br />
                    %e: excerpt of the post or page<br />
                    %c: content of the post or page', 'mnw'); ?></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Send a microblog notice when a post is published', 'mnw'); ?></th>
                <td><input type="checkbox" name="mnw_on_post" <?php if (get_option('mnw_on_post')) echo 'checked="checked"'; ?> /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Send a microblog notice when a page is published', 'mnw'); ?></th>
                <td><input type="checkbox" name="mnw_on_page" <?php if (get_option('mnw_on_page')) echo 'checked="checked"'; ?> /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Send a microblog notice when an attachment is published', 'mnw'); ?></th>
                <td><input type="checkbox" name="mnw_on_attachment" <?php if (get_option('mnw_on_attachment')) echo 'checked="checked"'; ?> /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Forward user to blog post/page/attachment', 'mnw'); ?></th>
                <td><input type="checkbox" name="mnw_forward_to_object" <?php if (get_option('mnw_forward_to_object')) echo 'checked="checked"'; ?> /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Send post/page/attachment URL as seealso', 'mnw'); ?></th>
                <td><input type="checkbox" name="mnw_as_seealso" <?php if (get_option('mnw_as_seealso')) echo 'checked="checked"'; ?> /></td>
            </tr>


<!--            <tr valign="top">
                <th scope="row"><?php _e('Mirror subscription', 'mnw'); ?></th>
                <td>
                    <input type="checkbox" name="mnw_mirror_subscription" <?php if (get_option('mnw_mirror_subscription')) echo 'checked="checked"'; ?> /><br />
                    <?php _e('Automatically subscribe to a subscriber, automatically unsubscribe if the other user unsubscribes.', 'mnw'); ?>
                </td>
            </tr>-->
        </table>
        <input type="hidden" name="action" value="update" />
        <input type="hidden" name="page_options" value="mnw_after_subscribe, mnw_themepage_url, mnw_post_template, mnw_on_post, mnw_on_page, mnw_on_attachment, mnw_forward_to_object, mnw_as_seealso" />
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
        </p>
    </form>
<?php
    mnw_finish_admin_page();
}
?>
