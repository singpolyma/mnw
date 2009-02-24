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

/*
 * Initialize database on activation.
 */

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
                'omb_full_name'           => get_bloginfo('name'),
                'omb_nickname'            => preg_replace('/[^A-Za-z0-9_\-\.]/', '', strtolower(get_bloginfo('name'))),
                'omb_license'             => 'http://creativecommons.org/licenses/by/3.0/',
                'omb_location'            => __('Teh web', 'mnw'), // sic
                'omb_avatar'              => '',
                'omb_bio'                 => get_bloginfo('name'),
                'mnw_themepage_url'       => $wpdb->get_var('SELECT p.guid FROM ' . $wpdb->prefix . 'postmeta m ' .
                                                        'LEFT JOIN ' . $wpdb->prefix . 'posts p ON m.post_id = p.ID ' .
                                                        'WHERE m.meta_key = "_wp_page_template" AND m.meta_value = "mnw.php"'),
                'mnw_after_subscribe'     => get_bloginfo('url'),
                'mnw_post_template'       => __('“%n” (see %u)', 'mnw'),
                'mnw_on_post'             => true,
                'mnw_on_page'             => false,
                'mnw_on_attachment'       => false);
#                'mnw_mirror_subscription' => true

    foreach($options as $key => $value) {
        add_option($key, $value);
    }
}

?>
