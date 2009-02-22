<?php

function mnw_get_notice() {
    if (isset($_GET[MNW_NOTICE_ID])) {
        global $wpdb;
        $notice_url = $wpdb->get_var('SELECT uri FROM ' . MNW_NOTICES_TABLE . ' WHERE id = ' . $wpdb->escape($_GET[MNW_NOTICE_ID]));
        if ($notice_url) { 
            wp_redirect($notice_url , 307);
            return array(false, array());
        }
    }
    return array('', array());
}

?>
