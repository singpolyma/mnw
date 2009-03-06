<?php
/**
 * This file is part of mnw.
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
require_once 'libomb/service_provider.php';
require_once 'lib.php';
require_once 'datastore.php';
require_once 'omb_datastore.php';

function mnw_get_xrds() {
  $srv = new OMB_Service_Provider(get_own_profile());
  $srv->getXRDS(mnw_set_action('oauth') . '&' . MNW_OAUTH_ACTION . '=',
                mnw_set_action('omb') . '&' . MNW_OMB_ACTION . '=');
  return array(false, array());
}

function mnw_handle_oauth() {
  if (isset($_REQUEST[MNW_OAUTH_ACTION])) {
        switch ($_REQUEST[MNW_OAUTH_ACTION]) {

        case 'requesttoken':
            $srv = new OMB_Service_Provider(get_own_profile(), new mnw_DataStore());
            $srv->oauth_requesttoken();
            return array(false, array());
            break;

        case 'userauthorization':
            global $user_level;
            get_currentuserinfo();
            if ($user_level < 10) {
              return array('userauth', array('error' => __('Not logged in or not admin.', 'mnw')));
            }
            $srv = new OMB_Service_Provider(get_own_profile(), new mnw_DataStore());
            try {
              $remote_user = $srv->oauth_userauthorization();
            } catch (Exception $e) {
              return array('userauth', array('error' => sprintf(__('Error while verifying the authorize request. Original error: %s', 'mnw'), $e->getMessage())));
            }
            common_ensure_session();
            $_SESSION['omb_provider'] = $srv;
            $_SESSION['omb_remote_user'] = $remote_user;
            return array('userauth', array('remote_user' => $remote_user));
            break;


        case 'userauth_continue':
            common_ensure_session();
            $srv = $_SESSION['omb_provider'];
            $usr = $_SESSION['omb_remote_user'];
            if (is_null($srv) || is_null($usr)) {
              return array('userauth', array('error' => __('Error with your session.', 'mnw')));
            }
            if (!wp_verify_nonce($_POST['nonce'], 'mnw_userauth_nonce')) {
              return array('userauth', array('error' => __('Error with your nonce.', 'mnw')));
            }
            if (!isset($_POST['profile']) || $_POST['profile'] !== $usr->getIdentifierURI() ) {
              return array('userauth', array('error' => __('Error with the profile parameter.', 'mnw')));
            }
            $accepted = isset($_POST['accept']) && !isset($_POST['reject']);
            list($redir, $val, $token) = $srv->continueUserauth($accepted);
            if ($accepted) {
              mnw_add_subscriber($usr, $token);
            }
            if ($redir) {
              wp_redirect($val, 303);
              return array(false, array());
            } else {
              return array('userauth_continue', array('token' => $val));
            }
            break;

        case 'accesstoken':
            $srv = new OMB_Service_Provider(get_own_profile(), new mnw_DataStore());
            $srv->accesstoken();
            return array(false, array());
            break;

        }
  } else {
    $ret = array('', array());
  }
  return $ret;
}

function mnw_handle_omb() {
  /* perform action mnw_omb_action. */
  if (isset($_REQUEST[MNW_OMB_ACTION])) {
        switch ($_REQUEST[MNW_OMB_ACTION]) {

        case 'updateprofile':
          $srv = new OMB_Service_Provider(get_own_profile(), new mnw_DataStore());
          $profile = $srv->updateprofile(new mnw_OMB_DataStore());
          return array(false, array());
          break;

        case 'postnotice':
          $srv = new OMB_Service_Provider(get_own_profile(), new mnw_DataStore());
          $notice = $srv->postnotice(new mnw_OMB_DataStore());
          mnw_receive_notice($notice);
          return array(false, array());
          break;
        }
  } else {
    $ret = array('', array());
  }
  return $ret;

}
?>
