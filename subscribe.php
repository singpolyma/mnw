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
 * Laconica - a distributed open-source microblogging tool
 * Copyright (C) 2008, Controlez-Vous, Inc.
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

require_once ('lib.php');

require_once ('Validate.php');
require_once ('Auth/Yadis/Yadis.php');
require_once ('omb.php');

function mnw_parse_subscribe() {
    if (isset($_GET[MNW_SUBSCRIBE_ACTION])) {
        switch ($_GET[MNW_SUBSCRIBE_ACTION]) {
        case 'continue':
            $ret = continue_subscription();
        case 'finish':
            $ret = finish_subscription();
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

function finish_subscription() {
    // Validate a bit.
    common_ensure_session();
    $omb = $_SESSION['oauth_authorization_request'];
    if (!$omb) {
        return array('subscribe', array('error' => __('No session found.', 'mnw')));
    }
    $req = OAuthRequest::from_request();
    $token = $req->get_parameter('oauth_token');
    if ($token != $omb['token']) {
        return array('subscribe', array('error' => __('Not authorized.', 'mnw')));
    }
    list($newtok, $newsecret) = access_token($omb);

    // Subscription is indeed finished and valid. Now store the subscriber in our database.
    global $wpdb;
    $profile = $wpdb->escape($_GET['omb_listener_profile']);
    $select = "SELECT * FROM " . MNW_SUBSCRIBER_TABLE . " WHERE url = '$profile'";
    if ($wpdb->query($select) > 0) {
        $query = "UPDATE " . MNW_SUBSCRIBER_TABLE . " SET token= '" . $wpdb->escape($newtok) . "', secret= '" . $wpdb->escape($newsecret) . "' where url = '$profile'";
    } else {
        $query = "INSERT INTO " . MNW_SUBSCRIBER_TABLE . " (url, token, secret) " . "VALUES ('" . $wpdb->escape($_GET['omb_listener_profile']) . "','" . $wpdb->escape($newtok) . "','" . $wpdb->escape($newsecret) . "')";
    }
    $results = $wpdb->query($query);
    if ($results == 0) {
        return array('subscribe', array('error' => __('Error storing subscriber in local database.', 'mnw')));
    }
    wp_redirect(get_option('mnw_after_subscribe'));
    return array(false, array());
}

function continue_subscription() {
    if (!isset($_POST['profile_url'])) {
        return array('subscribe', array('error' => __('No remote profile submitted.', 'mwn')));
    }
    $target = $_POST['profile_url'];
    if (!Validate::uri($target, array('allowed_schemes' => array('http', 'https')))) {
        return array('subscribe', array('error' => __('Invalid profile URL.', 'mnw')));
    }
    $fetcher = Auth_Yadis_Yadis::getHTTPFetcher(20);
    $yadis = Auth_Yadis_Yadis::discover($target, $fetcher);
    if (!$yadis || $yadis->failed) {
        return array('subscribe', array('error' => __('Invalid profile URL (no YADIS document).', 'mnw')));
    }
    # XXX: a little liberal for sites that accidentally put whitespace before the xml declaration
    $xrds = & Auth_Yadis_XRDS::parseXRDS(trim($yadis->response_text));
    if (!$xrds) {
        return array('subscribe', array('error' => __('Invalid profile URL (no XRDS defined).', 'mnw')));
    }
    $omb = getOmb($xrds);
    if (!$omb) {
        return array('subscribe', array('error' => __('Invalid profile URL (incorrect services).', 'mnw')));
    }
    list($token, $secret) = requestToken($omb);
    if (!$token || !$secret) {
        return array('subscribe', array('error' => __('Couldn\'t get a request token.', 'mnw')));
    }
    requestAuthorization($omb, $token, $secret);
    return array(false, array());
}
function access_token($omb) {
    $con = omb_oauth_consumer();
    $tok = new OAuthToken($omb['token'], $omb['secret']);
    $url = $omb['access_token_url'];
    # XXX: Is this the right thing to do? Strip off GET params and make them
    # POST params? Seems wrong to me.
    $parsed = parse_url($url);
    $params = array();
    parse_str($parsed['query'], $params);
    $req = OAuthRequest::from_consumer_and_token($con, $tok, "POST", $url, $params);
    $req->set_parameter('omb_version', OMB_VERSION_01);
    # XXX: test to see if endpoint accepts this signature method
    $req->sign_request(omb_hmac_sha1(), $con, $tok);
    # We re-use this tool's fetcher, since it's pretty good
    $fetcher = Auth_Yadis_Yadis::getHTTPFetcher();
    $result = $fetcher->post($req->get_normalized_http_url(), $req->to_postdata(), array( /*'User-Agent' => 'Laconica/' . LACONICA_VERSION*/
    ));
    if ($result->status != 200) {
        return null;
    }
    parse_str($result->body, $return);
    return array($return['oauth_token'], $return['oauth_token_secret']);
}
function requestToken($omb) {
    $con = omb_oauth_consumer();
    $url = omb_service_uri($omb[OAUTH_ENDPOINT_REQUEST]);
    # XXX: Is this the right thing to do? Strip off GET params and make them
    # POST params? Seems wrong to me.
    $parsed = parse_url($url);
    $params = array();
    parse_str($parsed['query'], $params);
    $req = OAuthRequest::from_consumer_and_token($con, null, "POST", $url, $params);
    $listener = omb_local_id($omb[OAUTH_ENDPOINT_REQUEST]);
    if (!$listener) {
        return null;
    }
    $req->set_parameter('omb_listener', $listener);
    $req->set_parameter('omb_version', OMB_VERSION_01);
    # XXX: test to see if endpoint accepts this signature method
    $req->sign_request(omb_hmac_sha1(), $con, null);
    # We re-use this tool's fetcher, since it's pretty good
    $fetcher = Auth_Yadis_Yadis::getHTTPFetcher();
    $result = $fetcher->post($req->get_normalized_http_url(), $req->to_postdata(), array( /*'User-Agent' => 'mnw/0.1'*/
    ));
    if ($result->status != 200) {
        return null;
    }
    parse_str($result->body, $return);
    return array($return['oauth_token'], $return['oauth_token_secret']);
}
function getOmb($xrds) {
    static $omb_endpoints = array(OMB_ENDPOINT_UPDATEPROFILE, OMB_ENDPOINT_POSTNOTICE);
    static $oauth_endpoints = array(OAUTH_ENDPOINT_REQUEST, OAUTH_ENDPOINT_AUTHORIZE, OAUTH_ENDPOINT_ACCESS);
    $omb = array();
    # XXX: the following code could probably be refactored to eliminate dupes
    $oauth_services = omb_get_services($xrds, OAUTH_DISCOVERY);
    if (!$oauth_services) {
        return null;
    }
    $oauth_service = $oauth_services[0];
    $oauth_xrd = getXRD($oauth_service, $xrds);
    if (!$oauth_xrd) {
        return null;
    }
    if (!addServices($oauth_xrd, $oauth_endpoints, $omb)) {
        return null;
    }
    $omb_services = omb_get_services($xrds, OMB_NAMESPACE);
    if (!$omb_services) {
        return null;
    }
    $omb_service = $omb_services[0];
    $omb_xrd = getXRD($omb_service, $xrds);
    if (!$omb_xrd) {
        return null;
    }
    if (!addServices($omb_xrd, $omb_endpoints, $omb)) {
        return null;
    }
    # XXX: check that we got all the services we needed
    foreach(array_merge($omb_endpoints, $oauth_endpoints) as $type) {
        if (!array_key_exists($type, $omb) || !$omb[$type]) {
            return null;
        }
    }
    if (!omb_local_id($omb[OAUTH_ENDPOINT_REQUEST])) {
        return null;
    }
    return $omb;
}
function addServices($xrd, $types, &$omb) {
    foreach($types as $type) {
        $matches = omb_get_services($xrd, $type);
        if ($matches) {
            $omb[$type] = $matches[0];
        } else {
            # no match for type
            return false;
        }
    }
    return true;
}
function getXRD($main_service, $main_xrds) {
    $uri = omb_service_uri($main_service);
    if (strpos($uri, "#") !== 0) {
        # FIXME: more rigorous handling of external service definitions
        return null;
    }
    $id = substr($uri, 1);
    $nodes = $main_xrds->allXrdNodes;
    $parser = $main_xrds->parser;
    foreach($nodes as $node) {
        $attrs = $parser->attributes($node);
        if (array_key_exists('xml:id', $attrs) && $attrs['xml:id'] == $id) {
            # XXX: trick the constructor into thinking this is the only node
            $bogus_nodes = array($node);
            return new Auth_Yadis_XRDS($parser, $bogus_nodes);
        }
    }
    return null;
}
function requestAuthorization($omb, $token, $secret) {
    global $config; # for license URL
    $con = omb_oauth_consumer();
    $tok = new OAuthToken($token, $secret);
    $url = omb_service_uri($omb[OAUTH_ENDPOINT_AUTHORIZE]);
    # XXX: Is this the right thing to do? Strip off GET params and make them
    # POST params? Seems wrong to me.
    $parsed = parse_url($url);
    $params = array();
    parse_str($parsed['query'], $params);
    $req = OAuthRequest::from_consumer_and_token($con, $tok, 'GET', $url, $params);
    # We send over a ton of information. This lets the other
    # server store info about our user, and it lets the current
    # user decide if they really want to authorize the subscription.
    $req->set_parameter('omb_version', OMB_VERSION_01);
    $req->set_parameter('omb_listener', omb_local_id($omb[OAUTH_ENDPOINT_REQUEST]));
    $req->set_parameter('omb_listenee', get_bloginfo('url'));
    $req->set_parameter('omb_listenee_profile', get_bloginfo('url'));
    $req->set_parameter('omb_listenee_nickname', get_option('omb_nickname'));
    $req->set_parameter('omb_listenee_license', get_option('omb_license'));
    $req->set_parameter('omb_listenee_fullname', get_option('omb_full_name'));
    $req->set_parameter('omb_listenee_homepage', get_bloginfo('url'));
    $req->set_parameter('omb_listenee_bio', get_option('omb_bio'));
    $req->set_parameter('omb_listenee_location', get_option('omb_location'));
    $avatar = get_option('omb_avatar');
    if ($avatar) {
        $req->set_parameter('omb_listenee_avatar', $avatar);
    }
    # XXX: add a nonce to prevent replay attacks
    global $wp_query;
    $req->set_parameter('oauth_callback',  mnw_append_param(get_option('mnw_themepage_url'), MNW_ACTION, 'subscribe') . '&' . MNW_SUBSCRIBE_ACTION . '=finish');
    # XXX: test to see if endpoint accepts this signature method
    $req->sign_request(omb_hmac_sha1(), $con, $tok);
    # store all our info here
    $omb['listenee'] = get_option('omb_nickname');
    $omb['listener'] = omb_local_id($omb[OAUTH_ENDPOINT_REQUEST]);
    $omb['token'] = $token;
    $omb['secret'] = $secret;
    # call doesn't work after bounce back so we cache; maybe serialization issue...?
    $omb['access_token_url'] = omb_service_uri($omb[OAUTH_ENDPOINT_ACCESS]);
    $omb['post_notice_url'] = omb_service_uri($omb[OMB_ENDPOINT_POSTNOTICE]);
    $omb['update_profile_url'] = omb_service_uri($omb[OMB_ENDPOINT_UPDATEPROFILE]);
    common_ensure_session();
    $_SESSION['oauth_authorization_request'] = $omb;
    # Redirect to authorization service
    wp_redirect($req->to_url());
    return;
}
?>
