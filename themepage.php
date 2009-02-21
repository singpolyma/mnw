<?php
/*
Template Name: mnw
*/
@list($continue, $data) = mnw_parse_request();
if ($continue !== false) {
    get_header();
?>
    <div id="content" class="narrowcolumn">
        <h2><?php wp_title('',true,''); ?></h2>
<?php
    switch ($continue) {
    default:
?>
        <p>
            <?php bloginfo('name'); ?> supports the <a href="//openmicroblogging.org" title="Official website of the OpenMicroBlogging standard">OpenMicroBlogging standard</a>. So, if you have an user account at another OMB service like <a href="//identi.ca" title="identi.ca, the largest open microblogging service">identi.ca</a>, you can easily subscribe to <?php bloginfo('name'); ?>.
        </p>
<?php
    case 'subscribe':
?>
        <h3>Subscribe</h3>
        <p>
            To subscribe, just enter the URL of your profile at another OMB service. You will be asked to log in there if you are not yet. There will be a confirmation prompt showing details of  <?php bloginfo('name'); ?>.
<?php 
        mnw_subscribe_form(isset($data['error']) ? $data['error'] : '');
?>
        </p>
<?php
        break;
    }
?>
	</div>

<?php get_sidebar(); ?>

<?php get_footer(); 
}
?>
