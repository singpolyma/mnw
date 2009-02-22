<?php

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

?>
