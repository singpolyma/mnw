<?php
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

function mnw_send_notice($content, $uri) {
    /* Insert notice into MNW_NOTICES_TABLE. */
    $insert = 'INSERT INTO ' . MNW_NOTICES_TABLE . " (uri, content, created) VALUES ('$uri', '$content', '" . common_sql_now() . "')";
    $result = $wpdb->query($insert);

    if ($result == 0) {
        return;
    }

    // Get all subscribers.
    $select = "SELECT url, token, secret FROM " . MNW_SUBSCRIBER_TABLE;
    $result = $wpdb->get_results($select, ARRAY_A);

    if ($result == 0) {
        return;
    }

    $omb_params = array(
                    'omb_listenee' => get_bloginfo('url'),
                    'omb_notice' => mnw_append_param(get_option('mnw_themepage_url'), MNW_ACTION, 'get_notice') . '&mnw_notice_id=' . $wpdb->insert_id,
                    'omb_notice_content' => $content);

    foreach($result as $subscriber) {
        try {
            $result = perform_omb_action($subscriber['url'], 'http://openmicroblogging.org/protocol/0.1/postNotice', $subscriber['token'], $subscriber['secret'], $omb_params);
            if ($result->status == 403) { # not authorized, don't send again
                delete_subscription($subscriber['url']);
            } else if ($result->status != 200) {
                print_r($req);
                print_r($result);
            }
        } catch (Exception $e) {
            continue;
        }
    }
}

?>
