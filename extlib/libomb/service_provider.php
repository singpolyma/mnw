<?php

require_once 'constants.php';
require_once 'omb_xmlwriter.php';

/**
 * OMB service realization
 *
 * This class realizes a complete, simple OMB service.
 *
 * PHP version 5
 *
 * LICENSE: This program is free software: you can redistribute it and/or modify
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
 *
 * @package   OMB
 * @author    Adrian Lang <mail@adrianlang.de>
 * @copyright 2009 Adrian Lang
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL 3.0
 **/

class OMB_Service_Provider {
  protected $user;
  protected $data_store;

  public function __construct ($user, $data_store = null) {
    $this->user = $user;
    $this->data_store = $data_store;
  }

  public function writeXRDS($oauth_base_url, $omb_base_url) {
    header('Content-Type: application/xrds+xml');
    $xw = new omb_XMLWriter();
    $xw->openURI('php://output');
    $xw->setIndent(true);

    $xw->startDocument('1.0', 'UTF-8');
    $xw->writeFullElement('XRDS',  array('xmlns' => 'xri://$xrds'), array(
        array('XRD',  array('xmlns' => 'xri://$xrd*($v*2.0)',
                                          'xml:id' => 'oauth',
                                          'xmlns:simple' => 'http://xrds-simple.net/core/1.0',
                                          'version' => '2.0'), array(
          array('Type', null, 'xri://$xrds*simple'),
          array('Service', null, array(
            array('Type', null, OAUTH_ENDPOINT_REQUEST),
            array('URI', null, $oauth_base_url . 'requesttoken'),
            array('Type', null, OAUTH_AUTH_HEADER),
            array('Type', null, OAUTH_POST_BODY),
            array('Type', null, OAUTH_HMAC_SHA1),
            array('LocalID', null, $this->user->getIdentifierURI())
          )),
          array('Service', null, array(
            array('Type', null, OAUTH_ENDPOINT_AUTHORIZE),
            array('URI', null, $oauth_base_url . 'userauthorization'),
            array('Type', null, OAUTH_AUTH_HEADER),
            array('Type', null, OAUTH_POST_BODY),
            array('Type', null, OAUTH_HMAC_SHA1)
          )),
          array('Service', null, array(
            array('Type', null, OAUTH_ENDPOINT_ACCESS),
            array('URI', null, $oauth_base_url . 'accesstoken'),
            array('Type', null, OAUTH_AUTH_HEADER),
            array('Type', null, OAUTH_POST_BODY),
            array('Type', null, OAUTH_HMAC_SHA1)
          )),
          array('Service', null, array(
            array('Type', null, OAUTH_ENDPOINT_RESOURCE),
            array('Type', null, OAUTH_AUTH_HEADER),
            array('Type', null, OAUTH_POST_BODY),
            array('Type', null, OAUTH_HMAC_SHA1)
          ))
        )),
        array('XRD',  array('xmlns' => 'xri://$xrd*($v*2.0)',
                                          'xml:id' => 'omb',
                                          'xmlns:simple' => 'http://xrds-simple.net/core/1.0',
                                          'version' => '2.0'), array(
          array('Type', null, 'xri://$xrds*simple'),
          array('Service', null, array(
            array('Type', null, OMB_ENDPOINT_POSTNOTICE),
            array('URI', null, $omb_base_url . 'postnotice')
          )),
          array('Service', null, array(
            array('Type', null, OMB_ENDPOINT_UPDATEPROFILE),
            array('URI', null, $omb_base_url . 'updateprofile')
          ))
        )),
        array('XRD',  array('xmlns' => 'xri://$xrd*($v*2.0)',
                                          'version' => '2.0'), array(
          array('Type', null, 'xri://$xrds*simple'),
          array('Service', null, array(
            array('Type', null, OAUTH_DISCOVERY),
            array('URI', null, '#oauth')
          )),
          array('Service', null, array(
            array('Type', null, OMB_VERSION),
            array('URI', null, '#omb')
          )) 
        ))
      ));
    $xw->endDocument();
    $xw->flush();
  }

  public function writeRequestToken() {
    $this->assertDataStore();
    $server = new OAuthServer($this->data_store);
    $server->add_signature_method(new OAuthSignatureMethod_HMAC_SHA1());
    OMB_Helper::removeMagicQuotesFromRequest();
    echo $server->fetch_request_token(OAuthRequest::from_request());
  }

  public function handleUserAuth() {
    $this->assertDataStore();

    OMB_Helper::removeMagicQuotesFromRequest();

    /* verify token */

    $this->token = $this->data_store->lookup_token(null, "request", $_GET['oauth_token']);
    $this->callback = $_GET['oauth_callback'];

    if (is_null($this->token)) {
      throw new Exception();
    }

    /* verify omb */

    if ($_GET['omb_version'] !== OMB_VERSION) {
      throw new Exception($GET_['omb_version']);
    }

    if ($_GET['omb_listener'] !== $this->user->getIdentifierURI()) {
      throw new Exception();
    }

    return OMB_Profile::fromParameters($_GET, 'omb_listenee');
  }

  public function continueUserAuth($accepted) {
    $this->assertDataStore();

    if (!$accepted) {
      $this->data_store->revoke_token($this->token);
      if (is_null($this->callback)) {
        return array(false, '', null);
      } else {
        /* TODO: This is wrong in terms of OAuth 1.0 but the way laconica works.
                 Moreover I don‘t know the right way either. */
        return array(true, $this->callback, null);
      }
    }

    $this->data_store->authorize_token($this->token->key);
    if (is_null($this->callback)) {
      return array(false, $this->token, $this->token);
    }

    $params = $this->user->asParameters('omb_listener', false);

    $params['oauth_token'] = $this->token->key;
    $params['omb_version'] = OMB_VERSION;

    $query_string = '';
    foreach ($params as $k => $v) {
      $query_string .= OAuthUtil::urlencode_rfc3986($k) . '=' . OAuthUtil::urlencode_rfc3986($v) . '&';
    }
    return array(true, $this->callback . ((parse_url($this->callback, PHP_URL_QUERY)) ? '&' : '?') . $query_string, $this->token);
  }

  public function writeAccessToken() {
    OMB_Helper::removeMagicQuotesFromRequest();
    $req = OAuthRequest::from_request();
    $server = new OAuthServer($this->data_store);
    $server->add_signature_method(new OAuthSignatureMethod_HMAC_SHA1());
    echo $server->fetch_access_token($req);
  }

  public function handleUpdateProfile($omb_datastore) {
    OMB_Helper::removeMagicQuotesFromRequest();
    $req = OAuthRequest::from_request();
    $server = new OAuthServer($this->data_store);
    $server->add_signature_method(new OAuthSignatureMethod_HMAC_SHA1());
    list($consumer, $token) = $server->verify_request($req);

    $version = $req->get_parameter('omb_version');
    if ($version !== OMB_VERSION) {
      throw new Exception(); // 400
    }

    $listenee =  $req->get_parameter('omb_listenee');
    $profile = $omb_datastore->getProfile($listenee);
    if (is_null($profile)) {
      throw new Exception(); // 404
    }

    $subscribers = $omb_datastore->getSubscriptions($listenee);
    if (count($subscribers) === 0) {
      throw new Exception(); // 403
    }

    $nickname = $req->get_parameter('omb_listenee_nickname');
    if (!is_null($nickname)) {
      $profile->setNickname($nickname);
    }

    $profile_url = $req->get_parameter('omb_listenee_profile');
    if (!is_null($profile_url)) {
      $profile->setProfileURL($profile_url);
    }

    $license_url = $req->get_parameter('omb_listenee_license');
    if (!is_null($license_url)) {
      $profile->setLicenseURL($license_url);
    }

    $fullname = $req->get_parameter('omb_listenee_fullname');
    if (!is_null($fullname)) {
      $profile->setFullname($fullname);
    }

    $homepage = $req->get_parameter('omb_listenee_homepage');
    if (!is_null($homepage)) {
      $profile->setHomepage($homepage);
    }

    $bio = $req->get_parameter('omb_listenee_bio');
    if (!is_null($bio)) {
      $profile->setBio($bio);
    }

    $location = $req->get_parameter('omb_listenee_location');
    if (!is_null($location)) {
      $profile->setLocation($location);
    }

    $avatar = $req->get_parameter('omb_listenee_avatar');
    if (!is_null($avatar)) {
      $profile->setAvatarURL($avatar);
    }

    $omb_datastore->saveProfile($profile, true);

    header('HTTP/1.1 200 OK');
    header('Content-type: text/plain');
    /* There should be no clutter but the version. */
    echo "omb_version=" . OMB_VERSION;
  }

  public function handlePostNotice($omb_datastore) {
    OMB_Helper::removeMagicQuotesFromRequest();
    $req = OAuthRequest::from_request();
    $server = new OAuthServer($this->data_store);
    $server->add_signature_method(new OAuthSignatureMethod_HMAC_SHA1());
    list($consumer, $token) = $server->verify_request($req);

    $version = $req->get_parameter('omb_version');
    if ($version !== OMB_VERSION) {
      throw new Exception(); // 400
    }

    $listenee =  $req->get_parameter('omb_listenee');
    $profile = $omb_datastore->getProfile($listenee);
    if (is_null($profile)) {
      throw new Exception(); // 404
    }

    $subscribers = $omb_datastore->getSubscriptions($listenee);
    if (count($subscribers) === 0) {
      throw new Exception(); // 403
    }

    $notice = OMB_Notice::fromParameters($profile, $req->get_parameters());

    echo "omb_version=" . OMB_VERSION;
    return $notice;
  }

  protected function assertDataStore() {
    if (is_null($this->data_store)) {
      throw new Exception();
    }
  }
}
