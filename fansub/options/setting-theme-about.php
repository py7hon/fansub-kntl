<?php
if(!function_exists('add_filter')) exit;
$parent_slug = 'tools.php';

$option = new FANSUB_Option(__('System Information', 'fansub'), 'fansub_about');
$option->set_parent_slug($parent_slug);
$option->set_is_option_page(false);
$option->set_use_style_and_script(true);

$option->init();
fansub_option_add_object_to_list($option);

function fansub_option_page_about_content() {
    global $wpdb;
    $current_theme = wp_get_theme();
    $themes = wp_get_themes();
    ?>
    <div id="dashboard-widgets-wrap" class="fansub server-information">
        <div id="dashboard-widgets" class="metabox-holder">
            <div class="postbox-container">
                <?php ob_start(); ?>
                <table>
                    <tbody>
                    <tr>
                        <td class="label">WordPress Version</td>
                        <td><?php echo fansub_get_wp_version(); ?></td>
                    </tr>
                    <tr>
                        <td class="label">Home URL</td>
                        <td><?php echo home_url(); ?></td>
                    </tr>
                    <tr>
                        <td class="label">Site URL</td>
                        <td><?php bloginfo('url'); ?></td>
                    </tr>
                    <tr>
                        <td class="label">Admin Email</td>
                        <td><?php bloginfo('admin_email'); ?></td>
                    </tr>
                    </tbody>
                </table>
                <?php
                $content = ob_get_clean();
                $args = array(
                    'title' => __('Your Site', 'fansub'),
                    'content' => $content
                );
                fansub_field_admin_postbox($args);
                ?>
                <?php ob_start(); ?>
                <table>
                    <tbody>
                    <tr>
                        <td class="label">Current Theme</td>
                        <td><?php echo $current_theme->get('Name'); ?></td>
                    </tr>
                    <tr>
                        <td class="label">Current Theme Author</td>
                        <td><?php echo $current_theme->get('Author'); ?></td>
                    </tr>
                    <tr>
                        <td class="label">Current Theme URL</td>
                        <td><?php echo $current_theme->get('AuthorURI'); ?></td>
                    </tr>
                    <tr>
                        <td class="label">Installed</td>
                        <td><?php echo count($themes); ?></td>
                    </tr>
                    </tbody>
                </table>
                <?php
                $content = ob_get_clean();
                $args = array(
                    'title' => __('Theme', 'fansub'),
                    'content' => $content
                );
                fansub_field_admin_postbox($args);
                ?>
            </div>
            <div class="postbox-container">
                <?php ob_start(); ?>
                <table>
                    <tbody>
                    <tr>
                        <td class="label">PHP Version</td>
                        <td><?php echo phpversion(); ?></td>
                    </tr>
                    <tr>
                        <td class="label">MySQL Version</td>
                        <td><?php echo $wpdb->db_version(); ?></td>
                    </tr>
                    <?php if(function_exists('apache_get_version')) : ?>
                        <tr>
                            <td class="label">Apache</td>
                            <td><?php echo apache_get_version(); ?></td>
                        </tr>
                    <?php else : ?>
                        <tr>
                            <td class="label">Software</td>
                            <td><?php print_r($_SERVER['SERVER_SOFTWARE']); ?></td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                <hr>
                <a onclick="window.open('<?php echo FANSUB_URL; ?>/views/phpinfo.php', 'PHPInfo', 'width=800, height=600, scrollbars=1'); return false;" href="#" class="button-primary">PHP Info</a>
                <?php
                $content = ob_get_clean();
                $args = array(
                    'title' => __('Server Info', 'fansub'),
                    'content' => $content
                );
                fansub_field_admin_postbox($args);
                ?>
                <?php ob_start(); ?>
                <table>
                    <tbody>
                    <tr>
                        <td class="label">Browser</td>
                        <td><?php echo fansub_uppercase_first_char_words(fansub_get_browser()); ?></td>
                    </tr>
                    <tr>
                        <td class="label">User Agent</td>
                        <td><?php echo $_SERVER['HTTP_USER_AGENT']; ?></td>
                    </tr>
                    <tr>
                        <td class="label">IP Address</td>
                        <td><?php echo fansub_get_ip_address(); ?></td>
                    </tr>
                    </tbody>
                </table>
                <?php
                $content = ob_get_clean();
                $args = array(
                    'title' => __('Client Info', 'fansub'),
                    'content' => $content
                );
                fansub_field_admin_postbox($args);
                ?>
            </div>
        </div>
    </div>
    <?php
}
add_action('fansub_option_page_' . $option->get_option_name_no_prefix() . '_content', 'fansub_option_page_about_content');