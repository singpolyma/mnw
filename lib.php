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

/* Stuff called common_ is copied from laconica/lib/util.php */

require_once 'libomb/profile.php';

function mnw_receive_notice($notice) {
    global $wpdb;
    $insert = 'INSERT INTO ' . MNW_FNOTICES_TABLE . " (uri, url, user_id, content, created, to_us) " .
              "SELECT '%s', '%s', user.id, '%s', NOW(), '%s' FROM " . MNW_SUBSCRIBER_TABLE . " AS user WHERE user.uri = '%s'";
    $insert2 = $wpdb->prepare($insert, $notice->getIdentifierURI(), $notice->getURL(), $notice->getContent(), mnw_is_to_us($notice->getContent()) ? '1' : '0', $notice->getAuthor()->getIdentifierURI());
    return $wpdb->query($insert2);
}

function delete_subscription($url) {
    global $wpdb;
    return $wpdb->query('UPDATE ' . MNW_SUBSCRIBER_TABLE . ' SET token = null, secret = null WHERE url = "' . $url . '"');
}

function delete_remote_user_by_id($id) {
    global $wpdb;
    return $wpdb->query('UPDATE ' . MNW_SUBSCRIBER_TABLE . ' SET token = null, secret = null, resubtoken = null, resubsecret = null WHERE id = "' . $id . '"');
}

function mnw_is_to_us($content) {
  return preg_match('/(^T |@)' . get_option('omb_nickname') . '/', $content);
}

function get_own_profile() {
  static $profile;
  if (is_null($profile)) {
    $profile = new OMB_Profile(get_bloginfo('url'));
    $profile->setProfileURL(get_bloginfo('url'));
    $profile->setHomepage(get_bloginfo('url'));
    if($v = get_option('omb_nickname')) $profile->setNickname($v);
    if($v = get_option('omb_license')) $profile->setLicenseURL($v);
    if($v = get_option('omb_full_name')) $profile->setFullname($v);
    if($v = get_option('omb_bio')) $profile->setBio($v);
    if($v = get_option('omb_location')) $profile->setLocation($v);
    if($v = get_option('omb_avatar')) $profile->setAvatarURL($v);
  }
  return $profile;
}

function common_have_session() {
    return (0 != strcmp(session_id(), ''));
}

function common_ensure_session() {
    if (!common_have_session()) {
        @session_start();
    }
}

function common_good_rand($bytes)
{
    // XXX: use random.org...?
    if (file_exists('/dev/urandom')) {
        return common_urandom($bytes);
    } else { // FIXME: this is probably not good enough
        return common_mtrand($bytes);
    }
}

function common_urandom($bytes)
{
    $h = fopen('/dev/urandom', 'rb');
    // should not block
    $src = fread($h, $bytes);
    fclose($h);
    $enc = '';
    for ($i = 0; $i < $bytes; $i++) {
        $enc .= sprintf("%02x", (ord($src[$i])));
    }
    return $enc;
}

function common_mtrand($bytes)
{
    $enc = '';
    for ($i = 0; $i < $bytes; $i++) {
        $enc .= sprintf("%02x", mt_rand(0, 255));
    }
    return $enc;
}

function mnw_set_action($action) {
    $themepage = get_option('mnw_themepage_url');
    if (strrpos($themepage, '?') === false) {
        $themepage .= '?';
    } else {
        $themepage .= '&';
    }
    return $themepage . MNW_ACTION . '=' . $action;
}

function mnw_set_param($url, $param_name, $newval) {
    if (strpos($url, $param_name) !== false) {
        return preg_replace("/([?&]$param_name=)[^&]*/", '${1}' . $newval, $url);
    }
    return $url . (strpos($url, '?') === false ? '?' : '&') . $param_name . '=' . $newval;
}
?>
