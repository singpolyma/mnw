<?php
/**
 * This file is part of mnw. Other files in mnw may have different copyright notices.
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

$mnw_options = array("omb_full_name", "omb_nickname", "omb_license", "omb_bio", "omb_location", "omb_avatar");

foreach($mnw_options as $option_name) {
    add_action("update_option_{$option_name}", 'mnw_upd_settings');
}

function mnw_upd_settings() {
    // Get all subscribers.
    global $wpdb;
    $select = "SELECT url, token, secret FROM " . MNW_SUBSCRIBER_TABLE;
    $result = $wpdb->get_results($select, ARRAY_A);

    if ($result == 0) {
        return;
    }

    $omb_params = array(
                    'omb_listenee'          => get_bloginfo('url'),
                    'omb_listenee_profile'  => get_bloginfo('url'),
                    'omb_listenee_homepage' => get_bloginfo('url'),
                    'omb_listenee_nickname' => get_option('omb_nickname'),
                    'omb_listenee_license'  => get_option('omb_license'),
                    'omb_listenee_fullname' => get_option('omb_full_name'),
                    'omb_listenee_bio'      => get_option('omb_bio'),
                    'omb_listenee_location' => get_option('omb_location'),
                    'omb_listenee_avatar'   => get_option('omb_avatar'));

    foreach($result as $subscriber) {
        try {
            $result = perform_omb_action($subscriber['url'], 'http://openmicroblogging.org/protocol/0.1/updateProfile', $subscriber['token'], $subscriber['secret'], $omb_params);
            if ($result->status == 403) { # not authorized, don't send again
                delete_subscription($subscriber['url']);
            } else if ($result->status != 200) {
                print_r($result);
            }
        } catch (Exception $e) {
            continue;
        }
    }
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
        <h3>General mnw settings</h3>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">URL of a wordpress page which uses mnw.php as template. All public URLs are based on this URL; you should never change it.</th>
                <td><input type="text" name="mnw_themepage_url" value="<?php echo get_option('mnw_themepage_url'); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    Template used to generate microblog posts on blog post or page publication.<br />
                    You may use the following placeholders:<br />
                    %t: title of the post or page<br />
                    %u: url of the post or page<br />
                    %e: excerpt of the post or page<br />
                    %c: content of the post or page</th>
                <td><input type="text" name="mnw_post_template" value="<?php echo get_option('mnw_post_template'); ?>" /></td>
            </tr>
        </table>
        <input type="hidden" name="action" value="update" />
        <input type="hidden" name="page_options" value="<?php echo join(",", $mnw_options); ?>, mnw_themepage_url, mnw_post_template" />
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
        </p>
    </form>
</div>
<?php
}
?>
