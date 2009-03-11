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

require_once 'libomb/notice.php';
require_once 'libomb/service_consumer.php';
require_once 'omb_datastore.php';

class mnw_Notice extends OMB_Notice {

    protected $url;

    function __construct($content, $url) {
      /* URI needs database ID, hence setting it to blogpost $url until we know the ID. */
      parent::__construct(get_own_profile(), $url, $content);
      $this->url = $url;
    }

    static function fromPost($post) {
        $str = get_option('mnw_post_template');
        $vals = array(
                    'u' => get_permalink($post->ID),
#                    'd' => $post->post_date,
                    'n' => $post->post_title,
                    'e' => $post->post_excerpt,
                    'c' => $post->post_content);
        foreach ($vals as $char => $content) {
            $spleft = 140 - mb_strlen(preg_replace('/%\w/', '', $str));
            if ($spleft >= mb_strlen($content)) {
                $repl = $content;
            } else if ($spleft > 0) {
                $repl = substr($content, 0, $spleft - 1) . '…';
            } else {
                $repl = '';
            }
            $str = preg_replace('/%' . $char . '/', $repl, $str);
        }
        global $wpdb;
        $content = $wpdb->escape($str);
        $url = get_permalink($post->ID);
        return new mnw_Notice($content, $url);
    }

    function send() {
    global $wpdb;
    /* Insert notice into MNW_NOTICES_TABLE. */
    $insert = 'INSERT INTO ' . MNW_NOTICES_TABLE . " (url, content, created) VALUES ('%s', '%s', NOW())";
    $result = $wpdb->query($wpdb->prepare($insert, $this->url, $this->content));
    if ($result == 0) {
        return;
    }

    // Get all subscribers.
    $select = 'SELECT url, token, secret FROM ' . MNW_SUBSCRIBER_TABLE . ' WHERE token IS NOT NULL';
    $result = $wpdb->get_results($select, ARRAY_A);

    if ($result == 0) {
        return;
    }

    $this->uri = mnw_set_action('get_notice') . '&mnw_notice_id=' . $wpdb->insert_id;
    $this->param_array = false;

    foreach($result as $subscriber) {
        try {
            $service = new OMB_Service_Consumer($subscriber['url'], get_bloginfo('url'), new mnw_OMB_DataStore());
            $service->setToken($subscriber['token'], $subscriber['secret']);
            $service->postNotice($this);
        } catch (Exception $e) {
            continue;
        }
    }
}

}
