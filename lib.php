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

function delete_subscription($url) {
    global $wpdb;
    return $wpdb->query('DELETE FROM ' . MNW_SUBSCRIBER_TABLE . ' WHERE url = "' . $url . '"');
}

function delete_subscription_by_id($id) {
    global $wpdb;
    return $wpdb->query('DELETE FROM ' . MNW_SUBSCRIBER_TABLE . ' WHERE id = "' . $id . '"');
}

function get_own_profile() {
  static $profile;
  if (is_null($profile)) {
    $profile = new OMB_Profile(get_bloginfo('url'));
    $profile->setProfileURL(get_bloginfo('url'));
    $profile->setNickname(get_option('omb_nickname'));
    $profile->setLicenseURL(get_option('omb_license'));
    $profile->setFullname(get_option('omb_full_name'));
    $profile->setHomepage(get_bloginfo('url'));
    $profile->setBio(get_option('omb_bio'));
    $profile->setLocation(get_option('omb_location'));
    $profile->setAvatarURL(get_option('omb_avatar'));
  }
  return $profile;
}

function common_sql_now()
{
    return strftime('%Y-%m-%d %H:%M:%S', time());
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

function mnw_append_param($url, $name, $val) {
    $newurl = $url;
    if (strrpos($newurl, '?') === false) {
        $newurl .= '?';
    } else {
        $newurl .= '&';
    }
    return $newurl . $name . '=' . $val;
}

function mnw_add_subscriber($profile, $token) {
    global $wpdb;
    $select = "SELECT * FROM " . MNW_SUBSCRIBER_TABLE . " WHERE url = '" . $profile->getProfileURL() . "'";
    if ($wpdb->query($select) > 0) {
        $query = "UPDATE " . MNW_SUBSCRIBER_TABLE . " SET " .
                    "resubtoken= '" . $wpdb->escape($token->key) . "', " .
                    "resubsecret= '" . $wpdb->escape($token->secret) . "', " .
                    "license = '" . $wpdb->escape($profile->getLicenseURL()) . "', " .
                    "nickname = '" . $wpdb->escape($profile->getNickname()) . "' " .
                    "where url = '" . $profile->getProfileURL() . "'";
    } else {
        $query = "INSERT INTO " . MNW_SUBSCRIBER_TABLE . " (url, resubtoken, resubsecret, license, nickname) " .
                  "VALUES ('" . $profile->getProfileURL() . "', " .
                    "'" . $wpdb->escape($token->key) . "', " .
                    "'" . $wpdb->escape($token->secret) . "', " .
                    "'" . $wpdb->escape($profile->getLicenseURL()) . "', " .
                    "'" . $wpdb->escape($profile->getNickname()) . "')";
    }
    $results = $wpdb->query($query);
}
?>
