<?php
/**
Plugin Name: mnw
Plugin URI: adrianlang.de/mnw
Description: OpenMicroBlogging compatible Microblogging for Wordpress
Version: 0.3
Author: Adrian Lang
Author URI: http://adrianlang.de
Text Domain: mnw
*/

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

set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . PATH_SEPARATOR .
                    dirname(__FILE__) . '/extlib');

load_plugin_textdomain('mnw', '', basename(dirname(__FILE__)) . '/languages/');

global $wpdb;
define('MNW_SUBSCRIBER_TABLE', $wpdb->prefix . 'mnw_subscribers');
define('MNW_NOTICES_TABLE', $wpdb->prefix . 'mnw_notices');
define('MNW_FNOTICES_TABLE', $wpdb->prefix . 'mnw_fnotices');
define('MNW_TOKENS_TABLE', $wpdb->prefix . 'mnw_tokens');
define('MNW_NONCES_TABLE', $wpdb->prefix . 'mnw_nonces');

define('MNW_ACTION', 'mnw_action');
define('MNW_SUBSCRIBE_ACTION', 'mnw_subscribe_action');
define('MNW_OAUTH_ACTION', 'mnw_oauth_action');
define('MNW_OMB_ACTION', 'mnw_omb_action');
define('MNW_NOTICE_ID', 'mnw_notice_id');

define('MNW_VERSION', '0.3');

/*
 * Config option: The User level needed to have access to the microblog
 * admin pages.
 */

define('MNW_ACCESS_LEVEL', 10);

/*
 * Initialize plugin on activation.
 */

register_activation_hook(__FILE__, 'mnw_install_wrap');

function mnw_install_wrap() {
    require_once 'mnw_install.php';
    mnw_install();
}

/*
 * Initialize admin-related stuff.
 */

require_once 'admin/mnw-admin.php';

/*
 * Display sidebar widget.
 */

require_once 'mnw_sidebar.php';

/*
 * Publish Yadis header.
 */

add_action('wp_head', 'mnw_publish_yadis');

function mnw_publish_yadis() {
    if (get_option('mnw_themepage_url') != '') {
        require_once 'lib.php';
        echo '<meta http-equiv="X-XRDS-Location" content="' .
             attribute_escape(mnw_set_action('xrds')) . '"/>';
    }
}

/*
 * Publish notice on post/page publication.
 */

add_action('future_to_publish', 'mnw_publish_post');
add_action('new_to_publish', 'mnw_publish_post');
add_action('draft_to_publish', 'mnw_publish_post');

function mnw_publish_post($post) {
    if (($post->post_type == 'post' && get_option('mnw_on_post')) ||
        ($post->post_type == 'page' && get_option('mnw_on_page')) ||
        ($post->post_type == 'attachment' && get_option('mnw_on_attachment'))) {
        require_once 'Notice.php';
        $notice = mnw_Notice::fromPost($post);
        $notice->send();
    }
}
?>
