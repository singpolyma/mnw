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

function mnw_post_new_notice() {
    check_admin_referer('mnw-new_notice');
    if ($_POST['mnw_notice'] === '') {
        throw new Exception(__('You did not specify a notice text.', 'mnw'));
    }

    require_once 'Notice.php';
    $notice = new mnw_Notice($_POST['mnw_notice']);
    if(count($notice->send()) > 0) {
        throw new Exception(__('Error sending notice.', 'mnw'));
    }
}

function mnw_notices_parse_action() {
    switch ($_REQUEST['action']) {
    case 'delete':
        check_admin_referer('mnw-delete-notice_' . $_GET['notice']);
        if (!isset($_GET['notice'])) {
            throw new Exception(__('No notice specified.', 'mnw'));
        }
        $notices = array($_GET['notice']);
        break;

    case 'delete-selected':
        check_admin_referer('mnw-bulk-notices');
        if (!isset($_POST['checked'])) {
            throw new Exception(__('No notices specified.', 'mnw'));
        }
        $notices = $_POST['checked'];
        break;

    default:
        throw new Exception(__('Invalid action specified.', 'mnw'));
    }

    global $wpdb;
    foreach ($notices as $k => $v) {
        $notices[$k] = $wpdb->escape($v);
    }
    $notice_count = count($notices);
    if($notice_count !== $wpdb->query('DELETE FROM ' .
          ($_REQUEST['show_sent'] ? MNW_NOTICES_TABLE : MNW_FNOTICES_TABLE) .
          ' WHERE id = "' . implode('" OR id = "', $notices) . '"')) {
        throw new Exception(__ngettext('Could not delete notice.',
                           'Could not delete notices.', $notice_count, 'mnw'));
    }
    return $notice_count;
}

function mnw_notices() {
    /* Perform action and display result message. */
    if (isset($_POST['doaction_active']) &&
            $_POST['doaction_active'] == __('Send notice', 'mnw')) {
        echo '<div id="message" class="updated fade"><p>';
        try {
            mnw_post_new_notice();
            _e('Notice sent.', 'mnw');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        echo '</p></div>';
    }

    if (isset($_REQUEST['action']) && !empty($_REQUEST['action']) ) {
        echo '<div id="message" class="updated fade"><p>';
        try {
            $result = mnw_notices_parse_action();
            echo __ngettext('Notice successfully deleted.',
                            'Notices successfully deleted.', $result, 'mnw');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        echo '</p></div>';
    }

    /* Whether we should show the sent messages. */
    $show_sent = !((isset($_REQUEST['mnw_show'])
          && $_REQUEST['mnw_show'] === 'received') ||
          (isset($_REQUEST['show_sent']) && $_REQUEST['show_sent'] !== '1'));

    $captions = array(array(__('Sent notices', 'mnw'), 'sent'),
                      array(__('Received notices', 'mnw'), 'received'));

    function out_caption($item, $jackpot) {
        if($jackpot) {
            echo "<em>$item[0]</em>";
        } else {
            echo "<a href='" . $_SERVER['REQUEST_URI'] .
                                "&amp;mnw_show=$item[1]'>$item[0]</a>";
        }
    }

    /* Get notices. */
    global $wpdb;
    if ($show_sent) {
        $notices = $wpdb->get_results('SELECT id, content FROM ' .
                                                MNW_NOTICES_TABLE, ARRAY_A);
    } else {
        $notices = $wpdb->get_results('SELECT ' . MNW_FNOTICES_TABLE .
                '.id, content, nickname as author FROM ' . MNW_FNOTICES_TABLE .
                ' JOIN ' . MNW_SUBSCRIBER_TABLE . ' ON ' .
                'user_id = ' . MNW_SUBSCRIBER_TABLE . '.id', ARRAY_A);
    }

    mnw_start_admin_page();
?>
    <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
      <h3><?php _e('New notice', 'mnw'); ?></h3>
      <?php wp_nonce_field('mnw-new_notice'); ?>
      <textarea id="mnw_notice" name="mnw_notice" cols="45" rows="3" style="font-size: 2em; line-height: normal;">
        <?php if (isset($_POST['mnw_notice'])) echo $_POST['mnw_notice'];?>
      </textarea>
      <br />
      <input type="submit" name="doaction_active" class="button-primary action" value="<?php _e('Send notice', 'mnw'); ?>" />
    </form>

    <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
      <input type="hidden" name="show_sent" value="<?php echo $show_sent; ?>" />
      <h3><?php out_caption($captions[0], $show_sent); echo ' / ';
                out_caption($captions[1], !$show_sent); ?></h3>
      <?php wp_nonce_field('mnw-bulk-notices'); ?>
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

<table class="widefat" cellspacing="0" id="notices-table">
  <thead>
    <tr>
        <th scope="col" class="check-column"><input type="checkbox" /></th>
<?php if(!$show_sent) { ?>
        <th scope="col"><?php _e('Author', 'mnw'); ?></th>
<?php } ?>
        <th scope="col"><?php _e('Content', 'mnw'); ?></th>
        <th scope="col" class="action-links"><?php _e('Action'); ?></th>
    </tr>
  </thead>
  <tfoot>
    <tr>
        <th scope="col" class="check-column"><input type="checkbox" /></th>
<?php if(!$show_sent) { ?>
        <th scope="col"><?php _e('Author', 'mnw'); ?></th>
<?php } ?>
        <th scope="col"><?php _e('Content', 'mnw'); ?></th>
        <th scope="col" class="action-links"><?php _e('Action'); ?></th>
    </tr>
  </tfoot>
  <tbody class="notices">

<?php
    if ($notices == 0) {
?>
    <tr>
      <td colspan="<?php echo $show_sent ? 2 : 3;?>"><?php _e('No notices', 'mnw'); ?></td>
    </tr>
<?php
    } else {
      foreach ($notices as $notice) {
?>
      <tr>
        <th scope='row' class='check-column'><input type='checkbox' name='checked[]' value='<?php echo $notice['id']; ?>' /></th>
<?php if(!$show_sent) { ?>
        <td><?php echo $notice['author']; ?></th>
<?php } ?>
        <td><?php echo $notice['content']; ?></td>
        <td class='togl action-links'>
          <a href="<?php echo wp_nonce_url(
              $_SERVER['REQUEST_URI'] . '&amp;action=delete' . '&amp;show_sent=' . $show_sent . '&amp;notice=' . $notice['id'],
              'mnw-delete-notice_' . $notice['id']); ?>"
             title="<?php _e('Delete this notice', 'mnw'); ?>">
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

mnw_notices();
?>
