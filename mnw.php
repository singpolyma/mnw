<?php
/*
Plugin Name: mupress not wordless
Plugin URI: adrianlang.de/mnw
Description: OpenMicroBlogging compatible Microblogging for Wordpress
Version: 0.1
Author: Adrian Lang
Author URI: http://adrianlang.de
*/

# require_once ('extlib/omb.php');

set_include_path(get_include_path() . PATH_SEPARATOR . ABSPATH . 'wp-content/plugins/mnw/extlib');

require_once ('Validate.php');
require_once ('Auth/Yadis/Yadis.php');
require_once ('omb.php');

function mnw_subscribe_form() {
?>
<?php
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        mnw_show_subscribe_form();
        return;
    }

    if (!isset($_POST['profile_url'])) {
        mnw_show_subscribe_form('No remote profile submitted.');
        return;
    }
    
    $target = $_POST['profile_url'];                
    if (!Validate::uri($target, array('allowed_schemes' => array('http', 'https')))) {
        mnw_show_subscribe_form('Invalid profile URL.', $target);
        return;
    }

    $fetcher = Auth_Yadis_Yadis::getHTTPFetcher();
    $yadis = Auth_Yadis_Yadis::discover($target, $fetcher);

    if (!$yadis || $yadis->failed) {
        mnw_show_subscribe_form('Invalid profile URL (no YADIS document).', $target);
        return;
    }

    # XXX: a little liberal for sites that accidentally put whitespace before the xml declaration

    $xrds =& Auth_Yadis_XRDS::parseXRDS(trim($yadis->response_text));

    if (!$xrds) {
        mnw_show_subscribe_form('Invalid profile URL (no XRDS defined).');
        return;
    }

    $omb = getOmb($xrds);

        if (!$omb) {
            $this->showForm(_('Not a valid profile URL (incorrect services).'));
            return;
        }

        if (omb_service_uri($omb[OAUTH_ENDPOINT_REQUEST]) ==
            common_local_url('requesttoken'))
        {
            $this->showForm(_('That\'s a local profile! Login to subscribe.'));
            return;
        }

        if (User::staticGet('uri', omb_local_id($omb[OAUTH_ENDPOINT_REQUEST]))) {
            $this->showForm(_('That\'s a local profile! Login to subscribe.'));
            return;
        }

        list($token, $secret) = $this->requestToken($omb);

        if (!$token || !$secret) {
            $this->showForm(_('Couldn\'t get a request token.'));
            return;
        }

        $this->requestAuthorization($user, $omb, $token, $secret);

}

    function getOmb($xrds)
    {
        static $omb_endpoints = array(OMB_ENDPOINT_UPDATEPROFILE, OMB_ENDPOINT_POSTNOTICE);
        static $oauth_endpoints = array(OAUTH_ENDPOINT_REQUEST, OAUTH_ENDPOINT_AUTHORIZE,
                                        OAUTH_ENDPOINT_ACCESS);
        $omb = array();

        # XXX: the following code could probably be refactored to eliminate dupes

        $oauth_services = omb_get_services($xrds, OAUTH_DISCOVERY);

        if (!$oauth_services) {
            return null;
        }

        $oauth_service = $oauth_services[0];

        $oauth_xrd = $this->getXRD($oauth_service, $xrds);

        if (!$oauth_xrd) {
            return null;
        }

        if (!$this->addServices($oauth_xrd, $oauth_endpoints, $omb)) {
            return null;
        }

        $omb_services = omb_get_services($xrds, OMB_NAMESPACE);

        if (!$omb_services) {
            return null;
        }

        $omb_service = $omb_services[0];

        $omb_xrd = $this->getXRD($omb_service, $xrds);

        if (!$omb_xrd) {
            return null;
        }

        if (!$this->addServices($omb_xrd, $omb_endpoints, $omb)) {
            return null;
        }

        # XXX: check that we got all the services we needed

        foreach (array_merge($omb_endpoints, $oauth_endpoints) as $type) {
            if (!array_key_exists($type, $omb) || !$omb[$type]) {
                return null;
            }
        }

        if (!omb_local_id($omb[OAUTH_ENDPOINT_REQUEST])) {
            return null;
        }

        return $omb;
    }

function mnw_show_subscribe_form($errormsg = '', $preload = '') {
    if ($errormsg != '') {
        echo "<p>ERROR: $errormsg</p>";
    }
?>
<?php
// XXX: Make this dynamic!!
?>
    <form id='omb-subscribe' method='post' action='<?php echo 'http://virtual/wordpress/?page_id=3'; /* bloginfo('url');*/ ?>'>
        <label for="profile_url">OMB Profile URL</label>
        <input name="profile_url" type="text" class="input_text" id="profile_url" value='<?php echo $preload; ?>'/>
        <input type="submit" id="submit" name="submit" class="submit" value="Subscribe"/>
    </form>
<?php
}
?>
