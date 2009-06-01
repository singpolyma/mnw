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

function mnw_remote_users_parse_action() {
  if (!isset($_REQUEST['action']) || empty($_REQUEST['action']) ) {
    return array();
  }

  $action = $_REQUEST['action'];
  switch ($action) {
  case 'delete':
    if (isset($_REQUEST['user'])) {
      global $wpdb;
      $user = $wpdb->escape($_REQUEST['user']);
      check_admin_referer('mnw-delete-user_' . $user);
      $_ret = delete_remote_user_by_id($user);
      return array($_ret, $user);
    }
    break;
  case 'delete-selected':
    check_admin_referer('mnw-bulk-users');
    $users = ($_POST['checked']);
      global $wpdb;
      return array(
        $wpdb->query('UPDATE ' . MNW_SUBSCRIBER_TABLE . ' SET secret = NULL, token = NULL, resubsecret = NULL, resubtoken = NULL WHERE id = "' . implode('" OR id = "', $users) . '"'),
        $users);
    break;
  }
}

function mnw_remote_users_options() {
  /* Perform action and display result message. */
  $actionstatus = mnw_remote_users_parse_action();
  if ($actionstatus !== array()) {
    echo '<div id="message" class="updated fade"><p>';
    if ($actionstatus[0] !== count($actionstatus[1])) {
      echo __ngettext('Could not delete remote user.', 'Could not delete remote users.', count($actionstatus[1]), 'mnw');
    } else {
      echo __ngettext('Remote user successfully deleted.', 'Remote users successfully deleted.', count($actionstatus[1]), 'mnw');
    }
    echo '</p></div>';
  }

  if (isset($_REQUEST['paged'])) {
    $paged = (int) $_REQUEST['paged'];
  } else {
    $paged = 1;
  }

  /* Get remote users. */
  global $wpdb;
  $users = $wpdb->get_results('SELECT SQL_CALC_FOUND_ROWS id, nickname, url, token, resubtoken, license FROM ' . MNW_SUBSCRIBER_TABLE .
                              ' WHERE token is not null or resubtoken is not null ' .
                              'LIMIT ' . floor(($paged - 1) * 15) . ', 15',
                              ARRAY_A);

  $total = $wpdb->get_var('SELECT FOUND_ROWS()');

  mnw_start_admin_page();
?>
<p><?php printf(__('<em>Subscribers</em> are users of an OMB service who listen to your notices. The messages of <em>subscribed users</em> get published to %s and may be displayed.', 'mnw'), get_bloginfo('title'));?></p>
<p><?php _e('If you delete a user, both subscriptions – if available – are removed.', 'mnw'); ?></p>
<p><?php _e('Note that the OpenMicroBlogging standard does not yet support a block feature; Therefore the remote user is not informed about a deletion. Moreover, if another user from the same service is subscribed to you, the remote service will probably publish your messages to deleted users as well.', 'mnw');?></p>
<p><?php _e('Likewise a user which is listed as subscriber may have canceled his subscription recently.', 'mnw');?></p>
    <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
      <h3><?php _e('Remote microblog users', 'mnw'); ?></h3>
      <?php wp_nonce_field('mnw-bulk-users') ?>
      <div class="tablenav">
        <div class="alignleft actions">
          <select name="action">
            <option value="" selected="selected"><?php _e('Bulk Actions'); ?></option>
            <option value="delete-selected"><?php _e('Delete'); ?></option>
          </select>
          <input type="submit" name="doaction_active" value="<?php _e('Apply'); ?>" class="button-secondary action" />
        </div>
        <div class="tablenav-pages">
            <span class="displaying-num">
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
      </div>
      <div class="clear" />
<table class="widefat" cellspacing="0" id="users-table">
  <thead>
  <tr>
    <th scope="col" class="check-column"><input type="checkbox" /></th>
    <th scope="col"><?php _e('User', 'mnw'); ?></th>
    <th scope="col"><?php _e('Direction', 'mnw'); ?></th>
    <th scope="col"><?php _e('License', 'mnw'); ?></th>
    <th scope="col" class="action-links"><?php _e('Action'); ?></th>
  </tr>
  </thead>

  <tfoot>
  <tr>
    <th scope="col" class="check-column"><input type="checkbox" /></th>
    <th scope="col"><?php _e('User', 'mnw'); ?></th>
    <th scope="col"><?php _e('Direction', 'mnw'); ?></th>
    <th scope="col"><?php _e('License', 'mnw'); ?></th>
    <th scope="col" class="action-links"><?php _e('Action'); ?></th>
  </tr>
  </tfoot>

  <tbody class="users">

<?php
    if ($users == 0) {
?>
    <tr>
      <td colspan="4"> <?php _e('No remote users', 'mnw'); ?></td>
    </tr>
<?php
    } else {
      foreach ($users as $user) {
?>
      <tr>
        <th scope='row' class='check-column'><input type='checkbox' name='checked[]' value='<?php echo $user['id']; ?>' /></th>
        <td><a href="<?php echo $user['url'];?>"><?php echo $user['nickname'];?></a></td>
        <td><?php if ($user['token'] && $user['resubtoken']) { _e('Both', 'mnw'); } elseif ($user['token']) { _e('Subscriber', 'mnw'); } else { _e('Subscribed user', 'mnw');} ?>
        <td><a href="<?php echo $user['license'];?>"><?php echo $user['license'];?></a></td>
        <td class='togl action-links'>
          <a href="<?php echo wp_nonce_url(
              $_SERVER['REQUEST_URI'] . '&amp;action=delete' . '&amp;user=' . $user['id'],
              'mnw-delete-user_' . $user['id']); ?>"
             title="<?php _e('Delete this user', 'mnw'); ?>">
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
<?php
    mnw_finish_admin_page();
}

mnw_remote_users_options();
?>
