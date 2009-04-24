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

require_once 'admin_menu_profile.php';
require_once 'admin_menu_notices.php';
require_once 'admin_menu_remote_users.php';

add_action('admin_menu', 'mnw_admin_menu');
function mnw_admin_menu() {
    add_menu_page(__('Microblog', 'mnw'), __('Microblog', 'mnw'), MNW_ACCESS_LEVEL, __FILE__);
    add_submenu_page(__FILE__, __('General microblog settings', 'mnw'), __('Settings', 'mnw'), MNW_ACCESS_LEVEL, __FILE__, 'mnw_plugin_options');
    add_submenu_page(__FILE__, __('Microblog profile settings', 'mnw'), __('Profile', 'mnw'), MNW_ACCESS_LEVEL, dirname(__FILE__) . '/admin_menu_profile.php', 'mnw_profile_options');
    add_submenu_page(__FILE__, __('Remote microblog users', 'mnw'), __('Remote users', 'mnw'), MNW_ACCESS_LEVEL, dirname(__FILE__) . '/admin_menu_remote_users.php', 'mnw_remote_users_options');
    add_submenu_page(__FILE__, __('Microblog notices', 'mnw'), __('Notices', 'mnw'), MNW_ACCESS_LEVEL, dirname(__FILE__) . '/admin_menu_notices.php', 'mnw_notices');
}

add_action('wp_dashboard_setup', 'mnw_dashboard_setup');
function mnw_dashboard_setup() {
    wp_add_dashboard_widget('mnw_dashboard', __('Microblog', 'mnw'), 'mnw_dashboard');
}

function mnw_dashboard() {
    global $wpdb;
    $title = get_bloginfo('title');

    echo '<p>';
    $subscribers = $wpdb->get_var('SELECT COUNT(*) FROM ' . MNW_SUBSCRIBER_TABLE
                                               . ' WHERE token is not null');
    printf(__ngettext('%s has <strong>%d subscriber</strong>.',
                              '%s has <strong>%d subscribers</strong>.',
                              $subscribers, 'mnw') . ' ', $title, $subscribers);

    $subscribed = $wpdb->get_var('SELECT COUNT(*) FROM ' . MNW_SUBSCRIBER_TABLE
                                              . ' WHERE resubtoken is not null');
    printf(__ngettext('It is <strong>subscribed to %d user</strong>.',
                      'It is <strong>subscribed to %d users</strong>.',
                      $subscribed, 'mnw') . ' ', $subscribed);
    echo '(<a href="admin.php?page=mnw/admin_menu_remote_users.php">' . __('User overview', 'mnw') . '</a>)';
    echo '</p>';

    echo '<p>';
    $resps = $wpdb->get_var('SELECT COUNT(*) FROM ' . MNW_FNOTICES_TABLE .
                                           ' WHERE to_us = 1');
    printf(__ngettext('<strong>%d</strong> notice is listed as ' .
                                '<strong>response</strong> to %s.',
                              '<strong>%d</strong> notices are listed as ' .
                                '<strong>responses</strong> to %s.',
                              $resps, 'mnw') . ' ', $resps, $title);
    $total = $wpdb->get_var('SELECT COUNT(*) FROM ' . MNW_FNOTICES_TABLE);
    printf(__ngettext('It has received a total of <strong>%d message</strong>.',
                      'It has received a total of <strong>%d messages</strong>.',
                      $total, 'mnw') . ' ', $total);
    echo '(<a href="admin.php?page=mnw/admin_menu_notices.php">' . __('Notices overview', 'mnw') . '</a>)';
    echo '</p>';
}

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
