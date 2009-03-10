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

require_once 'mnw.php';

function mnw_widgets_register() {
    register_sidebar_widget(__('mnw Subscribe', 'mnw'), 'mnw_subscribe_widget');
    register_widget_control(__('mnw Subscribe', 'mnw'), 'mnw_subscribe_widget_control');
    register_sidebar_widget(__('mnw Notices', 'mnw'), 'mnw_notices_widget');
    register_widget_control(__('mnw Notices', 'mnw'), 'mnw_notices_widget_control');
}
add_action('init', 'mnw_widgets_register');

function mnw_subscribe_widget($args) {
    extract($args);
    global $wpdb;
    $count = $wpdb->get_var('SELECT COUNT(*) FROM ' . MNW_SUBSCRIBER_TABLE . ' WHERE token is not null');
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

function mnw_notices_widget($args) {
    extract($args);

    $options = get_option('mnw_notices_widget');
    $title = apply_filters('widget_title', $options['title']);
    $entry_count = (int) $options['entry_count'];
    $only_direct = $options['only_direct'];
    $strip_at = $options['strip_at'];
    $new_on_top = $options['new_on_top'];
    $template = stripslashes($options['template']);

    $values = array('%t' => 'notice.content',
                    '%u' => 'notice.url',
                    '%c' => 'notice.created',
                    '%n' => 'author.nickname',
#                    '%f' => 'author.fullname',
                    '%v' => 'author.avatar',
                    '%a' => 'author.url');

    $selects = '';
    foreach ($values as $k => $v) {
      $selects .= "$v as '$k', ";
    }
    $selects = substr($selects, 0, strlen($selects) - 2);

    global $wpdb;
    $notices = $wpdb->get_results("SELECT $selects FROM " . MNW_FNOTICES_TABLE . ' as notice, ' . MNW_SUBSCRIBER_TABLE . ' AS author ' .
                                  'WHERE ' . ($only_direct ? 'notice.to_us = 1 AND' : '') . ' notice.user_id = author.id ' .
                                  'ORDER BY notice.created ' . ($new_on_top ? 'DESC' : 'ASC') . ' LIMIT ' . $entry_count, ARRAY_A);
    echo $before_widget;
    echo $before_title . $title . $after_title;
?>
        <div>
<?php
if ($notices) {
echo "<ul>";
      foreach($notices as $notice) {
        $notice['%c'] = date('d. F Y H:i:s', strtotime($notice['%c']));
        if ($strip_at) {
          $notice['%t'] = preg_replace('/^(?:T |@)' . get_option('omb_nickname') . '(?::\s*|\s*([A-Z]))/', '\1', $notice['%t']);
        }
        echo '<li>' . str_replace(array_keys($notice), array_values($notice), $template) . '</li>';
      }
echo "</ul>";
} else {
  _e('No notices.', 'mnw');
}
?>
        </div>
<?php
    echo $after_widget;
}

function mnw_notices_widget_control() {
  if (isset($_POST['mnw-notices-submit']) && $_POST['mnw-notices-submit'] === '1') {
    $options = array('title' => $_POST['mnw-title'], 'entry_count' => $_POST['mnw-entry_count'],
                     'only_direct' => $_POST['mnw-only_direct'], 'strip_at' => $_POST['mnw-strip_at'],
                     'template' => $_POST['mnw-template'], 'new_on_top' => $_POST['mnw-new_on_top']);
    update_option('mnw_notices_widget', $options);
  }
  $options = get_option('mnw_notices_widget');
?>
  <p style="text-align:right;" class="mnw_field">
    <label for="mnw-title"><?php _e('Title', 'mnw'); ?></label>
    <input id="mnw-title" name="mnw-title" type="text" value="<?php echo $options['title']; ?>" class="mnw_field" />
  </p>
  <p style="text-align:right;" class="mnw_field">
    <label for="mnw-entry_count"><?php _e('Entry count', 'mnw'); ?></label>
    <input id="mnw-entry_count" name="mnw-entry_count" type="text" value="<?php echo $options['entry_count']; ?>" class="mnw_field" />
  </p>
  <p style="text-align:right;" class="mnw_field">
    <label for="mnw-only_direct"><?php _e('Show only direct messages?', 'mnw'); ?></label>
    <input id="mnw-only_direct" name="mnw-only_direct" type="checkbox" <?php if ($options['only_direct']) echo 'checked="checked"'; ?> class="mnw_field" />
  </p>
  <p style="text-align:right;" class="mnw_field">
    <label for="mnw-strip_at"><?php _e('Strip @ parts at the beginning of the notice?', 'mnw'); ?></label>
    <input id="mnw-strip_at" name="mnw-strip_at" type="checkbox" <?php if ($options['strip_at']) echo 'checked="checked"'; ?> class="mnw_field" />
  </p>
  <p style="text-align:right;" class="mnw_field">
    <label for="mnw-new_on_top"><?php _e('Show new notices on top?', 'mnw'); ?></label>
    <input id="mnw-new_on_top" name="mnw-new_on_top" type="checkbox" <?php if ($options['new_on_top']) echo 'checked="checked"'; ?> class="mnw_field" />
  </p>
  <p style="text-align:right;" class="mnw_field">
    <label for="mnw-template"><?php _e('Entry template', 'mnw'); ?></label>
    <textarea id="mnw-template" name="mnw-template" rows="3" class="mnw_field"><?php /* The AJAX stuff is not able to handle ' and " in params */ echo preg_replace("/'/", '"', stripslashes($options['template'])); ?></textarea>
  </p>
  <input type="hidden" id="mnw-notices-submit" name="mnw-notices-submit" value="1" />
<?php
}
?>
