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
function delete_subscription($url) {
    global $wpdb;
    return $wpdb->query('DELETE FROM ' . MNW_SUBSCRIBER_TABLE . ' WHERE url = "' . $url . '"');
}

function discover_yadis_service($url, $service) {
    $fetcher = Auth_Yadis_Yadis::getHTTPFetcher(20);
    $yadis = Auth_Yadis_Yadis::discover($url, $fetcher);
    if (!$yadis || $yadis->failed) {
        throw new Exception('Yadis discovery failed.');
    }
    if (preg_match('/<Service>\s*<URI>([^<]+)<\/URI>\s*<Type>' . preg_quote($service, '/') . '<\/Type>\s*<\/Service>/', $yadis->response_text, $matches) == 0) {
        throw new Exception('Requested service not available.');
    }
    return $matches[1];
}

function perform_omb_action($yadis_url, $omb_action, $token, $secret, $omb_params) {
        $url = discover_yadis_service($yadis_url, $omb_action);

        $con = omb_oauth_consumer();
        $token = new OAuthToken($token, $secret);

        $parsed = parse_url($url);
        $params = array();
        parse_str($parsed['query'], $params);
        $req = OAuthRequest::from_consumer_and_token($con, $token, "POST", $url, $params);

        $req->set_parameter('omb_version', OMB_VERSION_01);
        foreach ($omb_params as $pname => $pvalue) {
            $req->set_parameter($pname, $pvalue);
        }

        $req->sign_request(omb_hmac_sha1(), $con, $token);
        # We re-use this tool's fetcher, since it's pretty good
        $fetcher = Auth_Yadis_Yadis::getHTTPFetcher();
        return $fetcher->post($req->get_normalized_http_url(), $req->to_postdata(), array('User-Agent: mnw/1.0'));
}

function common_sql_now()
{
    return strftime('%Y-%m-%d %H:%M:%S', time());
}

function common_root_url() {
    return get_bloginfo('url');
}
function common_have_session() {
    return (0 != strcmp(session_id(), ''));
}
function common_ensure_session() {
    if (!common_have_session()) {
        @session_start();
    }
}

function mnw_append_param($url, $name, $val) {
    $newurl = $url;
    if (strrpos($newurl, '?') === false) {
        $newurl .= '?';
    } else {
        $newurl .= '&';
    }
    return $newurl . $name . '=' . $val;
}
?>
