<?php
if (!function_exists('add_filter')) {
    exit;
}

define('FANSUB_PH_KEY', 'cr9Cct8&Zn5$*CxD');

class FANSUB_HRS_PageTemplater
{

    /**
     * A Unique Identifier
     */
    protected $plugin_slug;

    /**
     * A reference to an instance of this class.
     */
    private static $instance;

    /**
     * The array of templates that this plugin tracks.
     */
    protected $templates;


    /**
     * Returns an instance of this class.
     */
    public static function get_instance()
    {

        if (null == self::$instance) {
            self::$instance = new FANSUB_HRS_PageTemplater();
        }

        return self::$instance;

    }

    /**
     * Initializes the plugin by setting filters and administration functions.
     */
    private function __construct()
    {

        $this->templates = array();


        // Add a filter to the attributes metabox to inject template into the cache.
        add_filter(
            'page_attributes_dropdown_pages_args',
            array($this, 'register_project_templates')
        );


        // Add a filter to the save post to inject out template into the page cache
        add_filter(
            'wp_insert_post_data',
            array($this, 'register_project_templates')
        );


        // Add a filter to the template include to determine if the page has our
        // template assigned and return it's path
        add_filter(
            'template_include',
            array($this, 'view_project_template')
        );


        // Add your templates to this array.
        $this->templates = array(
            '../jwplayer.php' => 'JW Player',
        );

    }


    /**
     * Adds our template to the pages cache in order to trick WordPress
     * into thinking the template file exists where it doens't really exist.
     * @param $atts
     * @return
     */

    public function register_project_templates($atts)
    {

        // Create the key used for the themes cache
        $cache_key = 'page_templates-' . md5(get_theme_root() . '/' . get_stylesheet());

        // Retrieve the cache list.
        // If it doesn't exist, or it's empty prepare an array
        $templates = wp_get_theme()->get_page_templates();
        if (empty($templates)) {
            $templates = array();
        }

        // New cache, therefore remove the old one
        wp_cache_delete($cache_key, 'themes');

        // Now add our template to the list of templates by merging our templates
        // with the existing templates array from the cache.
        $templates = array_merge($templates, $this->templates);

        // Add the modified cache to allow WordPress to pick it up for listing
        // available templates
        wp_cache_add($cache_key, $templates, 'themes', 1800);

        return $atts;

    }

    /**
     * Checks if the template is assigned to the page
     * @param $template
     * @return string
     */
    public function view_project_template($template)
    {

        global $post;
        if (!is_a($post, 'WP_Post')) {
            return $template;
        }

        if (!isset($this->templates[get_post_meta(
                $post->ID, '_wp_page_template', true
            )])
        ) {

            return $template;

        }

        $file = plugin_dir_path(__FILE__) . get_post_meta(
                $post->ID, '_wp_page_template', true
            );

        // Just to be safe, we check if the file exist first
        if (file_exists($file)) {
            return $file;
        } else {
            echo $file;
        }

        return $template;

    }


}

add_action('plugins_loaded', array('FANSUB_HRS_PageTemplater', 'get_instance'));

function fansub_kntl_get_post_type()
{
    $value = fansub_option_get_value('fansub_kntl', 'post_type_name');
    if (empty($value)) {
        $data = fansub_kntl_get_option();
        $value = fansub_get_value_by_key($data, 'post_type_name');
    }
    if (empty($value)) {
        $value = 'post';
    }

    return $value;
}

function fansub_kntl_get_post_type_label_singular_name()
{
    $data = fansub_kntl_get_option();
    $singular_name = fansub_get_value_by_key($data, 'post_type_label_singular_name');
    if (empty($singular_name)) {
        $singular_name = fansub_get_value_by_key($data, 'post_type_label_name');
    }

    return $singular_name;
}

function fansub_kntl_convert_post_title_to_parts($title)
{
    $post_title = str_replace(' ', '-', $title);
    $title_parts = explode('-', $post_title);

    return $title_parts;
}

function fansub_kntl_get_post_types()
{
    return array(fansub_kntl_get_post_type(), 'episode', 'batch');
}

function fansub_kntl_get_current_animation_single()
{
    $animation = get_query_var('animation');
    if (!fansub_id_number_valid($animation)) {
        $animation = fansub_get_post_by_column('post_name', $animation, 'id', array('post_type' => fansub_kntl_get_post_type()));
    }

    return $animation;
}

function fansub_kntl_query_new_release($args = array())
{
    $transient_name = 'fansub_kntl_query_new_release';
    if (false === ($query = get_transient($transient_name)) || true) {
        $data_defaults = fansub_kntl_get_option();
        $post_type = fansub_kntl_get_post_types();
        $defaults = array(
            'post_type' => $post_type,
            'post_status' => 'publish'
        );
        unset($data_defaults['post_type']);
        $defaults = shortcode_atts($defaults, $data_defaults);
        $args = wp_parse_args($args, $defaults);
        $query = fansub_query($args);
        $batch_ids = array();
        $episode_ids = array();
        $animation_ids = array();
        $exclude_ids = array();
        $parents_has_new_child = array();
        $animations_has_new_child = array();
        $animation_type = fansub_kntl_get_post_type();
        $episode_type = 'episode';
        $episode_child_key = 'episodes';
        $batch_type = 'batch';
        $batch_child_key = 'batches';

        foreach ($query->posts as $post) {
            if ('batch' == $post->post_type) {
                $batch_ids[] = $post->ID;
            } elseif ('episode' == $post->post_type) {
                //$episode_ids[] = $post->ID;
            } else {
                $animation_ids[] = $post->ID;
            }
        }

        foreach ($batch_ids as $id) {
            $parent_id = get_post_meta($id, $episode_type, true);
            $has_new_child = false;
            if (fansub_id_number_valid($parent_id)) {
                $childs = get_post_meta($parent_id, $batch_child_key, true);
                if (fansub_array_has_value($childs)) {
                    $has_new_child = true;
                    $exclude_ids = array_merge($exclude_ids, $childs);
                    $exclude_ids = array_unique($exclude_ids);
                    $tmp_args = array(
                        'post_type' => $batch_type,
                        'posts_per_page' => 1,
                        'fields' => 'ids'
                    );
                    $tmp_query = fansub_query_post_by_meta($episode_type, $parent_id, $tmp_args, 'numeric');
                    if ($tmp_query->have_posts()) {
                        $exclude_ids[] = $id;
                        $exclude_ids = array_unique($exclude_ids);
                        $parents_has_new_child[] = $parent_id;
                        $latest_id = current($tmp_query->posts);
                        $exclude_ids = fansub_sanitize_array($exclude_ids);
                        $exclude_ids = array_unique($exclude_ids);
                        $exclude_ids = fansub_remove_array_item_by_value($latest_id, $exclude_ids);
                    }
                }
                $parent_id = get_post_meta($parent_id, $animation_type, true);
                if (fansub_id_number_valid($parent_id)) {
                    $childs = get_post_meta($parent_id, $episode_child_key, true);
                    if (fansub_array_has_value($childs)) {
                        $exclude_ids = array_merge($exclude_ids, $childs);
                        $exclude_ids = array_unique($exclude_ids);
                    }
                }
            } else {
                $parent_id = get_post_meta($id, $animation_type, true);
                if (fansub_id_number_valid($parent_id)) {
                    $childs = get_post_meta($parent_id, $batch_child_key, true);
                    if (fansub_array_has_value($childs)) {
                        $has_new_child = true;
                        $exclude_ids = array_merge($exclude_ids, $childs);
                        $exclude_ids = array_unique($exclude_ids);
                        $tmp_args = array(
                            'post_type' => $batch_type,
                            'posts_per_page' => 1,
                            'fields' => 'ids'
                        );
                        $tmp_query = fansub_query_post_by_meta($animation_type, $parent_id, $tmp_args, 'numeric');
                        if ($tmp_query->have_posts()) {
                            $exclude_ids[] = $id;
                            $exclude_ids = array_unique($exclude_ids);
                            $parents_has_new_child[] = $parent_id;
                            $latest_id = current($tmp_query->posts);
                            $exclude_ids = fansub_sanitize_array($exclude_ids);
                            $exclude_ids = fansub_remove_array_item_by_value($latest_id, $exclude_ids);
                        }
                    }
                }
            }
        }

        foreach ($episode_ids as $id) {
            $parent_id = get_post_meta($id, $animation_type, true);
            $has_new_child = false;
            if (fansub_id_number_valid($parent_id)) {
                $childs = get_post_meta($parent_id, $episode_child_key, true);
                if (fansub_array_has_value($childs)) {
                    $has_new_child = true;
                    $exclude_ids = array_merge($exclude_ids, $childs);
                    $exclude_ids = array_unique($exclude_ids);
                    $tmp_args = array(
                        'post_type' => $episode_type,
                        'posts_per_page' => 1,
                        'fields' => 'ids'
                    );
                    $tmp_query = fansub_query_post_by_meta($animation_type, $parent_id, $tmp_args, 'numeric');
                    if ($tmp_query->have_posts()) {
                        $exclude_ids[] = $id;
                        $exclude_ids = array_unique($exclude_ids);
                        $parents_has_new_child[] = $parent_id;
                        $latest_id = current($tmp_query->posts);
                        $exclude_ids = fansub_sanitize_array($exclude_ids);
                        $exclude_ids = fansub_remove_array_item_by_value($latest_id, $exclude_ids);
                    }
                }
            }
        }

        $exclude_ids = array_merge($exclude_ids, $parents_has_new_child);

        $exclude_ids = fansub_sanitize_array($exclude_ids);
        $args['post__not_in'] = $exclude_ids;
        $query = fansub_query($args);
        set_transient($transient_name, $query, 3 * DAY_IN_SECONDS);
    }

    return $query;
}

function fansub_horrilbesubs_get_single_page()
{
    return fansub_option_get_value('fansub_kntl', 'single_page');
}

function fansub_kntl_build_single_url($page_id, $animation_id)
{
    $url = get_permalink($animation_id);
    if (fansub_id_number_valid($page_id)) {
        $animation = get_post($animation_id);
        $post_type = fansub_kntl_get_post_type();
        if (is_a($animation, 'WP_Post') && $post_type == $animation->post_type) {
            $url = get_permalink($page_id);
            $permalink_struct = get_option('permalink_structure');
            if (!empty($permalink_struct)) {
                $url = trailingslashit($url);
                $url .= trailingslashit($animation->post_name);
            } else {
                $url = add_query_arg(array('animation' => $animation_id), $url);
            }
        }
    }

    return $url;
}

function fansub_ph_servers_loop($servers, $quality, $post, &$quality_item_class)
{
    $server_items_html = '';
    $hide_url = false;
    if ('private' == $post->post_status || post_password_required($post)) {
        $hide_url = true;
        $class = 'server-item private-item';
        if (post_password_required($post)) {
            $class .= ' password-required';
        }
        ob_start();
        ?>
        <li class="<?php echo $class; ?>" data-id="<?php echo $post->ID; ?>">
            <a href="javascript:" class="private-link" title="Private content">
                <span class="dashicons dashicons-lock"
                      style="color: rgb(51, 51, 51); display: inline-block; line-height: 29px; font-size: 20px;"></span>
            </a>
        </li>
        <?php
        $server_items_html .= ob_get_clean();
    }
    foreach ($servers as $server) : ?>
        <?php
        $meta_name = 'quality_' . $quality . '_' . $server;
        $meta_name = fansub_sanitize_id($meta_name);
        $meta_value = get_post_meta($post->ID, $meta_name, true);
        if (!empty($meta_value)) {
            fansub_add_string_with_space_before($quality_item_class, 'has-link');
        }
        $href = $meta_value;
        if ($hide_url) {
            $href = 'javascript:';
        } else {
            $href = fansub_ph_encrypt_download_url($href);
        }
        if (!empty($meta_value)) {
            $meta_value = fansub_ph_encrypt_download_url($meta_value);
        }
        ob_start();
        ?>
        <li class="server-item <?php echo $server; ?>-item">
            <?php if (empty($meta_value)) : ?>
                <span class="server server-<?php echo fansub_sanitize_id($server); ?>"><?php echo $server; ?></span>
            <?php else : ?>
                <a href="<?php echo esc_attr($href); ?>" data-href="<?php echo esc_url($meta_value); ?>">
                    <span class="server server-<?php echo fansub_sanitize_id($server); ?>"><?php echo $server; ?></span>
                </a>
            <?php endif; ?>
        </li>
        <?php
        $server_items_html .= ob_get_clean();
    endforeach;
    return $server_items_html;
}

function fansub_ph_quality_item_html($quality, $quality_item_class, $server_items_html)
{
    ?>
    <li class="quality-item <?php echo $quality; ?>-item">
        <span class="<?php echo $quality_item_class; ?>"><?php echo $quality; ?></span>
        <ul class="list-servers">
            <?php echo $server_items_html; ?>
        </ul>
    </li>
    <?php
}

function fansub_ph_get_qualities_and_servers($post_id = null)
{
    $qualities = '';
    $servers = '';
    if (fansub_id_number_valid($post_id)) {
        $pq = get_post_meta($post_id, 'custom_qualities', true);
        if (!empty($pq)) {
            $qualities = $pq;
        }
        $ps = get_post_meta($post_id, 'custom_servers', true);
        if (!empty($ps)) {
            $servers = $ps;
        }
    }
    $data = fansub_kntl_get_option();
    if (empty($qualities)) {
        $qualities = fansub_get_value_by_key($data, 'qualities');
    }
    if (empty($servers)) {
        $servers = fansub_get_value_by_key($data, 'servers');
    }

    $qualities = fansub_string_to_array(',', $qualities);
    $qualities = array_map('trim', $qualities);
    $qualities = array_filter($qualities);
    $qualities = array_unique($qualities);

    $servers = fansub_string_to_array(',', $servers);
    $servers = array_map('trim', $servers);
    $servers = array_filter($servers);
    $servers = array_unique($servers);

    return array('qualities' => $qualities, 'servers' => $servers);
}

function fansub_ph_get_auto_facebook_post_types()
{
    $options = get_option('fansub_kntl');
    $post_type = isset($options['fb_post_type']) ? $options['fb_post_type'] : '';
    $post_type = fansub_json_string_to_array($post_type);
    $result = array();
    if (fansub_array_has_value($post_type)) {
        foreach ($post_type as $value) {
            if (isset($value['value'])) {
                $result[] = $value['value'];
            }
        }
    }
    return $result;
}

function fansub_ph_encrypt_download_url($url)
{
    $result = home_url('/go/');
    $result .= base64_encode(FANSUB_PH_KEY . '|' . $url);
    return $result;
}