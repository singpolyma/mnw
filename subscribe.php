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

require_once 'lib.php';

require_once 'libomb/service.php';
require_once 'libomb/profile.php';

function mnw_parse_subscribe() {
    if (isset($_GET[MNW_SUBSCRIBE_ACTION])) {
        switch ($_GET[MNW_SUBSCRIBE_ACTION]) {
        case 'continue':
            $ret = continue_subscription();
            break;
        case 'finish':
            $ret = finish_subscription();
            break;
        }
    } else {
        $ret = array('', array());
    }
    if (isset($_POST['profile_url'])) {
        $ret[1]['profile_url'] = attribute_escape($_POST['profile_url']);
    } else if (isset($_GET['profile_url'])) {
        $ret[1]['profile_url'] = attribute_escape($_GET['profile_url']);
    }
    return $ret;
}

function continue_subscription() {
    if (!isset($_POST['profile_url'])) {
        return array('subscribe', array('error' => __('No remote profile submitted.', 'mnw')));
    }
    try {
      $service = new OMB_Service($_POST['profile_url'], get_bloginfo('url')); 
    } catch (Exception $e) {
      return array('subscribe', array('error' => __('Invalid profile URL.', 'mnw')));
    }
    $service->requestToken();

    $redir = $service->requestAuthorization(get_own_profile(), mnw_append_param(get_option('mnw_themepage_url'), MNW_ACTION, 'subscribe') . '&' . MNW_SUBSCRIBE_ACTION . '=finish');
    common_ensure_session();
    $_SESSION['omb_service'] = $service;
    wp_redirect($redir);
    return array(false, array());
}

function finish_subscription() {
    common_ensure_session();
    $service = $_SESSION['omb_service'];
    if (!$service) {
        return array('subscribe', array('error' => __('No session found.', 'mnw')));
    }
    try {
      $token = $service->finishAuthorization();
    } catch (OMB_NotAuthorizedException $e) {    
      return array('subscribe', array('error' => __('Not authorized.', 'mnw')));
    }

    // Subscription is finished and valid. Now store the subscriber in our database.
    global $wpdb;
    $profile = $wpdb->escape($_GET['omb_listener_profile']);
    $select = "SELECT * FROM " . MNW_SUBSCRIBER_TABLE . " WHERE url = '$profile'";
    if ($wpdb->query($select) > 0) {
        $query = "UPDATE " . MNW_SUBSCRIBER_TABLE . " SET " . 
                    "token= '" . $wpdb->escape($token->key) . "', " .
                    "secret= '" . $wpdb->escape($token->secret) .
                    "' where url = '$profile'";
    } else {
        $query = "INSERT INTO " . MNW_SUBSCRIBER_TABLE . " (url, token, secret) " .
                  "VALUES ('" . $wpdb->escape($_GET['omb_listener_profile']) . "','" .
                  $wpdb->escape($token->key) . "','" . $wpdb->escape($token->secret) . "')";
    }
    $results = $wpdb->query($query);
    if ($results == 0) {
        return array('subscribe', array('error' => __('Error storing subscriber in local database.', 'mnw')));
    }
    wp_redirect(get_option('mnw_after_subscribe'));
    return array(false, array());
}
?>
