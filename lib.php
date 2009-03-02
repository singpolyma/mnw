<?php
/**
 * This file is part of mnw. Other files in mnw may have different copyright notices.
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

/*
 * Laconica - a distributed open-source microblogging tool
 * Copyright (C) 2008, Controlez-Vous, Inc.
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

require_once 'libomb/profile.php';

function delete_subscription($url) {
    global $wpdb;
    return $wpdb->query('DELETE FROM ' . MNW_SUBSCRIBER_TABLE . ' WHERE url = "' . $url . '"');
}

function get_own_profile() {
  static $profile;
  if (is_null($profile)) {
    $profile = new OMB_Profile();
    $profile->identifier_uri = get_bloginfo('url');
    $profile->profile_url = get_bloginfo('url');
    $profile->nickname = get_option('omb_nickname');
    $profile->license_url = get_option('omb_license');
    $profile->fullname = get_option('omb_full_name');
    $profile->homepage = get_bloginfo('url');
    $profile->bio = get_option('omb_bio');
    $profile->location = get_option('omb_location');
    $profile->avatar_url = get_option('omb_avatar');
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

function mnw_append_param($url, $name, $val) {
    $newurl = $url;
    if (strrpos($newurl, '?') === false) {
        $newurl .= '?';
    } else {
        $newurl .= '&';
    }
    return $newurl . $name . '=' . $val;
}
?>
