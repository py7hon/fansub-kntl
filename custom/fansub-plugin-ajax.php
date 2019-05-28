<?php
if (!function_exists('add_filter')) exit;

if (!fansub_kntl_license_valid()) {
    return;
}

function fansub_search_autocomplete_ajax_callback()
{
    $term = isset($_REQUEST['term']) ? $_REQUEST['term'] : '';
    $suggestions = array();
    $args = array(
        'post_type' => fansub_kntl_get_post_type(),
        's' => $term,
        'posts_per_page' => 10
    );
    $query = fansub_query($args);
    $defaults = fansub_kntl_get_option_defaults();
    $option_data = fansub_kntl_get_option();
    $option_data = wp_parse_args($option_data, $defaults);
    $single_page = fansub_get_value_by_key($option_data, 'single_page');
    while ($query->have_posts()) {
        $query->the_post();
        $thumbnail_url = fansub_get_post_thumbnail_url();
        $image_url = bfi_thumb($thumbnail_url, array('width' => 70, 'height' => 90, 'crop' => true));
        $thumb_url = bfi_thumb($thumbnail_url, array('width' => 70, 'height' => 50, 'crop' => true));
        $permalink = fansub_kntl_build_single_url($single_page, get_the_ID());
        ob_start();
        ?>
        <div data-url="<?php echo $permalink; ?>" class="list">
            <a class="clearfix" href="<?php echo $permalink; ?>">
                <span class="image"><img src="<?php echo $image_url; ?>"></span>
                <span class="thumb"><img src="<?php echo $thumb_url; ?>"></span>

                <div class="info">
                    <span class="text add-text"><?php the_title(); ?></span>
                </div>
            </a>
        </div>
        <?php
        $post_html = ob_get_clean();
        $suggestion = array(
            'label' => get_the_title(),
            'link' => get_permalink(),
            'html' => $post_html
        );
        $suggestions[] = $suggestion;
    }
    wp_reset_postdata();
    if ($query->have_posts()) {
        $suggestion = array(
            'label' => 'View more',
            'link' => get_search_link($term)
        );
        $suggestions[] = $suggestion;
    }
    echo json_encode($suggestions);
    exit;
}

add_action('wp_ajax_fansub_search_autocomplete', 'fansub_search_autocomplete_ajax_callback');
add_action('wp_ajax_nopriv_fansub_search_autocomplete', 'fansub_search_autocomplete_ajax_callback');

function fansub_kntl_video_list_ajax_callback()
{
    $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : 0;
    $jwplayer = isset($_POST['jwplayer']) ? $_POST['jwplayer'] : false;
    $result = array(
        'have_posts' => false,
        'has_data' => false
    );
    $html_data = '';
    if (!(bool)$jwplayer) {
        $video = fansub_query_post_by_meta('animation', $post_id, array('post_type' => 'video', 'posts_per_page' => -1), 'numeric');
        $result['have_posts'] = $video->have_posts();
        while ($video->have_posts()) {
            $video->the_post();
            $id = get_the_ID();
            $fancy_id = 'videoBox' . $id;
            $jwplayer_video_url = get_post_meta($id, '_jwppp-video-url-1', true);
            ob_start();
            $article = '<article ';
            ob_start();
            post_class('');
            $article .= ob_get_clean();
            $article .= ' data-id="' . get_the_ID() . '"';
            if (current_theme_supports('fansub-schema')) {
                ob_start();
                fansub_html_tag_attributes('article', 'post');
                $article .= ob_get_clean();
            }
            if (!empty($jwplayer_video_url)) {
                $article .= ' data-jwplayer="1"';
            } else {
                $article .= ' data-jwplayer="0"';
            }
            $article .= '>';
            $article = apply_filters('fansub_article_before', $article);
            echo $article;
            $custom_html = '<a href="#' . $fancy_id . '" class="fancy-link"><img class="icon-play" src="' . FANSUB_KNTL_URL . '/images/icon-play.png' . '"></a>';
            fansub_post_thumbnail(array('width' => 260, 'height' => 146, 'crop' => true, 'custom_html' => $custom_html, 'permalink' => '#' . $fancy_id));
            //fansub_post_title_link();
            ?>
            <div id="<?php echo $fancy_id; ?>" class="video-fancy-box" style="display: none">
                <?php
                $only_member = get_post_meta($id, 'only_member', true);
                if ((bool)$only_member) {
                    $actual_link = fansub_get_current_url();
                    if (is_user_logged_in()) {
                        the_content();
                    } else {
                        ?>
                        <div class="center"
                             style="position: absolute; top: 40%; left: 50%; width: 220px; margin-left: -110px;">
                            <p>Bạn cần <a href="<?php echo wp_login_url($actual_link); ?>">login</a> để xem video này.
                            </p>
                        </div>
                        <?php
                    }
                } else {
                    the_content();
                }
                ?>
            </div>
            <?php
            fansub_article_after();
            $html_data .= ob_get_clean();
        }
        wp_reset_postdata();
    } else {
        $page = fansub_get_page_by_template('../jwplayer.php');
        if (is_a($page, 'WP_Post')) {
            $url = get_permalink($page);
            $url = add_query_arg(array('post_id' => $post_id), $url);
            $html_data = '<iframe src="' . $url . '" allowfullscreen webkitallowfullscreen mozallowfullscreen>';
            $result['has_data'] = true;
        }
    }
    $result['html_data'] = $html_data;
    echo json_encode($result);
    exit;
}

add_action('wp_ajax_fansub_kntl_video_list', 'fansub_kntl_video_list_ajax_callback');
add_action('wp_ajax_nopriv_fansub_kntl_video_list', 'fansub_kntl_video_list_ajax_callback');

function fansub_kntl_search_post_ajax_callback()
{
    $refresh = isset($_POST['refresh']) ? $_POST['refresh'] : 0;
    $default_query_vars = isset($_POST['default_query_vars']) ? $_POST['default_query_vars'] : array();
    $default_query_vars = fansub_json_string_to_array($default_query_vars);
    $box_type = isset($_POST['box_type']) ? $_POST['box_type'] : 'all';
    $query_vars = isset($_POST['query_vars']) ? $_POST['query_vars'] : array();
    $query_vars = fansub_json_string_to_array($query_vars);
    $data = isset($_POST['options_data']) ? $_POST['options_data'] : array();
    $data = fansub_json_string_to_array($data);
    $load_more = isset($_POST['load_more']) ? $_POST['load_more'] : 0;
    $search = isset($_POST['search']) ? $_POST['search'] : '';
    $is_search = isset($_POST['is_search']) ? $_POST['is_search'] : 0;
    $single = isset($_POST['single']) ? $_POST['single'] : 0;
    if ((bool)$refresh) {
        $query_vars = $default_query_vars;
    }
    $result = array(
        'success' => false,
        'more_post' => true
    );

    $data_post_not_in = fansub_get_value_by_key($query_vars, 'post__not_in');

    if (!(bool)$load_more) {
        if (!empty($search)) {
            $query_vars['s'] = $search;
            unset($query_vars['post__not_in']);
        } else {
            unset($query_vars['s']);
        }
    }

    if ((bool)$is_search) {
        $query_vars['s'] = $search;
    }

    $post_types = fansub_get_value_by_key($query_vars, 'post_type', 'episode');
    $post_types = fansub_sanitize_array($post_types);

    $date_format = isset($data['date_format']) ? $data['date_format'] : 'm/d';
    $post_type = fansub_kntl_get_post_type();
    $option_data = fansub_kntl_get_option();
    $single_page = fansub_get_value_by_key($option_data, 'single_page');

    if (fansub_array_has_value($default_query_vars)) {
        $query_vars = $default_query_vars;
    }
    if ((bool)$refresh) {
        //unset($query_vars['post__not_in']);
    }
    $query_args = $query_vars;
    if ((bool)$is_search) {
        $query_args['post_type'] = array('episode', 'batch');
        if ('batch' == $box_type) {
            $query_args['post_type'] = array('batch');
        }
    }

    $query = fansub_query($query_args);
    $post_count = $query->post_count;
    $posts_per_page = $query->query_vars['posts_per_page'];
    $html_items = '';
    $post_not_in = $query->query_vars['post__not_in'];
    $query_vars = $query->query_vars;
    $result['have_posts'] = $query->have_posts();
    $result['post_count'] = $post_count;
    $result['posts_per_page'] = $posts_per_page;
    if ($post_count < $posts_per_page) {
        $result['more_post'] = false;
    }
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $post = get_post(get_the_ID());
            $post_not_in[] = $post->ID;
            $post_title = $post->post_title;
            $post_link = '#';
            $suffix = '';
            $parts = array();
            if ('episode' == $post->post_type) {
                $parts = fansub_kntl_convert_post_title_to_parts($post->post_title);
                array_shift($parts);
                $parts = array_map('trim', $parts);
                $animation_id = get_post_meta($post->ID, 'animation', true);
                $animation = get_post($animation_id);
                if (is_a($animation, 'WP_Post')) {
                    $post_title = $animation->post_title;
                    $post_link = fansub_kntl_build_single_url($single_page, $animation_id);
                    if (!fansub_array_has_value($parts)) {

                    }
                    $part = array_pop($parts);
                    if (!empty($part)) {
                        $suffix = '<span class="suffix">' . ' - ' . $part . '</span>';
                    }
                }

                // Giu nguyen episode title
                if ((bool)$single) {
                    $post_title = $post->post_title;
                    $post_link = '#';
                }
            } elseif ('batch' == $post->post_type) {
                $parts = fansub_kntl_convert_post_title_to_parts($post->post_title);
                array_shift($parts);
                $parts = array_map('trim', $parts);
                $episode_id = get_post_meta($post->ID, 'episode', true);
                if (is_numeric($episode_id) && $episode_id > 0) {
                    $animation_id = get_post_meta($episode_id, 'animation', true);
                    $animation = get_post($animation_id);
                    if (is_a($animation, 'WP_Post')) {
                        $post_title = $animation->post_title;
                        $post_link = fansub_kntl_build_single_url($single_page, $animation_id);
                        if (!fansub_array_has_value($parts)) {

                        }
                        $part = array_pop($parts);
                        if (!empty($part)) {
                            $tmp_part = array_pop($parts);
                            if (!empty($tmp_part)) {
                                $part = $tmp_part . '-' . $part;
                            }
                            $suffix = '<span class="suffix">' . ' - ' . $part . '</span>';
                        }
                    }
                } else {
                    $animation_id = get_post_meta($post->ID, 'animation', true);
                    $animation = get_post($animation_id);
                    if (is_a($animation, 'WP_Post')) {
                        $post_title = $animation->post_title;
                        $post_link = fansub_kntl_build_single_url($single_page, $animation_id);
                        if (!fansub_array_has_value($parts)) {

                        }
                        $part = array_pop($parts);
                        if (!empty($part)) {
                            $tmp_part = array_pop($parts);
                            if (!empty($tmp_part)) {
                                $part = $tmp_part . '-' . $part;
                            }
                            $suffix = '<span class="suffix">' . ' - ' . $part . '</span>';
                        }
                    }
                }
                if ('batch' == $box_type || 'release' == $box_type) {
                    $post_title = $post->post_title;
                }
            } else {
                $post_title = $post->post_title;
                $post_link = fansub_kntl_build_single_url($single_page, $post->ID);
            }

            $link_html = new FANSUB_HTML('a');
            if ((bool)$single) {
                $link_html->use_only_text();
                $post_title = '<span class="post-title-link">' . $post_title . '</span>';
            }
            $link_html->set_href($post_link);
            $link_html->set_text($post_title);
            $link_html->set_class('post-title-link');
            $saved_suffix = get_post_meta($post->ID, 'suffix', true);
            if ('batch' == $box_type || ('release' == $box_type && 'batch' == $post->post_type)) {
                $suffix = '';
            }
            if (!empty($saved_suffix)) {
                $suffix = '<span class="suffix">' . ' - ' . $saved_suffix . '</span>';
            }
            $post_title_text = $link_html->build();
            if (!(bool)$single) {
                $post_title_text .= $suffix;
            }
            $qs = fansub_ph_get_qualities_and_servers(get_the_ID());

            $qualities = $qs['qualities'];
            $servers = $qs['servers'];
            ob_start();
            ?>
            <li <?php post_class(sanitize_html_class($post->post_type)); ?>>
                <ul class="list-cols">
                    <li class="col-date"><span class="post-date">(<?php echo get_the_date($date_format); ?>)</span></li>
                    <li class="col-title"><span class="post-title"><?php echo $post_title_text; ?></span></li>
                    <li class="col-download">
                        <ul class="list-qualities">
                            <?php foreach ($qualities as $quality) : ?>
                                <?php
                                $file_name_key = 'quality_' . $quality . '_file_name';
                                $file_name = get_post_meta($post->ID, $file_name_key, true);
                                if (empty($file_name)) {
                                    if ('episode' == $post->post_type) {
                                        $ep_am_id = get_post_meta($post->ID, 'animation', true);
                                        if (fansub_id_number_valid($ep_am_id)) {
                                            $ep_am = get_post($ep_am_id);
                                            $file_name = $ep_am->post_title . ' [' . $quality . ']';
                                        }
                                    } elseif ('batch' == $post->post_type) {
                                        $bt_ep_id = get_post_meta($post->ID, 'episode', true);
                                        if (fansub_id_number_valid($bt_ep_id)) {
                                            $ep_am_id = get_post_meta($bt_ep_id, 'animation', true);
                                            if (fansub_id_number_valid($ep_am_id)) {
                                                $ep_am = get_post($ep_am_id);
                                                $file_name = $ep_am->post_title . ' [' . $quality . ']';
                                            }
                                        } else {
                                            $ep_am_id = get_post_meta($post->ID, 'animation', true);
                                            if (fansub_id_number_valid($ep_am_id)) {
                                                $ep_am = get_post($ep_am_id);
                                                $file_name = $ep_am->post_title . ' [' . $quality . ']';
                                            }
                                        }
                                    }
                                } else {
                                    if (!fansub_string_contain($file_name, $quality)) {
                                        //$file_name .= ' [' . $quality . ']';
                                    }
                                }
                                $quality_item_class = 'quality quality-' . $quality;
                                $server_items_html = '<li class="server-item file-name">' . $file_name . '</li>';
                                $server_items_html .= fansub_ph_servers_loop($servers, $quality, $post, $quality_item_class);
                                fansub_ph_quality_item_html($quality, $quality_item_class, $server_items_html);
                                ?>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                </ul>
            </li>
            <?php
            $html_items .= ob_get_clean();
        }
        wp_reset_postdata();
        $result['html'] = $html_items;
        if (!empty($html_items)) {
            $result['success'] = true;
        }
    } else {
        $result['no_post_msg'] = '<li>' . fansub_get_value_by_key($option_data, 'search_none_text') . '</li>';
    }
    if ((bool)$refresh) {
        $query_vars['post__not_in'] = $data_post_not_in;
        if ((bool)$single) {
            $post_type = current($post_types);
            if ('post' == $post_type) {
                $query_vars['post_type'] = 'episode';
            }
        }
    }
    $query_vars['post__not_in'] = $post_not_in;
    $result['query_vars'] = json_encode($query_vars);
    echo json_encode($result);
    die();
}

add_action('wp_ajax_fansub_kntl_search_post', 'fansub_kntl_search_post_ajax_callback');
add_action('wp_ajax_nopriv_fansub_kntl_search_post', 'fansub_kntl_search_post_ajax_callback');

function fansub_kntl_pagination_ajax_callback()
{
    $result = array(
        'success' => false,
        'have_posts' => false,
        'html' => ''
    );
    $query_vars = isset($_POST['query_vars']) ? $_POST['query_vars'] : array();
    $query_vars = fansub_json_string_to_array($query_vars);
    $paged = isset($_POST['paged']) ? $_POST['paged'] : 1;
    $query_vars['paged'] = $paged;
    $query = fansub_query($query_vars);
    $defaults = fansub_kntl_get_option_defaults();
    $option_data = fansub_kntl_get_option();
    $option_data = wp_parse_args($option_data, $defaults);
    $single_page = fansub_get_value_by_key($option_data, 'single_page');
    if ($query->have_posts()) {
        $result['have_posts'] = $query->have_posts();
        $loop_html = '';
        while ($query->have_posts()) {
            $query->the_post();
            $permalink = fansub_kntl_build_single_url($single_page, get_the_ID());
            ob_start();
            ?>
            <li <?php post_class(); ?>>
                <?php
                fansub_post_thumbnail(array('bfi_thumb' => false, 'lazyload' => true, 'before' => '<div class="anime-thumb">', 'after' => '</div>', 'permalink' => $permalink));
                fansub_post_title_link(array('permalink' => $permalink));
                fansub_entry_summary();
                ?>
            </li>
            <?php
            $loop_html .= ob_get_clean();
        }
        wp_reset_postdata();
        $result['html'] = $loop_html;
    }
    echo json_encode($result);
    exit;
}

add_action('wp_ajax_fansub_kntl_pagination', 'fansub_kntl_pagination_ajax_callback');
add_action('wp_ajax_nopriv_fansub_kntl_pagination', 'fansub_kntl_pagination_ajax_callback');

function fansub_ph_check_post_password_ajax_callback()
{
    $result = array(
        'message' => 'Mật khẩu không đúng, xin vui lòng kiểm tra lại.',
        'success' => false
    );
    $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : '';
    if (fansub_id_number_valid($post_id)) {
        $post = get_post($post_id);
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        if (post_password_required($post) && $post->post_password == $password) {
            $result['success'] = true;
        }
    }
    wp_send_json($result);
}

add_action('wp_ajax_fansub_ph_check_post_password', 'fansub_ph_check_post_password_ajax_callback');
add_action('wp_ajax_nopriv_fansub_ph_check_post_password', 'fansub_ph_check_post_password_ajax_callback');

function fansub_ph_get_shortlink_ajax_callback()
{
    $result = array(
        'success' => false,
        'shortlink' => ''
    );
    $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : '';
    if (fansub_id_number_valid($post_id)) {
        $shortlink = wp_get_shortlink($post_id);
        $shortlink = md5($shortlink);
        update_post_meta($post_id, 'shortlink', $shortlink);
        $shortlink = home_url('/go/' . $shortlink);
        $result['shortlink'] = $shortlink;
        $result['success'] = true;
    }
    wp_send_json($result);
}

add_action('wp_ajax_fansub_ph_get_shortlink', 'fansub_ph_get_shortlink_ajax_callback');