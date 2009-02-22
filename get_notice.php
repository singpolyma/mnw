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
