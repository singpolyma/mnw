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

global $wpdb;
define('MNW_SUBSCRIBER_TABLE', $wpdb->prefix . 'mnw_subscribers');
define('MNW_NOTICES_TABLE', $wpdb->prefix . 'mnw_notices');
define('MNW_ACTION', 'mnw_action');
define('MNW_SUBSCRIBE_ACTION', 'mnw_subscribe_action');
define('MNW_NOTICE_ID', 'mnw_notice_id');

require_once ('lib.php');

require_once ('admin_menu.php');
require_once ('subscribe.php');
require_once ('get_notice.php');

/*
 * Initialize database on activation.
 */

register_activation_hook(__FILE__, 'mnw_install');

function mnw_install() {
    /* Create table MNW_SUBSCRIBER_TABLE. */
    global $wpdb;
    if ($wpdb->get_var("show tables like '" . MNW_SUBSCRIBER_TABLE . "'") !== MNW_SUBSCRIBER_TABLE) {
        $sql = "CREATE TABLE " . MNW_SUBSCRIBER_TABLE . " (
          id mediumint(9) NOT NULL AUTO_INCREMENT,
          url VARCHAR(255) NOT NULL,
          token VARCHAR(255),
          secret VARCHAR(255),
          UNIQUE KEY id (id)
        )";
        require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /* Create table MNW_NOTICES_TABLE. */
    if ($wpdb->get_var("show tables like '" . MNW_NOTICES_TABLE . "'") !== MNW_NOTICES_TABLE) {
        $sql = "CREATE TABLE " . MNW_NOTICES_TABLE . " (
          id mediumint(11) NOT NULL AUTO_INCREMENT,
          uri VARCHAR(255),
          content VARCHAR(140),
          created datetime,
          UNIQUE KEY id (id)
        )";
        require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /* Set wordpress-managed settings to default values. */
    $options = array (
                'omb_full_name'     => get_bloginfo('name'),
                'omb_nickname'      => preg_replace('/[^A-Za-z0-9_\-\.]/', '', get_bloginfo('name')),
                'omb_license'       => 'http://creativecommons.org/licenses/by/3.0/',
                'omb_location'      => 'Teh web', // sic
                'omb_avatar'        => '',
                'omb_bio'           => get_bloginfo('name'),
                'mnw_themepage_url' => $wpdb->get_var('SELECT p.guid FROM ' . $wpdb->prefix . 'postmeta m ' .
                                                        'LEFT JOIN ' . $wpdb->prefix . 'posts p ON m.post_id = p.ID ' . 
                                                        'WHERE m.meta_key = "_wp_page_template" AND m.meta_value = "mnw.php"'),
               'mnw_post_template'  => '„%t“ (see %u)');

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

    $uri = get_permalink($post->ID);
    $content = $wpdb->escape(preg_replace(array('/%t/', '/%u/'), array ($post->post_title, get_permalink($post->ID)), get_option('mnw_post_template')));

    /* Insert notice into MNW_NOTICES_TABLE. */
    $insert = 'INSERT INTO ' . MNW_NOTICES_TABLE . " (uri, content, created) VALUES ('$uri', '$content', '" . common_sql_now() . "')";
    $result = $wpdb->query($insert);

    if ($result == 0) {
        return;
    }

    // Get all subscribers.
    $select = "SELECT url, token, secret FROM " . MNW_SUBSCRIBER_TABLE;
    $result = $wpdb->get_results($select, ARRAY_A);

    if ($result == 0) {
        return;
    }

    $omb_params = array(
                    'omb_listenee' => get_bloginfo('url'),
                    'omb_notice' => mnw_append_param(get_option('mnw_themepage_url'), MNW_ACTION, 'get_notice') . '&mnw_notice_id=' . $wpdb->insert_id,
                    'omb_notice_content' => $content);

    foreach($result as $subscriber) {
        try {
            $result = perform_omb_action($subscriber['url'], 'http://openmicroblogging.org/protocol/0.1/postNotice', $subscriber['token'], $subscriber['secret'], $omb_params);
            if ($result->status == 403) { # not authorized, don't send again
                delete_subscription($subscriber['url']);
            } else if ($result->status != 200) {
                print_r($req);
                print_r($result);
            }
        } catch (Exception $e) {
            continue;
        }
    }
}

function mnw_parse_request() {
    if (isset($_GET[MNW_ACTION])) {
        switch ($_GET[MNW_ACTION]) {
        case 'subscribe':
            return mnw_parse_subscribe();
            break;
        case 'get_notice':
            return mnw_get_notice();
            break;
        }
    } else {
        return array('', array());
    }
}
?>
