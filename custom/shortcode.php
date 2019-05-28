<?php
function fansub_kntl_new_release_shortcode($atts, $content = null)
{
    $defaults = fansub_kntl_get_option_defaults();
    $option_data = fansub_kntl_get_option();
    $option_data = shortcode_atts($defaults, $option_data);
    $option_data = shortcode_atts($option_data, $atts);
    $release_title = fansub_get_value_by_key($option_data, 'release_box_title');
    $post_number = fansub_get_value_by_key($option_data, 'posts_per_page');
    $date_format = fansub_get_value_by_key($option_data, 'date_format');
    $single_page = fansub_get_value_by_key($option_data, 'single_page');
    $release_box_search_placeholder = fansub_get_value_by_key($option_data, 'release_box_search_placeholder');
    $data = $option_data;
    $result = '';
    $title = isset($data['release_box_title']) ? $data['release_box_title'] : $release_title;
    $number = isset($data['posts_per_page']) ? $data['posts_per_page'] : $post_number;
    $query = fansub_kntl_query_new_release(array('posts_per_page' => $number));
    $date_format = isset($data['date_format']) ? $data['date_format'] : $date_format;
    $post_type = fansub_kntl_get_post_type();
    $option_data = fansub_kntl_get_option();

    $query_vars = $query->query_vars;

    $post_not_in = isset($query->query_vars['post__not_in']) ? $query->query_vars['post__not_in'] : array();
    $refresh_text = fansub_get_value_by_key($option_data, 'refresh_text');
    $clear_text = fansub_get_value_by_key($option_data, 'clear_text');
    ob_start();
    ?>
    <div class="fansub-new-release fansub-box fansub-release">
        <?php if (!empty($title)) : ?>
            <h2 class="box-title"><?php echo $title; ?></h2>
        <?php endif; ?>
        <div class="box-content" data-type="release">
            <form action="" id="search" class="searchbox search-form">
                <div class="searchcontainer">
                    <input placeholder="<?php echo $release_box_search_placeholder; ?>" class="searchbar search-field">
                </div>
                <div class="refreshlink btn-refresh">
                    <a class="refreshbutton" href="#">
                        <i title="<?php echo $refresh_text; ?>" data-refresh-text="<?php echo $refresh_text; ?>"
                           data-clear-text="<?php echo $clear_text; ?>" class="dashicons dashicons-update"></i>
                    </a>
                </div>
            </form>
            <div class="latest">
                <ul class="list-releases">
                    <?php
                    while ($query->have_posts()) {
                        $query->the_post();
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
                                $post_link = fansub_kntl_build_single_url($single_page, $animation->ID);
                                if (!fansub_array_has_value($parts)) {

                                }
                                $part = array_pop($parts);
                                if (!empty($part)) {
                                    $suffix = '<span class="suffix">' . ' - ' . $part . '</span>';
                                }
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
                                    $post_link = fansub_kntl_build_single_url($single_page, $animation->ID);
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
                                    $post_link = fansub_kntl_build_single_url($single_page, $animation->ID);
                                    if (!fansub_array_has_value($parts)) {

                                    }
                                    $part = array_pop($parts);
                                    if (!empty($part)) {
                                        $tmp_part = array_pop($parts);
                                        if (!empty($tmp_part)) {
                                            //$part = $tmp_part . '-' . $part;
                                        }
                                        $suffix = '<span class="suffix">' . ' - ' . $part . '</span>';
                                    }
                                }
                            }
                            $post_title = $post->post_title;
                        } else {
                            $post_title = $post->post_title;
                            $post_link = fansub_kntl_build_single_url($single_page, $post->ID);
                        }
                        $link_html = new FANSUB_HTML('a');
                        $link_html->set_href($post_link);
                        $link_html->set_text($post_title);
                        $link_html->set_class('post-title-link');
                        $saved_suffix = get_post_meta($post->ID, 'suffix', true);
                        if ('batch' == $post->post_type) {
                            $suffix = '';
                        }
                        if (!empty($saved_suffix)) {
                            $suffix = '<span class="suffix">' . ' - ' . $saved_suffix . '</span>';
                        }
                        $qs = fansub_ph_get_qualities_and_servers(get_the_ID());

                        $qualities = $qs['qualities'];
                        $servers = $qs['servers'];
                        ?>
                        <li <?php post_class(sanitize_html_class($post->post_type)); ?>>
                            <ul class="list-cols">
                                <li class="col-date"><span
                                        class="post-date">(<?php echo get_the_date($date_format); ?>)</span></li>
                                <li class="col-title"><span
                                        class="post-title"><?php $link_html->output(); ?><?php echo $suffix; ?></span>
                                </li>
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
                    }
                    wp_reset_postdata();
                    ?>
                </ul>
                <?php $query_vars['post__not_in'] = $post_not_in; ?>
                <input type="hidden" class="default-query-vars"
                       value="<?php echo esc_attr(json_encode($query->query_vars)); ?>" autocomplete="off">
                <input type="hidden" class="query-vars"
                       data-default="<?php echo esc_attr(json_encode($query_vars)); ?>"
                       value="<?php echo esc_attr(json_encode($query_vars)); ?>" autocomplete="off">
                <input type="hidden" class="options-data" value="<?php echo esc_attr(json_encode($data)); ?>"
                       autocomplete="off">
            </div>
            <?php
            $load_more_text = fansub_get_value_by_key($option_data, 'show_more_text');
            $loading_text = fansub_get_value_by_key($option_data, 'loading_text');
            $reached_end_text = fansub_get_value_by_key($option_data, 'reached_end_text');
            ?>
            <div class="morebox">
                <a id="0" class="morebutton btn-more" style="display: block;" href="#"
                   data-reached-end-text="<?php echo $reached_end_text; ?>"
                   data-loading-text="<?php echo $loading_text; ?>"
                   data-text="<?php echo $load_more_text; ?>"><?php echo $load_more_text; ?></a>
            </div>
        </div>
    </div>
    <?php
    $result = ob_get_clean();

    return $result;
}

add_shortcode('fansub_release', 'fansub_kntl_new_release_shortcode');

function fansub_kntl_single_shortcode($atts, $content = null)
{
    $animation = fansub_kntl_get_current_animation_single();
    $result = '';
    if (fansub_id_number_valid($animation)) {
        if (post_password_required($animation)) {
            return get_the_password_form();
        }
        $animation = get_post($animation);
        if (is_a($animation, 'WP_Post') && fansub_kntl_get_post_type() == $animation->post_type) {
            if ('private' == $animation->post_status) {
                return '';
            }
            $defaults = fansub_kntl_get_option_defaults();
            $option_data = fansub_kntl_get_option();
            $option_data = shortcode_atts($defaults, $option_data);
            $option_data = shortcode_atts($option_data, $atts);
            $single_batch_title = fansub_get_value_by_key($option_data, 'single_batch_title');
            $single_episode_title = fansub_get_value_by_key($option_data, 'single_episode_title');
            $post_number = fansub_get_value_by_key($option_data, 'posts_per_page');
            $date_format = fansub_get_value_by_key($option_data, 'date_format');
            $single_page = fansub_get_value_by_key($option_data, 'single_page');
            $release_box_search_placeholder = fansub_get_value_by_key($option_data, 'release_box_search_placeholder');
            $single_search_placeholder = fansub_get_value_by_key($option_data, 'single_search_placeholder');
            $data = $option_data;

            $post_not_in = isset($query->query_vars['post__not_in']) ? $query->query_vars['post__not_in'] : array();
            $refresh_text = fansub_get_value_by_key($option_data, 'refresh_text');
            $clear_text = fansub_get_value_by_key($option_data, 'clear_text');

            $episode = fansub_query_post_by_meta('animation', $animation->ID, array(
                'post_type' => 'episode',
                'posts_per_page' => $post_number
            ), 'numeric');
            $batch = new WP_Query(array('post_type' => 'batch', 'posts_per_page' => $post_number));
            $batches = array();
            if ($episode->have_posts()) {
                foreach ($episode->posts as $apost) {
                    $saved = get_post_meta($apost->ID, 'batches', true);
                    if (fansub_array_has_value($saved)) {
                        $batches = array_merge($batches, $saved);
                    }
                }
            }
            $am_batches = get_post_meta($animation->ID, 'batches', true);
            if (is_array($batches) && is_array($am_batches)) {
                $batches = array_merge($batches, $am_batches);
            }
            $batches = fansub_sanitize_array($batches);
            if (fansub_array_has_value($batches)) {
                $batch_args = array(
                    'post_type' => 'batch',
                    'post__in' => $batches,
                    'posts_per_page' => -1
                );
                $batch = fansub_query($batch_args);
            }
            if (!$batch->have_posts() || !$episode->have_posts()) {
                $batches = get_post_meta($animation->ID, 'batches', true);
                $batches = fansub_sanitize_array($batches);
                $batch_args = array(
                    'post_type' => 'batch',
                    'post__in' => $batches,
                    'posts_per_page' => -1
                );
                $batch = fansub_query($batch_args);
            }
            $post_content = apply_filters('the_content', $animation->post_content);
            ob_start();
            ?>
            <div class="fansub-new-release single-box fansub-box fansub-single">
                <div class="series-info">
                    <div class="series-image"><?php fansub_post_thumbnail(array(
                            'loop' => false,
                            'bfi_thumb' => false,
                            'post_id' => $animation->ID
                        )); ?></div>
                    <div class="series-desc"><?php echo $post_content; ?></div>
                </div>
                <div style="clear:both;"></div>
                <div class="series-releases">
                    <div class="batch-box">
                        <?php
                        $query = $batch;
                        $have_batch = false;
                        ?>
                        <h2><?php echo $single_batch_title; ?></h2>

                        <div class="content-box box-content" data-type="all">
                            <?php if ($query->have_posts() && 1 == 2) : ?>
                                <form action="" id="search" class="searchbox search-form">
                                    <div class="searchcontainer">
                                        <input placeholder="<?php echo $single_search_placeholder; ?>"
                                               class="searchbar search-field">
                                    </div>
                                    <div class="refreshlink btn-refresh">
                                        <a class="refreshbutton" href="#">
                                            <i title="<?php echo $refresh_text; ?>"
                                               data-refresh-text="<?php echo $refresh_text; ?>"
                                               data-clear-text="<?php echo $clear_text; ?>"
                                               class="dashicons dashicons-update"></i>
                                        </a>
                                    </div>
                                </form>
                            <?php endif; ?>
                            <div class="latest">
                                <?php if ($query->have_posts()) : ?>
                                    <div class="tips">
                                        <i><?php echo fansub_get_value_by_key($option_data, 'single_batch_tip'); ?></i>
                                    </div>
                                <?php endif; ?>
                                <ul class="list-releases">
                                    <?php
                                    if ($query->have_posts()) {
                                        $query_vars = $query->query_vars;
                                        while ($query->have_posts()) {
                                            $query->the_post();
                                            $post = get_post(get_the_ID());
                                            $post_not_in[] = $post->ID;
                                            $post_title = $post->post_title;
                                            $post_link = '#';
                                            $suffix = '';
                                            if (!$have_batch) {
                                                $saved_ep = get_post_meta($post->ID, 'episode', true);
                                                $saved_am = get_post_meta($saved_ep, 'animation', true);
                                                if ($saved_am == $animation->ID) {
                                                    $have_batch = true;
                                                } else {
                                                    $saved_am = get_post_meta($post->ID, 'animation', true);
                                                    if ($saved_am == $animation->ID) {
                                                        $have_batch = true;
                                                    } else {
                                                        continue;
                                                    }
                                                }
                                            }
                                            $parts = array();
                                            if ('episode' == $post->post_type) {
                                                $parts = fansub_kntl_convert_post_title_to_parts($post->post_title);
                                                array_shift($parts);
                                                $parts = array_map('trim', $parts);
                                                $animation_id = get_post_meta($post->ID, 'animation', true);
                                                $animation = get_post($animation_id);
                                                if (is_a($animation, 'WP_Post')) {
                                                    $post_link = fansub_kntl_build_single_url($single_page, $animation->ID);
                                                    if (!fansub_array_has_value($parts)) {

                                                    }
                                                    $part = array_pop($parts);
                                                    if (!empty($part)) {
                                                        $suffix = '<span class="suffix">' . ' - ' . $part . '</span>';
                                                    }
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
                                                        $post_link = fansub_kntl_build_single_url($single_page, $animation->ID);
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

                                                        $post_link = fansub_kntl_build_single_url($single_page, $animation->ID);
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
                                            } else {
                                                $post_title = $post->post_title;
                                                $post_link = fansub_kntl_build_single_url($single_page, $post->ID);
                                            }
                                            if (fansub_array_has_value($parts)) {

                                            }
                                            $link_html = new FANSUB_HTML('span');
                                            $link_html->set_text($post_title);
                                            $link_html->set_class('post-title-link');
                                            $saved_suffix = get_post_meta($post->ID, 'suffix', true);
                                            if (!empty($saved_suffix)) {
                                                $suffix = '<span class="suffix">' . ' - ' . $saved_suffix . '</span>';
                                            }
                                            $qs = fansub_ph_get_qualities_and_servers(get_the_ID());

                                            $qualities = $qs['qualities'];
                                            $servers = $qs['servers'];
                                            ?>
                                            <li <?php post_class(sanitize_html_class($post->post_type)); ?>>
                                                <ul class="list-cols">
                                                    <li class="col-date"><span
                                                            class="post-date">(<?php echo get_the_date($date_format); ?>
                                                            )</span></li>
                                                    <li class="col-title"><span
                                                            class="post-title"><?php $link_html->output(); ?></span>
                                                    </li>
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
                                        }
                                        wp_reset_postdata();
                                        if (!$have_batch) {
                                            echo '<li>' . fansub_get_value_by_key($option_data, 'single_batch_none') . '</li>';
                                        }
                                    } else {
                                        echo '<li>' . fansub_get_value_by_key($option_data, 'single_batch_none') . '</li>';
                                    }
                                    ?>
                                </ul>
                                <?php
                                $query_vars['post__not_in'] = $post_not_in;
                                //unset($query_vars['post__not_in']);
                                ?>
                                <input type="hidden" class="default-query-vars"
                                       value="<?php echo esc_attr(json_encode($query->query_vars)); ?>"
                                       autocomplete="off">
                                <input type="hidden" class="query-vars"
                                       data-default="<?php echo esc_attr(json_encode($query_vars)); ?>"
                                       value="<?php echo esc_attr(json_encode($query_vars)); ?>" autocomplete="off">
                                <input type="hidden" class="options-data"
                                       value="<?php echo esc_attr(json_encode($data)); ?>" autocomplete="off">
                            </div>
                            <?php
                            $load_more_text = fansub_get_value_by_key($option_data, 'show_more_text');
                            $loading_text = fansub_get_value_by_key($option_data, 'loading_text');
                            $reached_end_text = fansub_get_value_by_key($option_data, 'reached_end_text');
                            ?>
                            <?php if ($query->have_posts() && $have_batch && 1 == 2) : ?>
                                <div class="morebox">
                                    <a id="0" class="morebutton btn-more" style="display: block;" href="#"
                                       data-reached-end-text="<?php echo $reached_end_text; ?>"
                                       data-loading-text="<?php echo $loading_text; ?>"
                                       data-text="<?php echo $load_more_text; ?>"><?php echo $load_more_text; ?></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="episode-box">
                        <?php
                        $query = $episode;
                        ?>
                        <h2><?php echo $single_episode_title; ?></h2>

                        <div class="content-box box-content" data-type="single">
                            <?php if ($query->have_posts()) : ?>
                                <form action="" id="search" class="searchbox search-form">
                                    <div class="searchcontainer">
                                        <input placeholder="<?php echo $single_search_placeholder; ?>"
                                               class="searchbar search-field">
                                    </div>
                                    <div class="refreshlink btn-refresh">
                                        <a class="refreshbutton" href="#">
                                            <i title="<?php echo $refresh_text; ?>"
                                               data-refresh-text="<?php echo $refresh_text; ?>"
                                               data-clear-text="<?php echo $clear_text; ?>"
                                               class="dashicons dashicons-update"></i>
                                        </a>
                                    </div>
                                </form>
                            <?php endif; ?>
                            <div class="latest">
                                <ul class="list-releases">
                                    <?php
                                    if ($query->have_posts()) {
                                        $query_vars = $episode->query_vars;
                                        while ($episode->have_posts()) {
                                            $episode->the_post();
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
                                                    $post_link = fansub_kntl_build_single_url($single_page, $animation->ID);
                                                    if (!fansub_array_has_value($parts)) {

                                                    }
                                                    $part = array_pop($parts);
                                                    if (!empty($part)) {
                                                        $suffix = '<span class="suffix">' . ' - ' . $part . '</span>';
                                                    }
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
                                                        $post_link = fansub_kntl_build_single_url($single_page, $animation->ID);
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
                                                        $post_link = fansub_kntl_build_single_url($single_page, $animation->ID);
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
                                            } else {
                                                $post_title = $post->post_title;
                                                $post_link = fansub_kntl_build_single_url($single_page, $post->ID);
                                            }
                                            $link_html = new FANSUB_HTML('span');
                                            $link_html->set_text($post_title);
                                            $link_html->set_class('post-title-link');
                                            $saved_suffix = get_post_meta($post->ID, 'suffix', true);
                                            if (!empty($saved_suffix)) {
                                                $suffix = '<span class="suffix">' . ' - ' . $saved_suffix . '</span>';
                                            }
                                            $qs = fansub_ph_get_qualities_and_servers(get_the_ID());

                                            $qualities = $qs['qualities'];
                                            $servers = $qs['servers'];
                                            ?>
                                            <li <?php post_class(sanitize_html_class($post->post_type)); ?>>
                                                <ul class="list-cols">
                                                    <li class="col-date"><span
                                                            class="post-date">(<?php echo get_the_date($date_format); ?>
                                                            )</span></li>
                                                    <li class="col-title"><span
                                                            class="post-title"><?php $link_html->output(); ?></span>
                                                    </li>
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
                                        }
                                        wp_reset_postdata();
                                    } else {
                                        echo '<li>' . fansub_get_value_by_key($option_data, 'single_episode_none') . '</li>';
                                    }
                                    ?>
                                </ul>
                                <?php
                                $query_vars['post__not_in'] = $post_not_in;
                                //unset($query_vars['post__not_in']);
                                ?>
                                <input type="hidden" class="default-query-vars"
                                       value="<?php echo esc_attr(json_encode($query->query_vars)); ?>"
                                       autocomplete="off">
                                <input type="hidden" class="query-vars"
                                       data-default="<?php echo esc_attr(json_encode($query_vars)); ?>"
                                       value="<?php echo esc_attr(json_encode($query_vars)); ?>" autocomplete="off">
                                <input type="hidden" class="options-data"
                                       value="<?php echo esc_attr(json_encode($data)); ?>" autocomplete="off">
                            </div>
                            <?php
                            $load_more_text = fansub_get_value_by_key($option_data, 'show_more_text');
                            $loading_text = fansub_get_value_by_key($option_data, 'loading_text');
                            $reached_end_text = fansub_get_value_by_key($option_data, 'reached_end_text');
                            ?>
                            <?php if ($query->have_posts()) : ?>
                                <div class="morebox">
                                    <a id="0" class="morebutton btn-more" style="display: block;" href="#"
                                       data-reached-end-text="<?php echo $reached_end_text; ?>"
                                       data-loading-text="<?php echo $loading_text; ?>"
                                       data-text="<?php echo $load_more_text; ?>"><?php echo $load_more_text; ?></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                    $video = fansub_query_post_by_meta('animation', $animation->ID, array(
                        'post_type' => 'video',
                        'posts_per_page' => -1
                    ), 'numeric');
                    $video_box_title = fansub_get_value_by_key($option_data, 'video_box_title', 'Promotional Videos');
                    if ($video->have_posts()) {
                        ?>
                        <div class="video-box" data-post-id="<?php echo $animation->ID; ?>">
                            <div class="module-header">
                                <h2><?php echo $video_box_title; ?></h2>
                            </div>
                            <div class="module-body">
                                <div class="center">
                                    <img alt=""
                                         src="<?php echo fansub_get_image_url('icon-loading-circle-light-full.gif'); ?>"
                                         style="border: medium none; box-shadow: none; display: block; margin: 80px auto; clear: both;">
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <?php if (comments_open($animation->ID)) : ?>
                    <div class="comments-area" id="comments">
                        <h2 class="comments-title"></h2>
                        <ul class="commentlist list-comments">
                            <?php wp_list_comments(array(), get_comments(array('post_id' => $animation->ID))); ?>
                        </ul>
                        <!-- .commentlist -->
                        <?php comment_form(array(), $animation->ID); ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php
            $result = ob_get_clean();
        }
    }

    return $result;
}

add_shortcode('fansub_single', 'fansub_kntl_single_shortcode');

function fansub_kntl_episode_shortcode($atts, $content = null)
{
    $defaults = fansub_kntl_get_option_defaults();
    $option_data = fansub_kntl_get_option();
    $option_data = shortcode_atts($defaults, $option_data);
    $option_data = shortcode_atts($option_data, $atts);
    $release_title = fansub_get_value_by_key($option_data, 'release_box_title');
    $post_number = fansub_get_value_by_key($option_data, 'posts_per_page');
    $date_format = fansub_get_value_by_key($option_data, 'date_format');
    $single_page = fansub_get_value_by_key($option_data, 'single_page');
    $release_box_search_placeholder = fansub_get_value_by_key($option_data, 'release_box_search_placeholder');
    $data = $option_data;
    $result = '';
    $title = isset($data['release_box_title']) ? $data['release_box_title'] : $release_title;
    $number = isset($data['posts_per_page']) ? $data['posts_per_page'] : $post_number;
    $query_args = array(
        'posts_per_page' => $number,
        'post_type' => 'episode'
    );
    $query = fansub_query($query_args);
    $date_format = isset($data['date_format']) ? $data['date_format'] : $date_format;
    $post_type = fansub_kntl_get_post_type();
    $option_data = fansub_kntl_get_option();

    $query_vars = $query->query_vars;

    $post_not_in = isset($query->query_vars['post__not_in']) ? $query->query_vars['post__not_in'] : array();
    $refresh_text = fansub_get_value_by_key($option_data, 'refresh_text');
    $clear_text = fansub_get_value_by_key($option_data, 'clear_text');
    ob_start();
    ?>
    <div class="fansub-new-release fansub-box fansub-episode">
        <?php if (!empty($title)) : ?>
            <h2 class="box-title"><?php echo $title; ?></h2>
        <?php endif; ?>
        <div class="box-content" data-type="epsisode">
            <form action="" id="search" class="searchbox search-form">
                <div class="searchcontainer">
                    <input placeholder="<?php echo $release_box_search_placeholder; ?>" class="searchbar search-field">
                </div>
                <div class="refreshlink btn-refresh">
                    <a class="refreshbutton" href="#">
                        <i title="<?php echo $refresh_text; ?>" data-refresh-text="<?php echo $refresh_text; ?>"
                           data-clear-text="<?php echo $clear_text; ?>" class="dashicons dashicons-update"></i>
                    </a>
                </div>
            </form>
            <div class="latest">
                <ul class="list-releases">
                    <?php
                    while ($query->have_posts()) {
                        $query->the_post();
                        $post = get_post(get_the_ID());
                        $post_not_in[] = $post->ID;
                        $post_title = '';
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
                                $post_link = fansub_kntl_build_single_url($single_page, $animation->ID);
                                if (!fansub_array_has_value($parts)) {

                                }
                                $part = array_pop($parts);
                                if (!empty($part)) {
                                    $suffix = '<span class="suffix">' . ' - ' . $part . '</span>';
                                }
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
                                    $post_link = fansub_kntl_build_single_url($single_page, $animation->ID);
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
                                    $post_link = fansub_kntl_build_single_url($single_page, $animation->ID);
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
                        } else {
                            $post_title = $post->post_title;
                            $post_link = fansub_kntl_build_single_url($single_page, $post->ID);
                        }
                        $link_html = new FANSUB_HTML('a');
                        $link_html->set_href($post_link);
                        $link_html->set_text($post_title);
                        $link_html->set_class('post-title-link');
                        $saved_suffix = get_post_meta($post->ID, 'suffix', true);
                        if (!empty($saved_suffix)) {
                            $suffix = '<span class="suffix">' . ' - ' . $saved_suffix . '</span>';
                        }
                        $qs = fansub_ph_get_qualities_and_servers(get_the_ID());

                        $qualities = $qs['qualities'];
                        $servers = $qs['servers'];
                        ?>
                        <li <?php post_class(sanitize_html_class($post->post_type)); ?>>
                            <ul class="list-cols">
                                <li class="col-date"><span
                                        class="post-date">(<?php echo get_the_date($date_format); ?>)</span></li>
                                <li class="col-title"><span
                                        class="post-title"><?php $link_html->output(); ?><?php echo $suffix; ?></span>
                                </li>
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
                    }
                    wp_reset_postdata();
                    ?>
                </ul>
                <?php $query_vars['post__not_in'] = $post_not_in; ?>
                <input type="hidden" class="default-query-vars"
                       value="<?php echo esc_attr(json_encode($query->query_vars)); ?>" autocomplete="off">
                <input type="hidden" class="query-vars"
                       data-default="<?php echo esc_attr(json_encode($query_vars)); ?>"
                       value="<?php echo esc_attr(json_encode($query_vars)); ?>" autocomplete="off">
                <input type="hidden" class="options-data" value="<?php echo esc_attr(json_encode($data)); ?>"
                       autocomplete="off">
            </div>
            <?php
            $load_more_text = fansub_get_value_by_key($option_data, 'show_more_text');
            $loading_text = fansub_get_value_by_key($option_data, 'loading_text');
            $reached_end_text = fansub_get_value_by_key($option_data, 'reached_end_text');
            ?>
            <div class="morebox">
                <a id="0" class="morebutton btn-more" style="display: block;" href="#"
                   data-reached-end-text="<?php echo $reached_end_text; ?>"
                   data-loading-text="<?php echo $loading_text; ?>"
                   data-text="<?php echo $load_more_text; ?>"><?php echo $load_more_text; ?></a>
            </div>
        </div>
    </div>
    <?php
    $result = ob_get_clean();

    return $result;
}

add_shortcode('fansub_episode', 'fansub_kntl_episode_shortcode');

function fansub_kntl_batch_shortcode($atts, $content = null)
{
    $defaults = fansub_kntl_get_option_defaults();
    $option_data = fansub_kntl_get_option();
    $option_data = shortcode_atts($defaults, $option_data);
    $option_data = shortcode_atts($option_data, $atts);
    $release_title = fansub_get_value_by_key($option_data, 'release_box_title');
    $post_number = fansub_get_value_by_key($option_data, 'posts_per_page');
    $date_format = fansub_get_value_by_key($option_data, 'date_format');
    $single_page = fansub_get_value_by_key($option_data, 'single_page');
    $release_box_search_placeholder = fansub_get_value_by_key($option_data, 'release_box_search_placeholder');
    $data = $option_data;
    $result = '';
    $title = isset($data['release_box_title']) ? $data['release_box_title'] : $release_title;
    $number = isset($data['posts_per_page']) ? $data['posts_per_page'] : $post_number;
    $query_args = array(
        'posts_per_page' => $number,
        'post_type' => 'batch'
    );
    $query = fansub_query($query_args);
    $date_format = isset($data['date_format']) ? $data['date_format'] : $date_format;
    $post_type = fansub_kntl_get_post_type();
    $option_data = fansub_kntl_get_option();

    $query_vars = $query->query_vars;

    $post_not_in = isset($query->query_vars['post__not_in']) ? $query->query_vars['post__not_in'] : array();
    $refresh_text = fansub_get_value_by_key($option_data, 'refresh_text');
    $clear_text = fansub_get_value_by_key($option_data, 'clear_text');
    ob_start();
    ?>
    <div class="fansub-new-release fansub-box fansub-batch">
        <?php if (!empty($title)) : ?>
            <h2 class="box-title"><?php echo $title; ?></h2>
        <?php endif; ?>
        <div class="box-content" data-type="batch">
            <form action="" id="search" class="searchbox search-form">
                <div class="searchcontainer">
                    <input placeholder="<?php echo $release_box_search_placeholder; ?>" class="searchbar search-field">
                </div>
                <div class="refreshlink btn-refresh">
                    <a class="refreshbutton" href="#">
                        <i title="<?php echo $refresh_text; ?>" data-refresh-text="<?php echo $refresh_text; ?>"
                           data-clear-text="<?php echo $clear_text; ?>" class="dashicons dashicons-update"></i>
                    </a>
                </div>
            </form>
            <div class="latest">
                <ul class="list-releases">
                    <?php
                    while ($query->have_posts()) {
                        $query->the_post();
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
                                //$post_title = $animation->post_title;
                                $post_link = fansub_kntl_build_single_url($single_page, $animation->ID);
                                if (!fansub_array_has_value($parts)) {

                                }
                                $part = array_pop($parts);
                                if (!empty($part)) {
                                    $suffix = '<span class="suffix">' . ' - ' . $part . '</span>';
                                }
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
                                    //$post_title = $animation->post_title;
                                    $post_link = fansub_kntl_build_single_url($single_page, $animation->ID);
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
                                    //$post_title = $animation->post_title;
                                    $post_link = fansub_kntl_build_single_url($single_page, $animation->ID);
                                    if (!fansub_array_has_value($parts)) {

                                    }
                                    $part = array_pop($parts);
                                    if (!empty($part)) {
                                        $tmp_part = array_pop($parts);
                                        if (!empty($tmp_part)) {
                                            //$part = $tmp_part . '-' . $part;
                                        }
                                        $suffix = '<span class="suffix">' . ' - ' . $part . '</span>';
                                    }
                                }
                            }
                        } else {
                            $post_title = $post->post_title;
                            $post_link = fansub_kntl_build_single_url($single_page, $post->ID);
                        }
                        $link_html = new FANSUB_HTML('a');
                        $link_html->set_href($post_link);
                        $link_html->set_text($post_title);
                        $link_html->set_class('post-title-link');
                        $saved_suffix = get_post_meta($post->ID, 'suffix', true);
                        $suffix = '';
                        if (!empty($saved_suffix)) {
                            $suffix = '<span class="suffix">' . ' - ' . $saved_suffix . '</span>';
                        }
                        $qs = fansub_ph_get_qualities_and_servers(get_the_ID());

                        $qualities = $qs['qualities'];
                        $servers = $qs['servers'];
                        ?>
                        <li <?php post_class(sanitize_html_class($post->post_type)); ?>>
                            <ul class="list-cols">
                                <li class="col-date"><span
                                        class="post-date">(<?php echo get_the_date($date_format); ?>)</span></li>
                                <li class="col-title"><span
                                        class="post-title"><?php $link_html->output(); ?><?php echo $suffix; ?></span>
                                </li>
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
                    }
                    wp_reset_postdata();
                    ?>
                </ul>
                <?php $query_vars['post__not_in'] = $post_not_in; ?>
                <input type="hidden" class="default-query-vars"
                       value="<?php echo esc_attr(json_encode($query->query_vars)); ?>" autocomplete="off">
                <input type="hidden" class="query-vars"
                       data-default="<?php echo esc_attr(json_encode($query_vars)); ?>"
                       value="<?php echo esc_attr(json_encode($query_vars)); ?>" autocomplete="off">
                <input type="hidden" class="options-data" value="<?php echo esc_attr(json_encode($data)); ?>"
                       autocomplete="off">
            </div>
            <?php
            $load_more_text = fansub_get_value_by_key($option_data, 'show_more_text');
            $loading_text = fansub_get_value_by_key($option_data, 'loading_text');
            $reached_end_text = fansub_get_value_by_key($option_data, 'reached_end_text');
            ?>
            <div class="morebox">
                <a id="0" class="morebutton btn-more" style="display: block;" href="#"
                   data-reached-end-text="<?php echo $reached_end_text; ?>"
                   data-loading-text="<?php echo $loading_text; ?>"
                   data-text="<?php echo $load_more_text; ?>"><?php echo $load_more_text; ?></a>
            </div>
        </div>
    </div>
    <?php
    $result = ob_get_clean();

    return $result;
}

add_shortcode('fansub_batch', 'fansub_kntl_batch_shortcode');

function fansub_kntl_anime_list($atts, $content = null)
{
    $defaults = fansub_kntl_get_option_defaults();
    $option_data = fansub_kntl_get_option();
    $default_atts = array('title' => 'Anime Titles');
    $option_data = wp_parse_args($option_data, $defaults);
    $atts = shortcode_atts($default_atts, $atts);
    $option_data = wp_parse_args($atts, $option_data);
    $box_title = fansub_get_value_by_key($option_data, 'title');
    $single_page = fansub_get_value_by_key($option_data, 'single_page');
    $posts_per_page = fansub_get_value_by_key($option_data, 'anime_number', 20);
    $posts_per_page = 12;
    $post_type = fansub_kntl_get_post_type();
    $args = array(
        'posts_per_page' => $posts_per_page,
        'post_type' => $post_type,
        'orderby' => 'title',
        'order' => 'ASC',
        'paged' => fansub_get_paged()
    );
    $query = fansub_query($args);
    if (!empty($box_title)) {
        $box_title = fansub_wrap_tag($box_title, 'h2');
    }
    $result = '<div class="anime-list fansub-new-release">' . $box_title;
    if ($query->have_posts()) {
        $pagination_args = array(
            'query' => $query,
            'show_first_item' => true,
            'label' => '',
            'first' => '««',
            'last' => '»»',
            'ajax' => true,
            'current_item_link' => true
        );
        ob_start();
        fansub_pagination($pagination_args);
        $pagination = ob_get_clean();
        $result .= $pagination;
        $result .= '<ul class="list-unstyled list-animes">';
        $loop_html = '';
        while ($query->have_posts()) {
            $query->the_post();
            $permalink = fansub_kntl_build_single_url($single_page, get_the_ID());
            ob_start();
            ?>
            <li <?php post_class(); ?>>
                <?php
                fansub_post_thumbnail(array(
                    'bfi_thumb' => false,
                    'lazyload' => true,
                    'before' => '<div class="anime-thumb">',
                    'after' => '</div>',
                    'permalink' => $permalink
                ));
                fansub_post_title_link(array('permalink' => $permalink));
                fansub_entry_summary();
                ?>
            </li>
            <?php
            $loop_html .= ob_get_clean();
        }
        wp_reset_postdata();
        $result .= $loop_html;
        $result .= '</ul>';
        $result .= $pagination;
    }
    $result .= '</div>';

    return $result;
}

add_shortcode('fansub_list', 'fansub_kntl_anime_list');

function fansub_kntl_advanced_search($atts, $content = null)
{
    $defaults = fansub_kntl_get_option_defaults();
    $option_data = fansub_kntl_get_option();
    $default_atts = array('title' => '');
    $option_data = wp_parse_args($option_data, $defaults);
    $atts = shortcode_atts($default_atts, $atts);
    $option_data = wp_parse_args($atts, $option_data);
    $box_title = fansub_get_value_by_key($option_data, 'title');
    $placeholder = fansub_get_value_by_key($option_data, 'placehoder', 'Search Anime...');
    $single_page = fansub_get_value_by_key($option_data, 'single_page');
    $post_type = fansub_kntl_get_post_type();
    $q = isset($_REQUEST['q']) ? $_REQUEST['q'] : '';
    $args = array(
        'posts_per_page' => -1,
        'post_type' => $post_type,
        's' => $q
    );
    $query = fansub_query($args);
    if (!empty($box_title)) {
        $box_title = fansub_wrap_tag($box_title, 'h2');
    }
    $result = '<div class="advanced-search fansub-new-release">' . $box_title . '<div class="module-body">';
    $form = fansub_search_form(array(
        'placeholder' => $placeholder,
        'action' => get_permalink(),
        'echo' => false,
        'class' => 'advanced-search-form',
        'name' => 'q'
    ));
    $result .= $form;
    if ($query->have_posts() && !empty($q)) {
        $result .= '<div class="anime-list search-results">';
        $result .= '<h4>Search Results</h4>';
        $result .= '<ul class="list-unstyled list-animes">';
        $loop_html = '';
        while ($query->have_posts()) {
            $query->the_post();
            $permalink = fansub_kntl_build_single_url($single_page, get_the_ID());
            ob_start();
            ?>
            <li <?php post_class(); ?>>
                <?php
                fansub_post_thumbnail(array(
                    'bfi_thumb' => false,
                    'lazyload' => true,
                    'before' => '<div class="anime-thumb">',
                    'after' => '</div>',
                    'permalink' => $permalink
                ));
                fansub_post_title_link(array('permalink' => $permalink));
                fansub_entry_summary();
                ?>
            </li>
            <?php
            $loop_html .= ob_get_clean();
        }
        wp_reset_postdata();
        $result .= $loop_html;
        $result .= '</ul></div>';
    }
    $result .= '</div></div>';

    return $result;
}

add_shortcode('fansub_advanced_search', 'fansub_kntl_advanced_search');

function fansub_kntl_jwplayer_shortcode_embed($atts, $content = null)
{
    $defaults = fansub_kntl_get_option_defaults();
    $option_data = fansub_kntl_get_option();
    $default_atts = array('title' => '');
    $option_data = wp_parse_args($option_data, $defaults);
    $atts = shortcode_atts($default_atts, $atts);
    $option_data = wp_parse_args($atts, $option_data);
    $post_id = fansub_get_value_by_key($option_data, 'post_id');
    $result = '';
    if (!fansub_id_number_valid($post_id)) {
        $post_id = isset($_REQUEST['post_id']) ? $_REQUEST['post_id'] : 0;
    }
    if (fansub_id_number_valid($post_id)) {
        global $post;
        $tmp = $post;
        $post = get_post($post_id);
        setup_postdata($post);
        ob_start();
        the_content();
        $html_data = ob_get_clean();
        wp_reset_postdata();
        $post = $tmp;
        $result = $html_data;
    }

    return $result;
}

add_shortcode('fansub_jwplayer', 'fansub_kntl_jwplayer_shortcode_embed');