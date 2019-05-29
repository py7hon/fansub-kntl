<?php if(!function_exists('add_filter')) exit; ?>
<?php $maintenance_mode = fansub_in_maintenance_mode(); ?>
</div><!-- .site-content -->
<?php do_action('fansub_after_site_content'); ?>
<?php if(!$maintenance_mode) : ?>
    <?php do_action('fansub_before_site_footer'); ?>
    <footer id="colophon" class="site-footer clearfix"<?php fansub_html_tag_attributes('footer', 'site_footer'); ?>>
        <?php fansub_theme_get_template('footer'); ?>
    </footer><!-- .site-footer -->
    <?php do_action('fansub_after_site_footer'); ?>
<?php endif; ?>
</div><!-- .site-inner -->
</div><!-- .site -->
<?php
if(!$maintenance_mode) {
    do_action('fansub_after_site');
    do_action('fansub_before_wp_footer');
    wp_footer();
    do_action('fansub_after_wp_footer');
    do_action('fansub_close_body');
} else {
    do_action('fansub_maintenance_footer');
}
?>
</body>
</html>