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

require_once 'libomb/service_consumer.php';
require_once 'libomb/profile.php';
require_once 'omb_datastore.php';

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
      $service = new OMB_Service_Consumer($_POST['profile_url'], get_bloginfo('url'), mnw_OMB_DataStore::getInstance());
    } catch (Exception $e) {
      return array('subscribe', array('error' => __('Invalid profile URL.', 'mnw')));
    }
    $service->requestToken();

    $redir = $service->requestAuthorization(get_own_profile(), mnw_set_action('subscribe') . '&' . MNW_SUBSCRIBE_ACTION . '=finish');
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
    $_GET['omb_listener'] = $service->getListenerURI();
    $profile = OMB_Profile::fromParameters($_GET, 'omb_listener');
    $store = mnw_OMB_DataStore::getInstance();
    $store->saveProfile($profile, true);
    $results = $store->saveSubscription($profile->getIdentifierURI(), get_bloginfo('url'), $token);
    if ($results == 0) {
        return array('subscribe', array('error' => __('Error storing subscriber in local database.', 'mnw')));
    }
    wp_redirect(get_option('mnw_after_subscribe'));
    return array(false, array());
}
?>
