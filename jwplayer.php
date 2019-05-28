<?php
/*
 * Template Name: JW Player
 */
$post_id = isset($_POST['post_id']) ? $_REQUEST['post_id'] : 0;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> style="background-color: #000;margin: 0 !important;overflow: hidden">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <title>Watch Videos</title>
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <link rel="pingback" href="<?php bloginfo('pingback_url'); ?>">
    <meta name="robots" content="noindex">
    <?php
    if(is_singular() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
    wp_head();
    ?>
    <style type="text/css">
        body > .fansub-jwplayer > *,
        body > * {
            display: none;
        }

        body > .fansub-jwplayer > .fansub-jwplayer-inner,
        body > .fansub-jwplayer {
            display: block;
        }

        body > .fansub-jwplayer > .fansub-jwplayer-inner > div {
            margin: 0 !important;
        }

        body > .fansub-jwplayer > .fansub-jwplayer-inner > div > .jwplayer {
            height: 480px !important;
            width: 100% !important;
        }

        body > .fansub-jwplayer > .fansub-jwplayer-inner > div > .jwplayer.jw-flag-fullscreen iframe,
        body > .fansub-jwplayer > .fansub-jwplayer-inner > div > .jwplayer.jw-flag-fullscreen {
            height: 100% !important;
        }
    </style>
    <style type="text/css">
*{margin:0;padding:0}#myElement{position:absolute;width:100%!important;height:100%!important}
</style>
</head>
<body <?php body_class('fansub-jwplayer'); ?> style="background-color: #000 !important;">
<?php
$only_member = get_post_meta($post_id, 'only_member', true);
if((bool)$only_member) {
    ?>
    <div class="fansub-jwplayer">
        <div class="fansub-jwplayer-inner">
            <div class="center" style="position: absolute; top: 40%; left: 50%; width: 220px; margin-left: -110px;">
                <p>You need <a href="<?php echo wp_login_url($actual_link); ?>">login</a> đTo watch this video.</p>
            </div>
        </div>

    </div>
    <?php
} else {
    ?>
    <div class="fansub-jwplayer">
        <div class="fansub-jwplayer-inner">
            <?php echo do_shortcode('[fansub_jwplayer post_id="' . $post_id . '"]'); ?>
        </div>

    </div>
    <?php
}
?>
</body>
</html>