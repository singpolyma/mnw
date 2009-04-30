<?php
/*
Template Name: mnw
*/

/* ----------
   DO NOT change the following lines. */

/* Initialize mnw themepage handling, parse request. */
require_once 'mnw-themepage-handler.php';
$page = new mnw_Themepage();

/* Check if the themepage should be displayed at all.
   Maybe mnw issued a redirect or displayed non-html content. */
if (!$page->shouldDisplay) {
    return;
}

/* Start customizing the page HERE.
   ---------- */

get_header();
?>
<div id="content" class="narrowcolumn">
    <h2><?php wp_title('', true, ''); ?></h2>
<?php
    /* Display the page‘s content. */
    $page->render();
?>
</div>
<?php
get_sidebar();
get_footer(); 
?>
