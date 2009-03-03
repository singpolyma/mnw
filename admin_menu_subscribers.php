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

function mnw_subscribers_parse_action() {
  if (!isset($_REQUEST['action']) || empty($_REQUEST['action']) ) {
    return array();
  }

  $action = $_REQUEST['action'];
  switch ($action) {
  case 'delete':
    if (isset($_REQUEST['subscriber'])) {
      global $wpdb;
      $subscriber = $wpdb->escape($_REQUEST['subscriber']);
      check_admin_referer('mnw-delete-subscriber_' . $subscriber);
      $_ret = delete_subscription_by_id($subscriber);
      return array($_ret, $subscriber);
    }
    break;
  case 'delete-selected':
    check_admin_referer('mnw-bulk-subscribers');
    $subscribers = ($_POST['checked']);
      global $wpdb;
      return array(
        $wpdb->query('DELETE FROM ' . MNW_SUBSCRIBER_TABLE . ' WHERE id = "' . implode('" OR id = "', $subscribers) . '"'),
        $subscribers);
    break;
  }
}

function mnw_subscribers_options() {
  /* Perform action and display result message. */
  $actionstatus = mnw_subscribers_parse_action();
  if ($actionstatus !== array()) {
    echo '<div id="message" class="updated fade"><p>';
    if ($actionstatus[0] !== count($actionstatus[1])) {
      echo __ngettext('Could not delete subscriber.', 'Could not delete subscribers.', count($actionstatus[1]), 'mnw');
    } else {
      echo __ngettext('Subscriber successfully deleted.', 'Subscribers successfully deleted.', count($actionstatus[1]), 'mnw');
    }
    echo '</p></div>';
  }

  /* Get URL to this page via wordpress admin interface. */
  $this_url = 'admin.php?page=' .  basename(dirname(__FILE__)) . '/admin_menu_subscribers.php';

  /* Get subscribers. */
  global $wpdb;
  $subscribers = $wpdb->get_results('SELECT id, url FROM ' . MNW_SUBSCRIBER_TABLE,
                                    ARRAY_A);

?>
<div class="wrap">
    <h2>mnw</h2>
<p><?php _e('Subscribers are users of an OMB service who listen to your notices. Note that OMB does not yet support a block feature. Therefore the remote user is not informed about the deletion. Moreover, if another user from the same service is subscribed to you, the remote service will probably publish your messages to deleted subscribers as well.', 'mnw');?></p>
    <form method="post" action="<?php echo $this_url; ?>">
      <h3><?php _e('OMB subscribers', 'mnw'); ?></h3>
      <?php wp_nonce_field('mnw-bulk-subscribers') ?>
      <div class="tablenav">
        <div class="alignleft actions">
          <select name="action">
            <option value="" selected="selected"><?php _e('Bulk Actions'); ?></option>
            <option value="delete-selected"><?php _e('Delete'); ?></option>
          </select>
          <input type="submit" name="doaction_active" value="<?php _e('Apply'); ?>" class="button-secondary action" />
        </div>
      </div>
      <div class="clear" />
<table class="widefat" cellspacing="0" id="subscribers-table">
  <thead>
  <tr>
    <th scope="col" class="check-column"><input type="checkbox" /></th>
    <th scope="col"><?php _e('Subscriber', 'mnw'); ?></th>
    <th scope="col" class="action-links"><?php _e('Action'); ?></th>
  </tr>
  </thead>

  <tfoot>
  <tr>
    <th scope="col" class="check-column"><input type="checkbox" /></th>
    <th scope="col"><?php _e('Subscriber', 'mnw'); ?></th>
    <th scope="col" class="action-links"><?php _e('Action'); ?></th>
  </tr>
  </tfoot>

  <tbody class="subscribers">

<?php
    if ($subscribers == 0) {
?>
    <tr>
      <td colspan="3"> <?php _e('No subscribers', 'mnw'); ?></td>
    </tr>
<?php
    } else {
      foreach ($subscribers as $subscriber) {
?>
      <tr>
        <th scope='row' class='check-column'><input type='checkbox' name='checked[]' value='<?php echo $subscriber['id']; ?>' /></th>
        <td><a href="<?php echo $subscriber['url'];?>"><?php echo $subscriber['url'];?></a></td>
        <td class='togl action-links'>
          <a href="<?php echo wp_nonce_url(
              $this_url . '&amp;action=delete' . '&amp;subscriber=' . $subscriber['id'],
              'mnw-delete-subscriber_' . $subscriber['id']); ?>"
             title="<?php _e('Delete this subscriber', 'mnw'); ?>">
            <?php _e('Delete', 'mnw'); ?>
          </a>
        </td>
      </tr>
<?php
      }
    }
?>
    </tbody>
</table>
    </form>
</div>
<?php
}
?>
