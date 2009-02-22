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
        <h2><?php wp_title('',true,''); ?></h2>
<?php
    switch ($continue) {
    case '':
?>
        <p>
            <?php bloginfo('name'); ?> unterstützt den <a href="//openmicroblogging.org" title="Offizielle Website des OpenMicroBlogging-Standards">OpenMicroBlogging</a>-Standard.
            Wenn du einen Account bei einem anderen OMB-Dienst wie <a href="//identi.ca" title="identi.ca, der größte offene Mikroblogging-Dienst">identi.ca</a> hast, kannst du <?php bloginfo('name'); ?> besonders einfach abonnieren.
        </p>
<?php
    case 'subscribe':
        /* Gather data for subscribe form. */
        global $wp_query;
        $action = attribute_escape(mnw_append_param(get_option('mnw_themepage_url'), MNW_ACTION, 'subscribe') . '&' . MNW_SUBSCRIBE_ACTION . '=continue');

        /* Start displaying the form. */
?>
        <h3>Abonnieren</h3>
        <p>
            Gib die URL deines Profils bei einem OMB-Dienst an, um <?php bloginfo('name'); ?> zu abonnieren.
            Du wirst gebeten, dich einzuloggen, wenn du es noch nicht bist.
            Danach zeigt eine Bestätigungsseite Details von  <?php bloginfo('name'); ?> an.
        </p>
<?php   if (isset($data['error'])) { ?>
            <p>Error: <?php echo $data['error'];?></p>
<?php   } ?>
        <form id='omb-subscribe' method='post' action='<?php echo $action; ?>'>
            <label for="profile_url">OMB-Profil-URL</label>
            <input name="profile_url" type="text" class="input_text" id="profile_url" value='<?php if (isset($data['profile_url'])) echo $data['profile_url']; ?>'/>
            <input type="submit" id="submit" name="submit" class="submit" value="Abonnieren"/>
        </form>
<?php
        break;
    }
?>
	</div>

<?php get_sidebar(); ?>

<?php get_footer(); 
}
?>
