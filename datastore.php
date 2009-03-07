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
require_once 'OAuth.php';
require_once 'libomb/omb_oauth_datastore.php';

class mnw_DataStore extends omb_OAuthDataStore {
    // We keep a record of who's contacted us

    function lookup_consumer($consumer_key)
    {
        return new OAuthConsumer($consumer_key, '');
    }

    function lookup_token($consumer, $token_type, $token_key)
    {
        $ret = $this->_lookup_token($consumer->key, $token_key);
        if ($ret && (($ret['type'] !== '3') ^ ($token_type === 'access'))) {
            return new OAuthToken($token_key, $ret['secret']);
        } else {
            return null;
        }
    }

    private function _lookup_token($consumer, $key) {
        global $wpdb;
        $sql = "SELECT secret, type FROM " . MNW_TOKENS_TABLE .
              " WHERE consumer = '$consumer'" .
              " AND token = '$key'";
        return $wpdb->get_row($sql, ARRAY_A);
    }

    function lookup_nonce($consumer, $token, $nonce, $timestamp)
    {
        global $wpdb;
        if ($wpdb->query("SELECT * FROM " . MNW_NONCES_TABLE . " WHERE nonce = '$nonce'") === 1) { 
            return true;
        } else {
            $wpdb->query("INSERT INTO " . MNW_NONCES_TABLE . " VALUES ('$nonce')");
            return false;
        }
    }

    function new_request_token($consumer)
    {
        global $wpdb;
        $token = common_good_rand(16);
        $secret = common_good_rand(16);
        $sql = "INSERT INTO " . MNW_TOKENS_TABLE . " (consumer, token, secret, type) " .
               "VALUES ('$consumer->key', '$token', '$secret', '0')";
        if (!$wpdb->query($sql)) {
            return null;
        } else {
            return new OAuthToken($token, $secret);
        }
    }

    function fetch_request_token($consumer)
    {
        throw new Exception();
    }

    function new_access_token($token, $consumer)
    {
        $request = $this->_lookup_token($consumer->key, $token->key);
        if (!$request || $request['type'] !== '1') {
          return null;
        }

        global $wpdb;
        $ntoken = common_good_rand(16);
        $nsecret = common_good_rand(16);
        $sql = "INSERT INTO " . MNW_TOKENS_TABLE . " (consumer, token, secret, type) " .
               "VALUES ('$consumer->key', '$ntoken', '$nsecret', '3')";
        if (!$wpdb->query($sql)) {
            return null;
        }

        if (!$wpdb->query("UPDATE " . MNW_TOKENS_TABLE . " SET type = '2' " . 
                          "WHERE consumer = '$consumer->key' " .
                          "AND token = '$token->key'")) {
            return null;
        }

        return new OAuthToken($ntoken, $nsecret);
    }

    function fetch_access_token($consumer)
    {
        throw new Exception();
    }

    public function revoke_token($consumer, $token_key) {
        global $wpdb;
        $wpdb->query('DELETE FROM ' . MNW_TOKENS_TABLE .
                    " WHERE consumer = '$consumer->key' AND token = '$token_key'");
    }

    public function authorize_token($consumer, $token_key) {
        global $wpdb;
        $wpdb->query("UPDATE " . MNW_TOKENS_TABLE . " SET type = '1' " .
                     "WHERE consumer = '$consumer->key' " .
                     "AND token = '$token_key' AND type = '0'");
    }

}
?>
