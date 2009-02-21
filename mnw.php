<?php
/*
Plugin Name: mupress not wordless
Plugin URI: adrianlang.de/mnw
Description: OpenMicroBlogging compatible Microblogging for Wordpress
Version: 0.1
Author: Adrian Lang
Author URI: http://adrianlang.de
*/

set_include_path(get_include_path() . PATH_SEPARATOR . ABSPATH . 'wp-content/plugins/mnw/extlib');
define('MNW_SUBSCRIBER_TABLE', $wpdb->prefix . 'mnw_subscribers');
define('MNW_ACTION', 'mnw_action');

require_once ('lib.php');

require_once ('admin_menu.php');

/*
 * Initialize database on activation.
 */

register_activation_hook(__FILE__, 'mnw_install');

function mnw_install() {
    /* Create table MNW_SUBSCRIBER_TABLE. */
    global $wpdb;
    if ($wpdb->get_var("show tables like '" . MNW_SUBSCRIBER_TABLE . "'") != MNW_SUBSCRIBER_TABLE) {
        $sql = "CREATE TABLE " . MNW_SUBSCRIBER_TABLE . " (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      url VARCHAR(255) NOT NULL,
      token VARCHAR(255),
      secret VARCHAR(255),
      UNIQUE KEY id (id)
    );";
        require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /* Set wordpress-managed settings to default values. */
    $options = array (
                'omb_full_name' => get_bloginfo('name'),
                'omb_nickname'  => preg_replace('/[^A-Za-z0-9_\-\.]/', '', get_bloginfo('name')),
                'omb_license'   => 'http://creativecommons.org/licenses/by/3.0/',
                'omb_location'  => 'Teh web', // sic
                'omb_avatar'    => '',
                'omb_bio'       => get_bloginfo('name'));

    foreach($options as $key => $value) {
        add_option($key, $value);
    }
}

/*
 * Publish notice on post publication.
 */

add_action('future_to_publish', 'mnw_publish_post');
add_action('new_to_publish', 'mnw_publish_post');
add_action('draft_to_publish', 'mnw_publish_post');

function mnw_publish_post($post) {
    global $wpdb;
    // Get all subscribers.
    $select = "SELECT url, token, secret FROM " . MNW_SUBSCRIBER_TABLE;
    $result = $wpdb->get_results($select, ARRAY_A);

    if ($result == 0) {
        return;
    }

    $omb_params = array(
                    'omb_listenee' => get_bloginfo('url'),
                    'omb_notice' => get_permalink($post->ID),
                    'omb_notice_content' => '„' . $post->post_title . '“ see ' . get_permalink($post->ID));

    foreach($result as $subscriber) {
        try {
            $result = perform_omb_action($subscriber['url'], 'http://openmicroblogging.org/protocol/0.1/postNotice', $subscriber['token'], $subscriber['secret'], $omb_params);
            if ($result->status == 403) { # not authorized, don't send again
                throw new PermanentError('Remote user is not subscribed anymore.'); 
            } else if ($result->status != 200) {
                print_r($req);
                print_r($result);
            }
        } catch (PermanentError $e) {
            delete_subscription($subscriber['url']);
        }
    }
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

require_once ('Validate.php');
require_once ('Auth/Yadis/Yadis.php');
require_once ('omb.php');

function mnw_parse_request() {
    if (isset($_GET[MNW_ACTION])) {
        switch ($_GET[MNW_ACTION]) {
        case 'continue':
            return continue_subscription();
        case 'finish':
            return finish_subscription();
        }
    } else {
        return array('', array());
    }
}

function finish_subscription() {
    // Validate a bit.
    common_ensure_session();
    $omb = $_SESSION['oauth_authorization_request'];
    if (!$omb) {
        return array('subscribe', array('error' => 'No session found.'));
    }
    $req = OAuthRequest::from_request();
    $token = $req->get_parameter('oauth_token');
    if ($token != $omb['token']) {
        return array('subscribe', array('error' => 'Not authorized.'));
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
        return array('subscribe', array('error' => 'Error storing subscriber in local database'));
    }
    wp_redirect(get_bloginfo('url'));
    return array(false, array());
}

function continue_subscription() {
    if (!isset($_POST['profile_url'])) {
        return array('subscribe', array('error' => 'No remote profile submitted.'));
    }
    $target = $_POST['profile_url'];
    if (!Validate::uri($target, array('allowed_schemes' => array('http', 'https')))) {
        return array('subscribe', array('error' => 'Invalid profile URL.'));
    }
    $fetcher = Auth_Yadis_Yadis::getHTTPFetcher(20);
    $yadis = Auth_Yadis_Yadis::discover($target, $fetcher);
    if (!$yadis || $yadis->failed) {
        return array('subscribe', array('error' => 'Invalid profile URL (no YADIS document).'));
    }
    # XXX: a little liberal for sites that accidentally put whitespace before the xml declaration
    $xrds = & Auth_Yadis_XRDS::parseXRDS(trim($yadis->response_text));
    if (!$xrds) {
        return array('subscribe', array('error' => 'Invalid profile URL (no XRDS defined).'));
    }
    $omb = getOmb($xrds);
    if (!$omb) {
        return array('subscribe', array('error' => 'Not a valid profile URL (incorrect services).'));
    }
    list($token, $secret) = requestToken($omb);
    if (!$token || !$secret) {
        return array('subscribe', array('error' => 'Couldn\'t get a request token.'));
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
    $req->set_parameter('oauth_callback',  mnw_append_param($wp_query->post->guid, MNW_ACTION, 'finish'));
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

function mnw_append_param($url, $name, $val) {
    $newurl = $url;
    if (strrpos($newurl, '?') === false) {
        $newurl .= '?';
    } else {
        $newurl .= '&';
    }
    return $newurl . $name . '=' . $val;
}


function mnw_subscribe_form($errormsg = '') {
    if (isset($_POST['profile_url'])) {
        $preload = attribute_escape($_POST['profile_url']);
    } else if (isset($_GET['profile_url'])) {
        $preload = attribute_escape($_GET['profile_url']);
    } else {
        $preload = '';
    }

    global $wp_query;
    $action = attribute_escape(mnw_append_param($wp_query->queried_object->guid, MNW_ACTION, 'continue'));

    if ($errormsg != '') {
        echo "<p>ERROR: $errormsg</p>";
    }
?>
    <form id='omb-subscribe' method='post' action='<?php echo $action; ?>'>
        <label for="profile_url">OMB Profile URL</label>
        <input name="profile_url" type="text" class="input_text" id="profile_url" value='<?php echo $preload; ?>'/>
        <input type="submit" id="submit" name="submit" class="submit" value="Subscribe"/>
    </form>
<?php
}
?>
