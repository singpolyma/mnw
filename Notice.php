<?
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

class mnw_Notice {

    private $content;
    private $uri;

    function __construct($content, $uri) {
        $this->content = $content;
        $this->uri = $uri;
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
            $spleft = 140 - strlen(preg_replace('/%\w/', '', $str));
            if ($spleft >= strlen($content)) {
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
        $uri = get_permalink($post->ID);
        return new mnw_Notice($content, $uri);
    }

    function send() {
    global $wpdb;
    /* Insert notice into MNW_NOTICES_TABLE. */
    $insert = 'INSERT INTO ' . MNW_NOTICES_TABLE . " (uri, content, created) VALUES ('$this->uri', '$this->content', '" . common_sql_now() . "')";
    $result = $wpdb->query($insert);

    if ($result == 0) {
        return;
    }

    // Get all subscribers.
    $select = "SELECT url, token, secret FROM " . MNW_SUBSCRIBER_TABLE;
    $result = $wpdb->get_results($select, ARRAY_A);

    if ($result == 0) {
        return;
    }

    $omb_params = array(
                    'omb_listenee' => get_bloginfo('url'),
                    'omb_notice' => mnw_append_param(get_option('mnw_themepage_url'), MNW_ACTION, 'get_notice') . '&mnw_notice_id=' . $wpdb->insert_id,
                    'omb_notice_content' => $this->content);

    foreach($result as $subscriber) {
        try {
            $result = perform_omb_action($subscriber['url'], 'http://openmicroblogging.org/protocol/0.1/postNotice', $subscriber['token'], $subscriber['secret'], $omb_params);
            if ($result->status == 403) { # not authorized, don't send again
                delete_subscription($subscriber['url']);
            } else if ($result->status != 200) {
                print_r($req);
                print_r($result);
            }
        } catch (Exception $e) {
            continue;
        }
    }
}

}