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

/*
 * Initialize database on activation.
 */

function mnw_install() {
    /* Create table MNW_SUBSCRIBER_TABLE. */
    global $wpdb;
    if ($wpdb->get_var("show tables like '" . MNW_SUBSCRIBER_TABLE . "'") !== MNW_SUBSCRIBER_TABLE) {
        $sql = "CREATE TABLE " . MNW_SUBSCRIBER_TABLE . " (
          id mediumint(9) NOT NULL AUTO_INCREMENT,
          uri VARCHAR(255) NOT NULL,
          url VARCHAR(255),
          token VARCHAR(255),
          secret VARCHAR(255),
          resubtoken VARCHAR(255),
          resubsecret VARCHAR(255),
          license VARCHAR(255),
          nickname VARCHAR(64),
          avatar VARCHAR(255),
          UNIQUE KEY id (id)
        ) CHARACTER SET utf8 COLLATE utf8_bin;";
        require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /* Create table MNW_NOTICES_TABLE. */
    if ($wpdb->get_var("show tables like '" . MNW_NOTICES_TABLE . "'") !== MNW_NOTICES_TABLE) {
        $sql = "CREATE TABLE " . MNW_NOTICES_TABLE . " (
          id mediumint(11) NOT NULL AUTO_INCREMENT,
          url VARCHAR(255) comment 'URL of a wordpress object which corresponds to this notice, null if none exists',
          content VARCHAR(140),
          created datetime,
          UNIQUE KEY id (id)
        ) CHARACTER SET utf8 COLLATE utf8_bin;";
        require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /* Create table MNW_FNOTICES_TABLE. */
    if ($wpdb->get_var("show tables like '" . MNW_FNOTICES_TABLE . "'") !== MNW_FNOTICES_TABLE) {
        $sql = "CREATE TABLE " . MNW_FNOTICES_TABLE . " (
          id mediumint(11) NOT NULL AUTO_INCREMENT,
          uri VARCHAR(255),
          url VARCHAR(255),
          user_id mediumint(9) NOT NULL references " . MNW_SUBSCRIBER_TABLE . " (id),
          content VARCHAR(140),
          created datetime,
          to_us tinyint(1),
          UNIQUE KEY id (id)
        ) CHARACTER SET utf8 COLLATE utf8_bin;";
        require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /* Create table MNW_TOKENS_TABLE. */
    if ($wpdb->get_var("show tables like '" . MNW_TOKENS_TABLE . "'") !== MNW_TOKENS_TABLE) {
        $sql = "CREATE TABLE " . MNW_TOKENS_TABLE . " (
          consumer varchar(255) not null comment 'root URL of the consumer',
          token char(32) not null,
          secret char(32) not null,
          type tinyint not null default 0 comment '0 = initial request token, 1 = authorized request token, 2 = used request token, 3 = access token',
          constraint primary key (consumer, token)
        ) CHARACTER SET utf8 COLLATE utf8_bin;";
        require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /* Create table MNW_NONCES_TABLE. */
    if ($wpdb->get_var("show tables like '" . MNW_NONCES_TABLE . "'") !== MNW_NONCES_TABLE) {
        $sql = "CREATE TABLE " . MNW_NONCES_TABLE . " (
          nonce char(32) not null,
          constraint primary key (nonce)
        ) CHARACTER SET utf8 COLLATE utf8_bin;";
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
                'mnw_on_attachment'       => false,
                'mnw_subscribe_style'     => 'text-align: right; font-size:170%;',
                'mnw_notices_widget'      => array('title' => __('mnw Notices', 'mnw'), 'entry_count' => 5,
                                                'only_direct' => true, 'strip_at' => true,
                                                'template' => __('„<a href="%s">%s</a>“ (<a href="%s">%s</a> @ %s)', 'mnw'),
                                                'new_on_top' => true));
#                'mnw_mirror_subscription' => true

    foreach($options as $key => $value) {
        add_option($key, $value);
    }
}

?>
