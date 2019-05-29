<?php
if(!function_exists('add_filter')) exit;
function fansub_theme_register_lib_bootstrap() {
    wp_register_style('bootstrap-style', get_template_directory_uri() . '/lib/bootstrap/css/bootstrap.min.css');
    wp_register_style('bootstrap-theme-style', get_template_directory_uri() . '/lib/bootstrap/css/bootstrap-theme.min.css', array('bootstrap-style'));
    wp_register_script('bootstrap', get_template_directory_uri() . '/lib/bootstrap/js/bootstrap.min.js', array('jquery'), false, true);
}

function fansub_theme_register_lib_superfish() {
    wp_register_style('superfish-style', get_template_directory_uri() . '/lib/superfish/css/superfish.min.css');
    wp_register_script('superfish', get_template_directory_uri() . '/lib/superfish/js/superfish.min.js', array('jquery'), false, true);
}

function fansub_theme_register_lib_font_awesome() {
    wp_register_style('font-awesome-style', get_template_directory_uri() . '/lib/font-awesome/css/font-awesome.min.css');
}

function fansub_theme_default_script_localize_object() {
    $defaults = fansub_default_script_localize_object();
    $args = array(
        'login_logo_url' => fansub_get_login_logo_url(),
        'mobile_menu_icon' => '<button class="menu-toggle mobile-menu-button" aria-expanded="false" aria-controls=""><i class="fa fa fa-bars"></i><span class="text">' . __('Menu', 'fansub') . '</span></button>'
    );
    $args = wp_parse_args($args, $defaults);
    return apply_filters('fansub_theme_default_script_object', $args);
}

function fansub_theme_register_core_style_and_script() {
    fansub_register_core_style_and_script();
}

function fansub_theme_get_template($slug, $name = '') {
    $slug = 'template-parts/' . $slug;
    get_template_part($slug, $name);
}

function fansub_theme_get_content_none() {
    fansub_theme_get_template('content', 'none');
}

function fansub_theme_get_template_page($name) {
    fansub_theme_get_template('page', $name);
}

function fansub_theme_get_module($name) {
    fansub_theme_get_template('module', $name);
}

function fansub_theme_get_loop($name) {
    fansub_theme_get_template('loop', $name);
}

function fansub_theme_get_image_url($name) {
    return get_template_directory_uri() . '/images/' . $name;
}

function fansub_theme_get_option($key, $base = 'theme_setting') {
    return fansub_option_get_value($base, $key);
}

function fansub_theme_get_logo_url() {
    $logo = fansub_theme_get_option('logo');
    $logo = fansub_sanitize_media_value($logo);
    return $logo['url'];
}

function fansub_theme_the_logo() {
    $logo_url = fansub_theme_get_logo_url();
    $logo_class = 'hyperlink';
    if(empty($logo_url)) {
        $logo_url = get_bloginfo('name');
    } else {
        $logo_url = '<img alt="' . get_bloginfo('description') . '" src="' . $logo_url . '">';
        $logo_class = 'img-hyperlink';
    }
    ?>
    <div class="site-branding">
        <?php if(is_front_page() && is_home()) : ?>
            <h1 class="site-title"<?php fansub_html_tag_attributes('h1', 'site_title'); ?>><a class="<?php echo $logo_class; ?>" title="<?php bloginfo('description'); ?>" href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php echo $logo_url; ?></a></h1>
        <?php else : ?>
            <p class="site-title"<?php fansub_html_tag_attributes('p', 'site_title'); ?>><a class="<?php echo $logo_class; ?>" title="<?php bloginfo('description'); ?>" href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php echo $logo_url; ?></a></p>
        <?php endif; ?>
        <p class="site-description"<?php fansub_html_tag_attributes('p', 'site_description'); ?>><?php bloginfo('description'); ?></p>
        <?php do_action('fansub_theme_logo'); ?>
    </div><!-- .site-branding -->
    <?php
}

function fansub_theme_the_menu($args = array()) {
    $items_wrap = '<ul id="%1$s" class="%2$s">%3$s</ul>';
    $theme_location = isset($args['theme_location']) ? $args['theme_location'] : 'primary';
    $menu_id = isset($args['menu_id']) ? $args['menu_id'] : $theme_location . '_menu';
    $menu_id = fansub_sanitize_id($menu_id);
    $menu_class = isset($args['menu_class']) ? $args['menu_class'] : '';
    fansub_add_string_with_space_before($menu_class , 'fansub-menu');
    fansub_add_string_with_space_before($menu_class , $theme_location);
    $superfish = isset($args['superfish']) ? $args['superfish'] : true;
    if($superfish) {
        fansub_add_string_with_space_before($menu_class, 'fansub-superfish-menu');
        $items_wrap = '<ul id="%1$s" class="sf-menu %2$s">%3$s</ul>';
    }
    $button_text = isset($args['button_text']) ? $args['button_text'] : __('Menu', 'fansub');
    ?>
    <nav id="site-navigation" class="main-navigation"<?php fansub_html_tag_attributes('nav', 'site_navigation'); ?>>
        <?php
        $menu_args = array(
            'theme_location' => $theme_location,
            'menu_class' => $menu_class,
            'menu_id' => $menu_id,
            'items_wrap' => $items_wrap
        );
        wp_nav_menu($menu_args);
        ?>
    </nav><!-- #site-navigation -->
    <?php
}

function fansub_theme_site_main_before() {
    ?>
    <div id="primary" class="content-area">
        <main id="main" class="site-main"<?php fansub_html_tag_attributes('main', 'site_main'); ?>>
    <?php
}

function fansub_theme_site_main_after() {
    ?>
        </main>
    </div>
    <?php
}

function fansub_theme_add_setting_field($args) {
    fansub_option_add_setting_field('theme_setting', $args);
}

function fansub_theme_add_setting_field_mobile_logo() {
    fansub_theme_add_setting_field(array('id' => 'mobile_logo', 'title' => __('Mobile Logo', 'fansub'), 'field_callback' => 'fansub_field_media_upload'));
}

function fansub_theme_add_setting_field_footer_logo() {
    fansub_theme_add_setting_field(array('title' => __('Footer Logo', 'fansub'), 'id' => 'footer_logo', 'field_callback' => 'fansub_field_media_upload'));
}

function fansub_theme_add_setting_field_footer_text() {
    fansub_theme_add_setting_field(array('title' => __('Footer Text', 'fansub'), 'id' => 'footer_text', 'field_callback' => 'fansub_field_editor'));
}

function fansub_theme_add_setting_field_select_page($option_name, $title) {
    fansub_theme_add_setting_field(array('title' => $title, 'id' => $option_name, 'field_callback' => 'fansub_field_select_page'));
}

function fansub_theme_add_setting_field_term_sortable($name, $title, $taxonomies = 'category', $only_parent = true) {
    $taxonomies = fansub_sanitize_array($taxonomies);
    $term_args = array();
    if($only_parent) {
        $term_args['parent'] = 0;
    }
    $args = array(
        'id' => $name,
        'title' => $title,
        'field_callback' => 'fansub_field_sortable_term',
        'connect' => true,
        'taxonomy' => $taxonomies,
        'term_args' => $term_args
    );
    fansub_theme_add_setting_field($args);
}

function fansub_theme_generate_license($password, $site_url = '', $domain = '') {
    if(empty($site_url)) {
        $site_url = get_bloginfo('url');
    }
    $license = new FANSUB_License();
    $license->set_password($password);
    $code = fansub_generate_serial();
    $license->set_code($code);
    if(empty($domain)) {
        $domain = fansub_get_root_domain_name($site_url);
    }
    $license->set_domain($domain);
    $license->set_customer_url($site_url);
    $license->generate();
    return $license->get_generated();
}

function fansub_theme_invalid_license_redirect() {
    $option = fansub_option_get_object_from_list('theme_license');
    if(fansub_object_valid($option) && !$option->is_this_page()) {
        global $pagenow;
        $admin_page = fansub_get_current_admin_page();
        if(('themes.php' != $pagenow || ('themes.php' == $pagenow && !empty($admin_page))) && fansub_can_redirect()) {
            if(is_admin() || (!is_admin() && !is_user_logged_in())) {
                set_transient('fansub_invalid_theme_license', 1);
                wp_redirect($option->get_page_url());
                exit;
            }
        } else {
            if(false === get_transient('fansub_invalid_theme_license')) {
                add_action('admin_notices', 'fansub_setup_theme_invalid_license_message');
            }
        }
    } else {
        if(false === get_transient('fansub_invalid_theme_license')) {
            add_action('admin_notices', 'fansub_setup_theme_invalid_license_message');
        }
    }
}

function fansub_theme_license_valid($data = array()) {
    global $fansub_theme_license;
    if(!fansub_object_valid($fansub_theme_license)) {
        $fansub_theme_license = new FANSUB_License();
    }
    return $fansub_theme_license->check_valid($data);
}

function fansub_theme_get_license_defined_data() {
    global $fansub_theme_license_data;
    $fansub_theme_license_data = fansub_sanitize_array($fansub_theme_license_data);
    return apply_filters('fansub_theme_license_defined_data', $fansub_theme_license_data);
}