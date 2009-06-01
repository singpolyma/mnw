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

function mnw_last_post_date() {
    global $wpdb;
    return $wpdb->get_var("SELECT created FROM " . MNW_NOTICES_TABLE . " ORDER BY created DESC LIMIT 1");
}

function mnw_feed() {
header('Content-Type: application/atom+xml; charset=' . get_option('blog_charset'), true);
echo '<?xml version="1.0" encoding="' . get_option('blog_charset') . '" ?' . '>';
?>

<feed
    xmlns="http://www.w3.org/2005/Atom"
    xml:lang="<?php echo get_option('rss_language'); ?>"
    xmlns:thr="http://purl.org/syndication/thread/1.0"
    <?php do_action('atom_ns'); ?>
>
    <title type="text"><?php
        printf(ent2ncr(__('Microblog posts by %s', 'mnw')), get_bloginfo('title'));
    ?></title>
    <subtitle type="text"><?php bloginfo_rss('description'); ?></subtitle>
    <link rel="alternate" href="<?php echo get_option('mnw_themepage_url'); ?>" type="<?php bloginfo_rss('html_type'); ?>" />
    <link href="<?php echo attribute_escape(mnw_set_action('notices')); ?>" rel="self" type="application/atom+xml"/>
    <id><?php echo get_option('mnw_themepage_url'); ?></id>

    <updated><?php echo mysql2date('Y-m-d\TH:i:s\Z', mnw_last_post_date()); ?></updated>
    <author><name><?php echo get_option('omb_nickname');?></name></author>
    <?php the_generator( 'atom' ); ?>

<?php
    global $wpdb;
    $notices = $wpdb->get_results('SELECT * FROM ' . MNW_NOTICES_TABLE . ' ORDER BY created DESC', ARRAY_A);
foreach($notices as $notice) {
?>
    <entry>
        <title><?php if(mb_strlen($notice['content']) > 140) echo substr($notice['content'], 0, 139) . '…'; else echo $notice['content']; ?></title>
        <link rel="alternate" href="<?php echo attribute_escape(mnw_set_action('get_notice') . '&mnw_notice_id=' . $notice['id']); ?>" type="<?php bloginfo_rss('html_type'); ?>" />
        <content><?php echo $notice['content']; ?></content>
        <id><?php echo attribute_escape(mnw_set_action('get_notice') . '&mnw_notice_id=' . $notice['id']); ?></id>
        <updated><?php echo mysql2date('Y-m-d\TH:i:s\Z', $notice['created'], false); ?></updated>
        <published><?php echo mysql2date('Y-m-d\TH:i:s\Z', $notice['created'], false); ?></published>
    </entry>
<?php } ?>
</feed>
<?php
    return array(false, array());
}
?>
