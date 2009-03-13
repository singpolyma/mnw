<?php

require_once 'constants.php';
require_once 'Validate.php';
require_once 'Auth/Yadis/Yadis.php';
require_once 'OAuth.php';
require_once 'unsupportedserviceexception.php';
require_once 'yadis_service.php';
require_once 'omb_yadis_xrds.php';
require_once 'helper.php';

/**
 * OMB service representation
 *
 * This class represents a complete remote OMB service. It provides discovery
 * and execution of the service‘s methods. 
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

class OMB_Service_Consumer {
  protected $url;
  protected $services; /* array containing OMB_Yadis_Service objects. */

  protected $token; /* An OAuthToken. */ 

  /**
   * According to OAuth Core 1.0, an user authorization request is no full-blown
   * OAuth request. nonce, timestamp, consumer_key and signature are not needed
   * in this step. See http://laconi.ca/trac/ticket/827 for more informations.
   *
   * Since Laconica up to version 0.7.2 performs a full OAuth request check, a
   * correct request would fail.
   **/
  public $performLegacyAuthRequest = true;

  /* Helper stuff we are going to need. */
  protected $fetcher;
  protected $oauth_consumer;
  protected $datastore;

  public function __construct ($service_url, $consumer_url, $datastore) {
    $this->url = $service_url;
    $this->fetcher = Auth_Yadis_Yadis::getHTTPFetcher();
    $this->datastore = $datastore;
    $this->oauth_consumer = new OAuthConsumer($consumer_url, '');

    $xrds = OMB_Yadis_XRDS::fromYadisURL($service_url, $this->fetcher);

    /* Detect our services. */
    $this->services = array();

    foreach (array(OAUTH_DISCOVERY => OMB_Helper::$OAUTH_SERVICES,
                   OMB_VERSION     => OMB_Helper::$OMB_SERVICES)
             as $service_root => $targetservices) {
      $root_service = new OMB_Yadis_Service($xrds->getService($service_root));
      $xrd = $xrds->getXRD($root_service->getURI());
      foreach ($targetservices as $targetservice) {
        $this->services[$targetservice] = new OMB_Yadis_Service($xrd->getService($targetservice));
      }
    }
  }

  public function requestToken() {
    $listener = $this->services[OAUTH_ENDPOINT_REQUEST]->getLocalID();
    $result = $this->performAction(OAUTH_ENDPOINT_REQUEST, array('omb_listener' => $listener));
    if ($result->status != 200) {
        throw new Exception();
    }
    parse_str($result->body, $return);
    $this->setToken($return['oauth_token'], $return['oauth_token_secret']);
    return $this->token;
  }

  public function requestAuthorization($profile, $finish_url) {
    if ($this->performLegacyAuthRequest) {
      $params = $profile->asParameters('omb_listenee', false);
      $params['omb_listener'] = $this->services[OAUTH_ENDPOINT_REQUEST]->getLocalID();
      $params['oauth_callback'] = $finish_url;

      $url = $this->prepareAction(OAUTH_ENDPOINT_AUTHORIZE, $params, 'GET')->to_url();
    } else {

      $this->assertService(OAUTH_ENDPOINT_AUTHORIZE);

      $params = array(
                'oauth_callback' => $finish_url,
                'oauth_token'    => $this->token->key,
                'omb_version'    => OMB_VERSION,
                'omb_listener'   => $this->services[OAUTH_ENDPOINT_REQUEST]->getLocalID());

      $params = array_merge($profile->asParameters('omb_listenee', false). $params);

      /* Build result URL. */
      $url = $this->services[OAUTH_ENDPOINT_AUTHORIZE]->getURI();
      $url .= (strrpos($url, '?') === false ? '?' : '&');
      foreach ($params as $k => $v) {
        $url .= OAuthUtil::urlencode_rfc3986($k) . '=' . OAuthUtil::urlencode_rfc3986($v) . '&';
      }
    }
    return $url;
  }

  public function finishAuthorization() {
    OMB_Helper::removeMagicQuotesFromRequest();
    if (OAuthRequest::from_request()->get_parameter('oauth_token') !=
          $this->token->key) {
      throw new Exception();
    }

    $result = $this->performAction(OAUTH_ENDPOINT_ACCESS, array());
    if ($result->status != 200) {
        throw new Exception();
    }

    parse_str($result->body, $return);
    $this->setToken($return['oauth_token'], $return['oauth_token_secret']);
    return $this->token;
  }

  /* Workaround fo serious OMB flaw: The Listener URI is not passed in the
     finishauthorization call*/
  public function getListenerURI() {
    return $this->services[OAUTH_ENDPOINT_REQUEST]->getLocalID();
  }

  public function updateProfile($profile) {
    $params = $profile->asParameters('omb_listenee', true);
    $this->performOMBAction(OMB_ENDPOINT_UPDATEPROFILE, $params, $profile->getIdentifierURI());
  }

  public function postNotice($notice) {
    $params = $notice->asParameters();
    $params['omb_listenee'] = $notice->getAuthor()->getIdentifierURI();
    $this->performOMBAction(OMB_ENDPOINT_POSTNOTICE, $params, $params['omb_listenee']);
  }

  protected function assertService($service) {
    if (!isset($this->services[$service])) {
      throw new OMB_UnsupportedServiceException($service);
    }
  }

  public function setToken($token, $secret) {
    $this->token = new OAuthToken($token, $secret);
  }

  protected function prepareAction($action_uri, $params, $method) {
    $this->assertService($action_uri);

    $url = $this->services[$action_uri]->getURI();
    $url_params = array();
    parse_str(parse_url($url, PHP_URL_QUERY), $url_params);
    $req = OAuthRequest::from_consumer_and_token($this->oauth_consumer, $this->token, $method, $url, $url_params);

    $req->set_parameter('omb_version', OMB_VERSION);

    /* Add user defined parameters. */
    foreach ($params as $param => $value) {
      $req->set_parameter($param, $value);
    }

    /* Sign the request. */
    $req->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->oauth_consumer, $this->token);

    return $req;
  }

  protected function performAction($action_uri, $params) {
    $req = $this->prepareAction($action_uri, $params, 'POST');

    /* Return result page. */
    return $this->fetcher->post($req->get_normalized_http_url(), $req->to_postdata(), array());
  }

  protected function performOMBAction($action_uri, $params, $listeneeUri) {
    $result = $this->performAction($action_uri, $params);
    if ($result->status == 403) {
      // The remote user unsubscribed us.
      $this->datastore->deleteSubscription($this->getListenerURI(), $listeneeUri);
    } else if ($result->status != 200) {
      // Probably a server error.
      throw new Exception('Got error status ' . $result->status . ' with content: ' . $result->body);
    } else if (strpos($result->body, 'omb_version=' . OMB_VERSION) === false) {
      // Server did not sent a correct response.
      throw new Exception('Got reponse ' . $result->body);
    }
  }
}
