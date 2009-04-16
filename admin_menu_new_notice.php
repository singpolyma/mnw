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

require_once 'lib.php';
require_once 'libomb/notice.php';
require_once 'omb_datastore.php';

function mnw_post_new_notice() {
    check_admin_referer('mnw-new_notice');

    global $wpdb;
    /* Insert notice into MNW_NOTICES_TABLE. */
    $insert = 'INSERT INTO ' . MNW_NOTICES_TABLE . " (content, created) VALUES ('%s', NOW())";
    $result = $wpdb->query($wpdb->prepare($insert, $_POST['mnw_notice']));
    if ($result == 0) {
        return 1;
    }

    $notice = new OMB_Notice(get_own_profile(), mnw_set_action('get_notice') . '&mnw_notice_id=' . $wpdb->insert_id, $_POST['mnw_notice'], '');

    $datastore = mnw_OMB_DataStore::getInstance();
    $result = $datastore->getSubscriptions(get_bloginfo('url'));

    if ($result === false) {
        return 2;
    }

    foreach($result as $subscriber) {
        try {
            $service = new OMB_Service_Consumer($subscriber['url'], get_bloginfo('url'), $datastore);
            $service->setToken($subscriber['token'], $subscriber['secret']);
            $service->postNotice($notice);
        } catch (Exception $e) {
            return 3;
        }
    }
    return 0;
}


function mnw_new_notice() {
  /* Perform action and display result message. */
  if (isset($_POST['doaction_active']) && $_POST['doaction_active'] == __('Send notice', 'mnw')) {
    echo '<div id="message" class="updated fade"><p>';
    switch(mnw_post_new_notice()) {
      case 0:
        _e('Notice sent.', 'mnw');
        break;
      case 1:
        _e('Error storing the notice.', 'mnw');
        break;
      case 2:
        _e('Error retrieving subscribers.', 'mnw');
        break;
      case 3:
        _e('Error sending notice.', 'mnw');
        break;
    }
    echo '</p></div>';
  }

  /* Get URL to this page via wordpress admin interface. */
  $this_url = 'admin.php?page=' .  basename(dirname(__FILE__)) . '/admin_menu_new_notice.php';

  mnw_start_admin_page();
?>
    <form method="post" action="<?php echo $this_url; ?>">
      <h3><?php _e('New notice', 'mnw'); ?></h3>
      <?php wp_nonce_field('mnw-new_notice'); ?>
      <textarea id="mnw_notice" name="mnw_notice" cols="45" rows="3" style="font-size: 2em; line-height: normal;"><?php if (isset($_POST['mnw_notice'])) echo $_POST['mnw_notice'];?></textarea>
      <br />
      <input type="submit" name="doaction_active" class="button-primary action" value="<?php _e('Send notice', 'mnw'); ?>" />
    </form>
<?php
    mnw_finish_admin_page();
}
?>
