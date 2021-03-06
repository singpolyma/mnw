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
class mnw_Themepage {
    protected $data;
    public $shouldDisplay;
    /*
     * Parse requests to the main microblog page.
     */
    public function __construct() {
        /* Assure that we have a valid themepage setting. */
        if (get_option('mnw_themepage_url') == '') {
            /* Since this method is only called from the themepage, we can
               just copy the current url if something‘s broken. */
            global $wp_query;
            update_option('mnw_themepage_url', $wp_query->post->guid);
        }
        $this->data = $this->parseRequest();
        $this->shouldDisplay = ($this->data[0] !== false);
    }
    protected static function parseRequest() {
        if (!isset($_REQUEST[MNW_ACTION])) {
            /* No action at all – display the standard page. */
            return array('', array());
        }
        /* Hash containing file and procedure name to handle the request. */
        $actions = array(
                    'subscribe' => array('subscribe.php', 'mnw_parse_subscribe'),
                    'get_notice' => array('get_notice.php', 'mnw_get_notice'),
                    'xrds' => array('mnw_provider.php', 'mnw_get_xrds'),
                    'oauth' => array('mnw_provider.php', 'mnw_handle_oauth'),
                    'omb' => array('mnw_provider.php', 'mnw_handle_omb'),
                    'notices' => array('mnw-notices.php', 'mnw_notices'));
        if (!isset($actions[$_REQUEST[MNW_ACTION]])) {
            /* Save a poor user who entered a wrong action. */
            wp_redirect(get_option('mnw_themepage_url'));
            return array(false, array());
        }
        /* Load file and call method for this action request. */
        $action = $actions[$_REQUEST[MNW_ACTION]];
        require_once $action[0];
        return $action[1]();
    }
    /* $continue is a string from the set ('', 'subscribe', 'get_notice', 'userauth', 'userauth_continue', 'notices').
       $data contains additional data generated by the plugin. */
    public function render() {
        $continue = $this->data[0];
        $data = $this->data[1];
        echo '<p>';
        printf(__('%s supports the <a href="//openmicroblogging.org"
title="Official website of the OpenMicroBlogging standard">
OpenMicroBlogging</a> standard. ', 'mnw'),
               get_bloginfo('title'));
        _e('It uses the free plugin <a href="//adrianlang.de/mnw"
title="Official website of mnw">mnw</a> for OMB functionality.', 'mnw');
?>
        </p>
        <ul>
            <li><a href="<?php echo attribute_escape(get_option('mnw_themepage_url')); ?>" title="<?php _e('Go to the subscribe form', 'mnw'); ?>"><?php _e('Subscribe', 'mnw'); ?></a></li>
            <li><a href="<?php echo attribute_escape(mnw_set_action('notices') . '&type=sent'); ?>" title="<?php _e('Display sent notices', 'mnw'); ?>"><?php _e('Sent notices', 'mnw'); ?></a></li>
            <li><a href="<?php echo attribute_escape(mnw_set_action('notices') . '&type=received'); ?>" title="<?php _e('Display received notices', 'mnw'); ?>"><?php _e('Received notices', 'mnw'); ?></a></li>
        </ul>
<?php
        switch ($continue) {
        case '':
        case 'subscribe':
            /* Gather data for subscribe form. */
            global $wp_query;
            $action = attribute_escape(mnw_set_action('subscribe') . '&' .
                                        MNW_SUBSCRIBE_ACTION . '=continue');
            /* Start displaying the form. */
            echo '<h3>' . __('Subscribe', 'mnw') . '</h3>';
            echo '<p>';
            printf(__('If you have an user account at another OMB service
like <a href="//identi.ca" title="identi.ca, the largest
open microblogging service">identi.ca</a>, you can
easily subscribe to %s.', 'mnw'), get_bloginfo('name'));
            echo '</p>';
            echo '<p>';
            _e('To subscribe, just enter the URL of your profile at another OMB
service. ', 'mnw');
            _e('You will be asked to log in there if you are not yet. ', 'mnw');
            printf(__('There will be a confirmation prompt showing details of
%s.', 'mnw'), get_bloginfo('name'));
            echo '</p>';
            if (isset($data['error'])) {
                echo '<p>';
                printf(__('Error: %s', 'mnw'), $data['error']);
                echo '</p>';
            }
?>
            <form id='omb-subscribe' method='post' action='<?php echo $action; ?>'>
                <label for="profile_url"><?php _e('OMB Profile URL', 'mnw'); ?>
                </label>
                <input name="profile_url" type="text" class="input_text"
                       id="profile_url" value='<?php
                        if (isset($data['profile_url']))
                            echo $data['profile_url']; ?>'/>
                <input type="submit" id="submit" name="submit" class="submit"
                       value="<?php _e('Subscribe', 'mnw'); ?>"/>
            </form>
<?php
            break;
        case 'get_notice':
            if (isset($data['notice']) && $data['notice'] !== false) {
?>
                <h3><?php printf(__('Notice from %s', 'mnw'), date(
                          __('d F Y H:i:s', 'mnw'),
                          strtotime($data['notice']->created)));?></h3>
                <p style="font-size: 2em; margin-left: 0.5em;">
<?php
                if ($data['notice']->url !== null) {
                    echo "<a href='" . $data['notice']->url . "'>";
                }
                echo $data['notice']->content;
                if ($data['notice']->url !== null) {
                    echo "</a>";
                }
?>
                </p>
<?php
            } else {
?>
                <h3><?php _e('Notice', 'mnw'); ?></h3>
                <p><?php _e('Notice not found.', 'mnw'); ?></p>
<?php
            }
            break;
        case 'userauth':
?>
            <h3><?php _e('Authorize subscription', 'mnw'); ?></h3>
            <p>
<?php
            if (isset($data['error'])) {
                echo $data['error'];
            } else {
                $uri = $data['remote_user']->getIdentifierURI();
                $action = attribute_escape(mnw_set_action('oauth') . '&' .
                          MNW_OAUTH_ACTION . '=userauth_continue');
?>
            <form id="mnw_userauthorization" name="mnw_userauthorization"
                      method="post" action="<?php echo $action; ?>">
                <p><?php printf(__('Do you really want to subscribe %s?',
                                'mnw'), '<a href="' .
                                $data['remote_user']->getProfileURL() . '">' .
                                $uri . '</a>');?></p>
                <input id="profile" type="hidden" value="<?php echo $uri; ?>"
                       name="profile"/>
                <input id="nonce" type="hidden" name="nonce"
                       value="<?php echo wp_create_nonce('mnw_userauth_nonce'); ?>"/>
                <input id="accept" class="submit" type="submit" title=""
                       value="<?php _e('Yes', 'mnw'); ?>" name="accept"/>
                <input id="reject" class="submit" type="submit" title=""
                       value="<?php _e('No', 'mnw'); ?>" name="reject"/>
            </form>
<?php
            }
?>
            </p>
<?php
            break;
        case 'userauth_continue':
?>
            <h3><?php _e('Authorization granted', 'mnw'); ?></h3>
            <p>
<?php
            if ($data['token'] !== '') {
                printf(__('Confirm the subscribee‘s service that token %s is
authorized.', 'mnw'), $data['token']);
            } else {
                _e('You rejected the subscription.', 'mnw');
            }
            echo '</p>';
            break;

        case 'notices':
            $show_sent = ($data[0] == 'sent');
            $paged = $data[1];
            $total = $data[2];
?>
            <h3><?php if ($show_sent) { _e('Sent notices', 'mnw'); } else { _e('Received notices', 'mnw');} ?></h3>
            <div>
                <ul>
<?php
            if ($data[3] !== null) {
                foreach($data[3] as $notice) {
                echo '<li>';
                if ($show_sent) {
                printf('„%s“ @ <a href="%s">%s</a>', $notice['content'], $notice['noticeurl'], $notice['created']);
                } else {
                printf('„%s“<div><a href="%s" title="%s">%s</a> @ <a href="%s">%s</a></div>', $notice['content'], $notice['authorurl'], $notice['fullname'], $notice['nickname'], $notice['noticeurl'], $notice['created']);
                }
                echo '</li>';
                }
            }

?>
                </ul>
        <div style="float: right;">
            <span>
<?php
                printf(__('Displaying %s–%s of %s', 'mnw'),
                    number_format_i18n(($paged - 1) * 15 + 1),
                    number_format_i18n(min($paged * 15, $total)),
                    number_format_i18n($total));
?>
            </span>
<?php
            echo paginate_links(array(
                'base' => add_query_arg( 'paged', '%#%' ),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => ceil($total / 15.0),
                'current' => $paged));
?>
  </div>
               <a href="<?php echo attribute_escape(mnw_set_action('notices') . "&type=$data[0]&format=atom"); ?>" title="<?php _e('Display Atom feed of notices', 'mnw');?>"><?php _e('Atom feed', 'mnw'); ?></a>
            </div>
<?php
            break;
        }
    }
}
?>
