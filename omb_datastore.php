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

require_once 'libomb/datastore.php';
require_once 'libomb/profile.php';

class mnw_OMB_DataStore implements OMB_DataStore {
  public function getProfile($identifierURI) {
    global $wpdb;
    $result = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . MNW_SUBSCRIBER_TABLE .
                                               " WHERE uri = '%s'", $identifierURI));
    if (!$result) {
      return null;
    }
    $profile = new OMB_Profile($result->uri);
    $profile->setProfileURL($result->url);
    $profile->setLicenseURL($result->license);
    $profile->setNickname($result->nickname);
    $profile->setFullname($result->fullname);
    $profile->setLocation($result->location);
    $profile->setBio($result->bio);
    $profile->setHomepage($result->homepage);
    $profile->setAvatarURL($result->avatar);
    return $profile;
  }

  public function saveProfile($profile, $overwrite = false) {
    global $wpdb;
    if ($wpdb->query($wpdb->prepare('SELECT * FROM ' . MNW_SUBSCRIBER_TABLE . ' ' .
                             "WHERE uri = '%s'", $profile->getIdentifierURI)) > 0) {
      if (!$overwrite) {
        throw new Exception();
      }
      $query = "UPDATE " . MNW_SUBSCRIBER_TABLE . " SET url = '%s', " .
                  "fullname = '%s', location = '%s', bio = '%s', homepage = '%s', " .
                  "license = '%s', nickname = '%s', avatar = '%s' where uri = '%s'";
    } else {
      $query = "INSERT INTO " . MNW_SUBSCRIBER_TABLE . " (url, " .
                "secret, fullname, location, bio, homepage, license, nickname, " .
                "avatar, uri) " .
                "VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')";
    }
    $wpdb->query($wpdb->prepare($query, $profile->getProfileURL(),
                   $profile->getFullname(), $profile->getLocation(),
                   $profile->getBio(), $profile->getHomepage(),
                   $profile->getLicenseURL(), $profile->getNickname(),
                   $profile->getAvatarURL(), $profile->getIdentifierURI()));
  }

  /* get OMB_Profile, return array of identifier_uris */
  public function getSubscribers($profile) {
    global $wpdb;
    $myself = get_own_profile();
    if ($profile->getIdentifierURI() !== $myself->getIdentifierURI()) {
      if($wpdb->get_var('SELECT resubtoken FROM ' . MNW_SUBSCRIBER_TABLE . " WHERE uri = '" . $profile->getIdentifierURI() . "'")) {
        return array($myself->getIdentifierURI());
      } else {
        return array();
      }
    } else {
      return $wpdb->get_col('SELECT uri FROM ' . MNW_SUBSCRIBER_TABLE . ' WHERE token IS NOT NULL');
    }
  }

  public function deleteSubscription($subscriberURI, $subscribedUserURI) {
    global $wpdb;
    $me = get_own_profile()->getIdentifierURI();
    if ($me == $subscribedUserURI) {
      $query = 'UPDATE ' . MNW_SUBSCRIBER_TABLE . " SET token = null, secret = null WHERE uri = '%s'";
      $user = $subscriberURI;
    } else {
      $query = 'UPDATE ' . MNW_SUBSCRIBER_TABLE . " SET resubtoken = null, resubsecret = null WHERE uri = '%s'";
      $user = $subscribedUserURI;
    }
    return $wpdb->query($wpdb->prepare($query, $user));
  }

}
?>
