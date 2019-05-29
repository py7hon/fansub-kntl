<?php
if(!function_exists('add_filter')) exit;
do_action('fansub_before_doctype');
$maintenance_mode = fansub_in_maintenance_mode();
?>
<!doctype html>
<html <?php language_attributes(); ?> class="no-js"<?php fansub_html_tag_attributes('html'); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <link rel="pingback" href="<?php bloginfo('pingback_url'); ?>">
    <?php
    do_action('fansub_before_wp_head');
    wp_head();
    do_action('fansub_after_wp_head');
    if($maintenance_mode) {
        do_action('fansub_maintenance_head');
    }
    ?>
</head>
<body <?php body_class(); ?><?php fansub_html_tag_attributes('body'); ?>>
<?php
do_action('fansub_open_body');
do_action('fansub_before_site');
?>
<div id="page" class="hfeed site">
    <div class="site-inner">
        <?php if(!$maintenance_mode) : ?>
            <?php do_action('fansub_before_site_header'); ?>
            <header id="masthead" class="site-header clearfix"<?php fansub_html_tag_attributes('header', 'masthead'); ?>>
                <?php fansub_theme_get_template('header'); ?>
            </header><!-- .site-header -->
            <?php do_action('fansub_after_site_header'); ?>
        <?php endif; ?>
        <?php do_action('fansub_before_site_content'); ?>
        <div id="content" class="site-content clearfix">