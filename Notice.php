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
require_once 'lib.php';

class mnw_Notice extends OMB_Notice {

    /* The URL of the mapped wordpress object. false for arbitrary messages. */
    protected $url;

    function __construct($content, $url = false) {
        /* URI needs database ID, hence setting it to blog $url until
           we know the ID. */
        parent::__construct(get_own_profile(), get_bloginfo('url'), $content);

        $this->url = $url;
        if ($url && get_option('mnw_as_seealso') === true) {
            $this->setSeealsoURL($url);
            $this->setSeealsoDisposition('link');
            $this->setSeealsoMediatype('text/html');
        }
    }

    static function fromPost($post) {
        $str = get_option('mnw_post_template');
        $vals = array(
                    'u' => get_permalink($post->ID),
                    'n' => $post->post_title,
                    'e' => $post->post_excerpt,
                    'c' => $post->post_content);
        foreach ($vals as $char => $content) {
            $spleft = 140 - mb_strlen(preg_replace('/%\w/', '', $str));
            if ($spleft >= mb_strlen($content)) {
                $repl = $content;
            } else if ($spleft > 0) {
                $repl = mb_substr($content, 0, $spleft - 1) . '…';
            } else {
                $repl = '';
            }
            $str = preg_replace("/%$char/", $repl, $str);
        }
        return new mnw_Notice($str, get_permalink($post->ID));
    }

    function send() {
        global $wpdb;
        /* Insert notice into MNW_NOTICES_TABLE. */
        if ($this->url === false) {
            $query = $wpdb->prepare('INSERT INTO ' . MNW_NOTICES_TABLE .
                    " (content, created) VALUES ('%s', NOW())",
                    $this->content);
        } else {
            $query = $wpdb->prepare('INSERT INTO ' . MNW_NOTICES_TABLE .
                    " (url, content, created) VALUES ('%s', '%s', NOW())",
                    $this->url, $this->content);
        }
        if ($wpdb->query($query) === 0) {
            throw new Exception(__('Error storing the notice.', 'mnw'));
        }

        $this->uri = mnw_set_action('get_notice') . '&mnw_notice_id=' . $wpdb->insert_id;
        $this->param_array = false;

        // Get all subscribers.
        $datastore = mnw_OMB_DataStore::getInstance();
        $result = $datastore->getSubscriptions(get_bloginfo('url'));

        if ($result === false) {
            throw new Exception(__('Error retrieving subscribers.', 'mnw'));
        }

        if (is_null($result)) {
            return array();
        }

        $err = array();
        foreach($result as $subscriber) {
            try {
                $service = new OMB_Service_Consumer($subscriber['url'], get_bloginfo('url'), $datastore);
                $service->setToken($subscriber['token'], $subscriber['secret']);
                $service->postNotice($this);
            } catch (Exception $e) {
                $err[] = $subscriber;
                continue;
            }
        }
        return $err;
    }
}
?>
