<?php
/*
Plugin Name: mupress not wordless
Plugin URI: adrianlang.de/mnw
Description: OpenMicroBlogging compatible Microblogging for Wordpress
Version: 0.1
Author: Adrian Lang
Author URI: http://adrianlang.de
*/

set_include_path(get_include_path() . PATH_SEPARATOR . ABSPATH . 'wp-content/plugins/mnw/extlib');
define('MNW_SUBSCRIBER_TABLE', $wpdb->prefix . 'mnw_subscribers');
define('MNW_ACTION', 'mnw_action');

require_once ('lib.php');

require_once ('admin_menu.php');
require_once ('subscribe.php');

/*
 * Initialize database on activation.
 */

register_activation_hook(__FILE__, 'mnw_install');

function mnw_install() {
    /* Create table MNW_SUBSCRIBER_TABLE. */
    global $wpdb;
    if ($wpdb->get_var("show tables like '" . MNW_SUBSCRIBER_TABLE . "'") != MNW_SUBSCRIBER_TABLE) {
        $sql = "CREATE TABLE " . MNW_SUBSCRIBER_TABLE . " (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      url VARCHAR(255) NOT NULL,
      token VARCHAR(255),
      secret VARCHAR(255),
      UNIQUE KEY id (id)
    );";
        require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /* Set wordpress-managed settings to default values. */
    $options = array (
                'omb_full_name' => get_bloginfo('name'),
                'omb_nickname'  => preg_replace('/[^A-Za-z0-9_\-\.]/', '', get_bloginfo('name')),
                'omb_license'   => 'http://creativecommons.org/licenses/by/3.0/',
                'omb_location'  => 'Teh web', // sic
                'omb_avatar'    => '',
                'omb_bio'       => get_bloginfo('name'));

    foreach($options as $key => $value) {
        add_option($key, $value);
    }
}

/*
 * Publish notice on post publication.
 */

add_action('future_to_publish', 'mnw_publish_post');
add_action('new_to_publish', 'mnw_publish_post');
add_action('draft_to_publish', 'mnw_publish_post');

function mnw_publish_post($post) {
    global $wpdb;
    // Get all subscribers.
    $select = "SELECT url, token, secret FROM " . MNW_SUBSCRIBER_TABLE;
    $result = $wpdb->get_results($select, ARRAY_A);

    if ($result == 0) {
        return;
    }

    $omb_params = array(
                    'omb_listenee' => get_bloginfo('url'),
                    'omb_notice' => get_permalink($post->ID),
                    'omb_notice_content' => '„' . $post->post_title . '“ see ' . get_permalink($post->ID));

    foreach($result as $subscriber) {
        try {
            $result = perform_omb_action($subscriber['url'], 'http://openmicroblogging.org/protocol/0.1/postNotice', $subscriber['token'], $subscriber['secret'], $omb_params);
            if ($result->status == 403) { # not authorized, don't send again
                throw new PermanentError('Remote user is not subscribed anymore.'); 
            } else if ($result->status != 200) {
                print_r($req);
                print_r($result);
            }
        } catch (PermanentError $e) {
            delete_subscription($subscriber['url']);
        }
    }
}

function mnw_parse_request() {
    if (isset($_GET[MNW_ACTION])) {
        switch ($_GET[MNW_ACTION]) {
        case 'subscribe':
            return mnw_parse_subscribe();
        }
    } else {
        return array('', array());
    }
}
?>
