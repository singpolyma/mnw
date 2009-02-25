<?php

require_once 'mnw.php';

function mnw_subscribe_widget_register() {
    register_sidebar_widget (__('mnw Subscribe'), 'mnw_subscribe_widget');
}
add_action('init', 'mnw_subscribe_widget_register');

function mnw_subscribe_widget($args) {
    extract($args);
    global $wpdb;
    $count = $wpdb->get_var('SELECT COUNT(id) FROM ' . MNW_SUBSCRIBER_TABLE . ' GROUP BY id');
    echo $before_widget;
?>
        <div style="height: 45px; padding-left: 45px; background: transparent url(<?php echo get_template_directory_uri(); ?>/omb.png) no-repeat scroll left center;
            text-align: right; font-size:180%;">
            <?php printf(__ngettext('%d OMB subscriber', '%d OMB subscribers', $count, 'mnw'), $count); ?><br />
            <a href="<?php get_option('mnw_themepage_url'); ?>"><?php _e('Subscribe!', 'mnw'); ?></a>
        </div>
<?php
    echo $after_widget;
}
?>
