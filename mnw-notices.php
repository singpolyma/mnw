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

require_once 'lib.php';

function mnw_notices() {
    /* Parse input type request. */
    $types = array(
        'sent' => 'SELECT *, CONCAT("' . mnw_set_action('get_notice') . '&mnw_notice_id=' . '", id) AS "noticeurl" FROM ' . MNW_NOTICES_TABLE . ' AS notice',
        'received' => 'SELECT *, author.url AS "authorurl", notice.url as "noticeurl" FROM ' . MNW_FNOTICES_TABLE . ' AS notice, ' .
                    MNW_SUBSCRIBER_TABLE . ' AS author ' . 
                    'WHERE to_us = 1 AND notice.user_id = author.id');

    if (isset($_GET['type']) && isset($types[$_GET['type']])) {
        $type = $_GET['type'];
    } else {
        $type = 'sent';
    }

    /* Parse output type request. */
    $formats = array(
        'html' => array('mnw-notices-html.php', 'mnw_notices_html'),
        'atom' => array('mnw-notices-feed.php', 'mnw_notices_feed'));
    if (isset($_GET['format']) && isset($formats[$_GET['format']])) {
        $format = $_GET['format'];
    } else {
        $format = 'html';
    }

    /* Get offset. */
    if (isset($_GET['offset'])) {
        $offset = (int) $_GET['offset'];
    } else {
        $offset = 0;
    }

    /* Get notices. */
    global $wpdb;
    $notices = $wpdb->get_results($types[$type] . ' ' .
                                  'ORDER BY notice.created DESC ' . 
                                  'LIMIT ' . $offset . ', 11', ARRAY_A);

    $more = !is_null($notices) && count($notices) == 11;
    unset($notices[10]);

    /* Send notices to output engine. */
    require_once $formats[$format][0];
    return $formats[$format][1]($type, $offset, $more, $notices);
}
?>
