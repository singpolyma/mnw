<?php

require_once 'Validate.php';

/**
 * Helper functions for libomb
 *
 * This file contains helper functions for libomb.
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

class OMB_Helper {

  /**
   * Non-scalar constants
   *
   * The set of OMB and OAuth Services an OMB Server has to implement.
   */

  public static $OMB_SERVICES =
    array(OMB_ENDPOINT_UPDATEPROFILE, OMB_ENDPOINT_POSTNOTICE);
  public static $OAUTH_SERVICES =
    array(OAUTH_ENDPOINT_REQUEST, OAUTH_ENDPOINT_AUTHORIZE, OAUTH_ENDPOINT_ACCESS);

  /**
   * Validate URL
   *
   * Basic URL validation. Currently http, https, ftp and gopher are supported
   * schemes.
   *
   * @param string $url The URL which is to be validated.
   *
   * @return bool If URL is valid.
   *
   * @access public
   */
  public static function validateURL($url) {
    return Validate::uri($url, array('allowed_schemes' => array('http', 'https',
            'gopher', 'ftp')));
  }

  /**
   * Validate Media type
   *
   * Basic Media type validation. Checks for valid maintype and correct format.
   *
   * @param string $mediatype The Media type which is to be validated.
   *
   * @return bool If Media type is valid.
   *
   * @access public
   */
  public static function validateMediaType($mediatype) {
    if (0 === preg_match('/^(\w+)\/([\w\d-+.]+)$/', $mediatype, $subtypes)) {
      return false;
    }
    if (!in_array(strtolower($subtypes[1]), array('application', 'audio', 'image',
              'message', 'model', 'multipart', 'text', 'video'))) {
      return false;
    }
    return true;
  }

  // Neutralise the evil effects of magic_quotes_gpc in the current request.
  // This is used before handing a request off to OAuthRequest::from_request.
  // Many thanks to Ciaran Gultnieks for this fix.
  public static function removeMagicQuotesFromRequest() {
    if(get_magic_quotes_gpc()) {
      $_POST=array_map('stripslashes',$_POST);
      $_GET=array_map('stripslashes',$_GET);
    }
  }
}
?>
