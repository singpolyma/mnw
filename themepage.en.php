<?php
/*
Template Name: mnw
*/

/* Let the plugin handle the request.
   $continue holds false if the plugin issued a redirect and no output should be shown.
   Else $continue is a string from the set ('', 'subscribe').
   $data contains additional data generated by the plugin. */

@list($continue, $data) = mnw_parse_request();

if ($continue !== false) {
    get_header();
?>
    <div id="content" class="narrowcolumn">
        <h2><?php wp_title('', true, ''); ?></h2>
<?php
    switch ($continue) {
    case '':
?>
        <p>
            <?php bloginfo('name'); ?> supports the <a href="//openmicroblogging.org" title="Official website of the OpenMicroBlogging standard">OpenMicroBlogging</a> standard.
            So, if you have an user account at another OMB service like <a href="//identi.ca" title="identi.ca, the largest open microblogging service">identi.ca</a>, you can easily subscribe to <?php bloginfo('name'); ?>.
        </p>
        <p>
            <?php bloginfo('name'); ?> uses the free plugin <a href="//adrianlang.de/mnw" title="mnw Website">mnw</a> for OMB support.
        </p>
<?php
    case 'subscribe':
        /* Gather data for subscribe form. */
        global $wp_query;
        $action = attribute_escape(mnw_append_param(get_option('mnw_themepage_url'), MNW_ACTION, 'subscribe') . '&' . MNW_SUBSCRIBE_ACTION . '=continue');

        /* Start displaying the form. */
?>
        <h3>Subscribe</h3>
        <p>
            To subscribe, just enter the URL of your profile at another OMB service.
            You will be asked to log in there if you are not yet.
            There will be a confirmation prompt showing details of  <?php bloginfo('name'); ?>.
        </p>
<?php   if (isset($data['error'])) { ?>
            <p>Error: <?php echo $data['error'];?></p>
<?php   } ?>
        <form id='omb-subscribe' method='post' action='<?php echo $action; ?>'>
            <label for="profile_url">OMB Profile URL</label>
            <input name="profile_url" type="text" class="input_text" id="profile_url" value='<?php if (isset($data['profile_url'])) echo $data['profile_url']; ?>'/>
            <input type="submit" id="submit" name="submit" class="submit" value="Subscribe"/>
        </form>
<?php
        break;
    case 'get_notice':
?>
        <h3 style="text-transform:uppercase;"><?php printf('Status on %s:', date('d F Y H:i:s', strtotime($data['notice']->created)));?></h3>
        <p style="font-size: 200%; margin-left: 0.5em;">
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
        break;

    case 'userauth':
?>
        <p>
<?php
        if (isset($data['error'])) {
          echo $data['error'];
        } else {
          $uri = $data['remote_user']->getIdentifierURI();
          $action = attribute_escape(mnw_append_param(get_option('mnw_themepage_url'), MNW_ACTION, 'oauth') . '&' . MNW_OAUTH_ACTION . '=userauth_continue');

?>
          <form id="mnw_userauthorization" name="mnw_userauthorization" method="post" action="<?php echo $action; ?>">
            <p>Do you really want to subscribe <a href='<?php echo $data['remote_user']->getProfileURL(); ?>'><?php echo $uri; ?></a>?</p>
            <input id="profile" type="hidden" value="<?php echo $uri; ?>" name="profile"/>
            <input id="nonce" type="hidden" value="<?php echo wp_create_nonce('mnw_userauth_nonce'); ?>" name="nonce"/>
            <input id="accept" class="submit" type="submit" title="" value="Yes" name="accept"/>
            <input id="reject" class="submit" type="submit" title="" value="No" name="reject"/>
          </form>
<?php
        }
?>
        </p>
<?php
        break;

    case 'userauth_continue':
        echo '<p>';
        if ($data['token'] !== '') {
          echo 'Confirm the subscribee‘s service that token  ' . $data['token'] . ' is authorized.';
        } else {
          echo 'You rejected the subscription.';
        }
        echo '</p>';
        break;

    }
?>
	</div>

<?php get_sidebar(); ?>

<?php get_footer(); 
}
?>
