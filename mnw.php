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

/*
 * Initialize database on activation.
 */
register_activation_hook(__FILE__, 'mnw_install');

require_once ('mnw_install.php');
require_once ('admin_menu.php');
require_once ('subscribe.php');
require_once ('get_notice.php');
require_once ('Notice.php');
/*
 * Publish notice on post/page publication.
 */

add_action('future_to_publish', 'mnw_publish_post');
add_action('new_to_publish', 'mnw_publish_post');
add_action('draft_to_publish', 'mnw_publish_post');

function mnw_publish_post($post) {
# post_type 
#    (string) (post|page|attachment) 

    $notice = mnw_Notice::fromPost($post);
    $notice->send();
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
