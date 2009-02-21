<?php
function delete_subscription($url) {
    global $wpdb;
    return $wpdb->query('DELETE FROM ' . MNW_SUBSCRIBER_TABLE . ' WHERE url = "' . $url . '"');
}

function discover_yadis_service($url, $service) {
    $fetcher = Auth_Yadis_Yadis::getHTTPFetcher(20);
    $yadis = Auth_Yadis_Yadis::discover($url, $fetcher);
    if (!$yadis || $yadis->failed) {
        throw new PermanentError('Yadis discovery failed.');
    }
    if (preg_match('/<Service>\s*<URI>([^<]+)<\/URI>\s*<Type>' . preg_quote($service, '/') . '<\/Type>\s*<\/Service>/', $yadis->response_text, $matches) == 0) {
        throw new PermanentError('Requested service not available.');
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

class PermanentError extends Exception {
}
?>
