<?php

$mnw_options = array("omb_full_name", "omb_nickname", "omb_license", "omb_bio", "omb_location", "omb_avatar");
foreach ($mnw_options as $option_name) {
    add_action( "update_option_{$option_name}", 'mnw_upd_settings');
}

function mnw_upd_settings() {
    // Get all subscribers.
    global $wpdb;

    $select = "SELECT url, token, secret FROM " . MNW_SUBSCRIBER_TABLE;
    $result = $wpdb->get_results($select, ARRAY_A);
    if ($result == 0) {
        return;
    }

    foreach ($result as $subscriber) {
    $target = $subscriber['url'];
    if (!Validate::uri($target, array('allowed_schemes' => array('http', 'https')))) {
        continue;
    }

    $fetcher = Auth_Yadis_Yadis::getHTTPFetcher(20);
    $yadis = Auth_Yadis_Yadis::discover($target, $fetcher);

    if (!$yadis || $yadis->failed) {
        continue;
    }
    if (preg_match('/<Service>\s*<URI>([^<]+)<\/URI>\s*<Type>http:\/\/openmicroblogging.org\/protocol\/0.1\/updateProfile<\/Type>\s*<\/Service>/',$yadis->response_text, $matches) == 0) {
        continue;
    }
        $con = omb_oauth_consumer();
        $token = new OAuthToken($subscriber['token'], $subscriber['secret']);
        $url = $matches[1];
    $parsed = parse_url($url);
    $params = array();
    parse_str($parsed['query'], $params);
    $req = OAuthRequest::from_consumer_and_token($con, $token,
                                                 "POST", $url, $params);

       $req->set_parameter('omb_version', OMB_VERSION_01);
        $req->set_parameter('omb_listenee', get_bloginfo('url'));
        $req->set_parameter('omb_listenee_profile', get_bloginfo('url'));
        $req->set_parameter('omb_listenee_nickname', get_option('omb_nickname'));
        $req->set_parameter('omb_listenee_license', get_option('omb_license'));

        $req->set_parameter('omb_listenee_fullname', get_option('omb_full_name'));
        $req->set_parameter('omb_listenee_homepage', get_bloginfo('url'));
        $req->set_parameter('omb_listenee_bio', get_option('omb_bio'));
        $req->set_parameter('omb_listenee_location', get_option('omb_location'));

        $req->set_parameter('omb_listenee_avatar', get_option('omb_avatar'));

    $req->sign_request(omb_hmac_sha1(), $con, $token);

    # We re-use this tool's fetcher, since it's pretty good

    $fetcher = Auth_Yadis_Yadis::getHTTPFetcher();

    $result = $fetcher->post($req->get_normalized_http_url(),
                             $req->to_postdata(),
                             array(/*'User-Agent' => 'mnw/1.0'*/));

    if ($result->status == 403) { # not authorized, don't send again
    //    $subscription->delete();
    }
// if ($result->status != 200) {
print_r($req);
print_r($result);
// }
}
}

add_action('admin_menu', 'mnw_admin_menu');

function mnw_admin_menu() {
  add_options_page('mnw Options', 'mnw', 8, __FILE__, 'mnw_admin_options');
}

function mnw_admin_options() {
    global $mnw_options;
?>
<div class="wrap">
    <h2>mnw</h2>
    <form method="post" action="options.php">
        <?php wp_nonce_field('update-options'); ?>
        <h3>OMB profile</h3>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Full name</th>
                <td><input type="text" name="omb_full_name" value="<?php echo get_option('omb_full_name'); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Nickname</th>
                <td><input type="text" name="omb_nickname" value="<?php echo get_option('omb_nickname'); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Bio</th>
                <td><textarea cols="40" rows="3" name="omb_bio"><?php echo get_option('omb_bio'); ?></textarea></td>
            </tr>
            <tr valign="top">
                <th scope="row">Location</th>
                <td><input type="text" name="omb_location" value="<?php echo get_option('omb_location'); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Avatar URL</th>
                <td><input type="text" name="omb_avatar" value="<?php echo get_option('omb_avatar'); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">License URL</th>
                <td><input type="text" name="omb_license" value="<?php echo get_option('omb_license'); ?>" /></td>
            </tr>
        </table>
        <input type="hidden" name="action" value="update" />
        <input type="hidden" name="page_options" value="<?php echo join(",", $mnw_options); ?>" />
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
        </p>
    </form>
</div>
<?php
}
?>
