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

require_once 'mnw.php';

function mnw_subscribe_widget_register() {
    register_sidebar_widget(__('mnw Subscribe', 'mnw'), 'mnw_subscribe_widget');
    register_widget_control(__('mnw Subscribe', 'mnw'), 'mnw_subscribe_widget_control');
}
add_action('init', 'mnw_subscribe_widget_register');

function mnw_subscribe_widget($args) {
    extract($args);
    global $wpdb;
    $count = $wpdb->get_var('SELECT COUNT(*) FROM ' . MNW_SUBSCRIBER_TABLE);
    echo $before_widget;
?>
        <img style="float: left;" src="<?php echo get_template_directory_uri(); ?>/omb.png"/>
        <div style="<?php echo get_option('mnw_subscribe_style'); ?>">
            <?php printf(__ngettext('%d OMB subscriber', '%d OMB subscribers', $count, 'mnw'), $count); ?><br />
            <a href="<?php echo get_option('mnw_themepage_url'); ?>"><?php _e('Subscribe!', 'mnw'); ?></a>
        </div>
<?php
    echo $after_widget;
}

function mnw_subscribe_widget_control() {
  if (isset($_POST['mnw-subscribe-submit']) && $_POST['mnw-subscribe-submit'] === '1') {
    update_option('mnw_subscribe_style', $_POST['mnw-subscribe-style']);
  }
?>
  <p style="text-align:right;" class="mnw_field">
    <label for="mnw-subscribe-style"><?php _e('Style', 'mnw'); ?></label>
    <input id="mnw-subscribe-style" name="mnw-subscribe-style" type="text" value="<?php echo get_option('mnw_subscribe_style'); ?>" class="mnw_field" />
  </p>
  <input type="hidden" id="mnw-subscribe-submit" name="mnw-subscribe-submit" value="1" />
<?php
}
?>
