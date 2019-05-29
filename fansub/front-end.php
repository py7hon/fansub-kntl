<?php
if(!function_exists('add_filter')) exit;

function hocwp_breadcrumb($args = array()) {
    $before = hocwp_get_value_by_key($args, 'before');
    $after = hocwp_get_value_by_key($args, 'after');
    if(function_exists('yoast_breadcrumb') && hocwp_wpseo_breadcrumb_enabled()) {
        yoast_breadcrumb('<nav class="fansub-breadcrumb breadcrumb yoast clearfix">' . $before, $after . '</nav>');
        return;
    }
    global $post;
    $separator = isset($args['separator']) ? $args['separator'] : '/';
    $breadcrums_id = isset($args['id']) ? $args['id'] : 'fansub_breadcrumbs';
    $home_title = __('Home', 'fansub');
    $custom_taxonomy = 'product_cat';
    $class = isset($args['class']) ? $args['class'] : '';
    $class = fansub_add_string_with_space_before($class, 'list-inline list-unstyled breadcrumbs');
    if(!is_front_page()) {
        echo '<div class="fansub-breadcrumb breadcrumb default clearfix">';
        echo '<ul id="' . $breadcrums_id . '" class="' . $class . '">';
        echo '<li class="item-home"><a class="bread-link bread-home" href="' . get_home_url() . '" title="' . $home_title . '">' . $home_title . '</a></li>';
        echo '<li class="separator separator-home"> ' . $separator . ' </li>';
        if(is_post_type_archive()) {
            echo '<li class="item-current item-archive"><strong class="bread-current bread-archive">' . post_type_archive_title('', false) . '</strong></li>';
        } elseif(is_archive() && is_tax() && !is_category()) {
            $post_type = get_post_type();
            if($post_type != 'post') {
                $post_type_object = get_post_type_object($post_type);
                $post_type_archive = get_post_type_archive_link($post_type);
                if(is_object($post_type_object)) {
                    echo '<li class="item-cat item-custom-post-type-' . $post_type . '"><a class="bread-cat bread-custom-post-type-' . $post_type . '" href="' . $post_type_archive . '" title="' . $post_type_object->labels->name . '">' . $post_type_object->labels->name . '</a></li>';
                    echo '<li class="separator"> ' . $separator . ' </li>';
                }
            }
            if(is_search()) {
                echo '<li class="item-current item-current-' . get_search_query() . '"><strong class="bread-current bread-current-' . get_search_query() . '" title="Search results for: ' . get_search_query() . '">Search results for: ' . get_search_query() . '</strong></li>';
            } else {
                $custom_tax_name = get_queried_object()->name;
                echo '<li class="item-current item-archive"><strong class="bread-current bread-archive">' . $custom_tax_name . '</strong></li>';
            }
        } elseif(is_single()) {
            $post_type = get_post_type();
            if($post_type != 'post') {
                $post_type_object = get_post_type_object($post_type);
                $post_type_archive = get_post_type_archive_link($post_type);
                echo '<li class="item-cat item-custom-post-type-' . $post_type . '"><a class="bread-cat bread-custom-post-type-' . $post_type . '" href="' . $post_type_archive . '" title="' . $post_type_object->labels->name . '">' . $post_type_object->labels->name . '</a></li>';
                echo '<li class="separator"> ' . $separator . ' </li>';
            }
            $category = get_the_category();
            $array_values = array_values($category);
            $last_category = end($array_values);
            $get_cat_parents = '';
            if(is_object($last_category)) {
                $get_cat_parents = rtrim(get_category_parents($last_category->term_id, true, ','), ',');
            }
            $cat_parents = explode(',', $get_cat_parents);
            $cat_display = '';
            foreach($cat_parents as $parents) {
                $cat_display .= '<li class="item-cat">' . $parents . '</li>';
                $cat_display .= '<li class="separator"> ' . $separator . ' </li>';
            }
            $taxonomy_exists = taxonomy_exists($custom_taxonomy);
            if(empty($last_category) && !empty($custom_taxonomy) && $taxonomy_exists) {
                $taxonomy_terms = get_the_terms($post->ID, $custom_taxonomy);
                if(isset($taxonomy_terms[0]) && is_a($taxonomy_terms[0], 'WP_Term')) {
                    $cat_id = $taxonomy_terms[0]->term_id;
                    $cat_nicename = $taxonomy_terms[0]->slug;
                    $cat_link = get_term_link($taxonomy_terms[0]->term_id, $custom_taxonomy);
                    $cat_name = $taxonomy_terms[0]->name;
                }
            }
            if(!empty($last_category)) {
                echo $cat_display;
                echo '<li class="item-current item-' . $post->ID . '"><strong class="bread-current bread-' . $post->ID . '" title="' . get_the_title() . '">' . get_the_title() . '</strong></li>';
            } elseif(!empty($cat_id)) {
                echo '<li class="item-cat item-cat-' . $cat_id . ' item-cat-' . $cat_nicename . '"><a class="bread-cat bread-cat-' . $cat_id . ' bread-cat-' . $cat_nicename . '" href="' . $cat_link . '" title="' . $cat_name . '">' . $cat_name . '</a></li>';
                echo '<li class="separator"> ' . $separator . ' </li>';
                echo '<li class="item-current item-' . $post->ID . '"><strong class="bread-current bread-' . $post->ID . '" title="' . get_the_title() . '">' . get_the_title() . '</strong></li>';

            } else {
                echo '<li class="item-current item-' . $post->ID . '"><strong class="bread-current bread-' . $post->ID . '" title="' . get_the_title() . '">' . get_the_title() . '</strong></li>';
            }
        } elseif(is_category()) {
            echo '<li class="item-current item-cat"><strong class="bread-current bread-cat">' . single_cat_title('', false) . '</strong></li>';
        } elseif(is_page()) {
            if($post->post_parent) {
                $anc = get_post_ancestors($post->ID);
                $anc = array_reverse($anc);
                $anc = array_reverse($anc);
                $parents = '';
                foreach($anc as $ancestor) {
                    $parents .= '<li class="item-parent item-parent-' . $ancestor . '"><a class="bread-parent bread-parent-' . $ancestor . '" href="' . get_permalink($ancestor) . '" title="' . get_the_title($ancestor) . '">' . get_the_title($ancestor) . '</a></li>';
                    $parents .= '<li class="separator separator-' . $ancestor . '"> ' . $separator . ' </li>';
                }
                echo $parents;
                echo '<li class="item-current item-' . $post->ID . '"><strong title="' . get_the_title() . '"> ' . get_the_title() . '</strong></li>';
            } else {
                echo '<li class="item-current item-' . $post->ID . '"><strong class="bread-current bread-' . $post->ID . '"> ' . get_the_title() . '</strong></li>';
            }
        } elseif(is_tag()) {
            $term_id = get_query_var('tag_id');
            $taxonomy = 'post_tag';
            $args ='include=' . $term_id;
            $terms = fansub_get_terms($taxonomy, $args);
            if(fansub_array_has_value($terms)) {
                echo '<li class="item-current item-tag-' . $terms[0]->term_id . ' item-tag-' . $terms[0]->slug . '"><strong class="bread-current bread-tag-' . $terms[0]->term_id . ' bread-tag-' . $terms[0]->slug . '">' . $terms[0]->name . '</strong></li>';
            }
        } elseif(is_day()) {
            echo '<li class="item-year item-year-' . get_the_time('Y') . '"><a class="bread-year bread-year-' . get_the_time('Y') . '" href="' . get_year_link(get_the_time('Y')) . '" title="' . get_the_time('Y') . '">' . get_the_time('Y') . ' Archives</a></li>';
            echo '<li class="separator separator-' . get_the_time('Y') . '"> ' . $separator . ' </li>';
            echo '<li class="item-month item-month-' . get_the_time('m') . '"><a class="bread-month bread-month-' . get_the_time('m') . '" href="' . get_month_link(get_the_time('Y'), get_the_time('m')) . '" title="' . get_the_time('M') . '">' . get_the_time('M') . ' Archives</a></li>';
            echo '<li class="separator separator-' . get_the_time('m') . '"> ' . $separator . ' </li>';
            echo '<li class="item-current item-' . get_the_time('j') . '"><strong class="bread-current bread-' . get_the_time('j') . '"> ' . get_the_time('jS') . ' ' . get_the_time('M') . ' Archives</strong></li>';
        } elseif(is_month()) {
            echo '<li class="item-year item-year-' . get_the_time('Y') . '"><a class="bread-year bread-year-' . get_the_time('Y') . '" href="' . get_year_link(get_the_time('Y')) . '" title="' . get_the_time('Y') . '">' . get_the_time('Y') . ' Archives</a></li>';
            echo '<li class="separator separator-' . get_the_time('Y') . '"> ' . $separator . ' </li>';
            echo '<li class="item-month item-month-' . get_the_time('m') . '"><strong class="bread-month bread-month-' . get_the_time('m') . '" title="' . get_the_time('M') . '">' . get_the_time('M') . ' Archives</strong></li>';
        } elseif(is_year()) {
            echo '<li class="item-current item-current-' . get_the_time('Y') . '"><strong class="bread-current bread-current-' . get_the_time('Y') . '" title="' . get_the_time('Y') . '">' . get_the_time('Y') . ' Archives</strong></li>';
        } elseif(is_author()) {
            global $author;
            $userdata = get_userdata($author);
            echo '<li class="item-current item-current-' . $userdata->user_nicename . '"><strong class="bread-current bread-current-' . $userdata->user_nicename . '" title="' . $userdata->display_name . '">' . 'Author: ' . $userdata->display_name . '</strong></li>';
        } elseif(get_query_var('paged')) {
            echo '<li class="item-current item-current-' . get_query_var('paged') . '"><strong class="bread-current bread-current-' . get_query_var('paged') . '" title="Page ' . get_query_var('paged') . '">'.__('Page') . ' ' . get_query_var('paged') . '</strong></li>';
        } elseif(is_search()) {
            echo '<li class="item-current item-current-' . get_search_query() . '"><strong class="bread-current bread-current-' . get_search_query() . '" title="Search results for: ' . get_search_query() . '">Search results for: ' . get_search_query() . '</strong></li>';
        } elseif(is_404()) {
            echo '<li>' . __('Error 404', 'fansub') . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
}

function fansub_facebook_login_button() {
    $action = fansub_get_method_value('action', 'request');
    ?>
    <button type="button" data-action="login-facebook" onclick="fansub_facebook_login();" class="btn-facebook btn-social-login btn btn-large">
        <svg class="flicon-facebook flip-icon" viewBox="0 0 256 448" height="448" width="256" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns="http://www.w3.org/2000/svg" version="1.1">
            <path d="M239.75 3v66h-39.25q-21.5 0-29 9t-7.5 27v47.25h73.25l-9.75 74h-63.5v189.75h-76.5v-189.75h-63.75v-74h63.75v-54.5q0-46.5 26-72.125t69.25-25.625q36.75 0 57 3z"/>
        </svg>
        <span>
            <?php
            if('register' == $action) {
                fansub_text('Đăng ký bằng Facebook', __('Register with Facebook', 'fansub'));
            } else {
                fansub_text('Đăng nhập bằng Facebook', __('Login with Facebook', 'fansub'));
            }
            ?>
        </span>
    </button>
    <?php
}

function fansub_google_login_button() {
    $action = fansub_get_method_value('action', 'request');
    ?>
    <button type="button" data-action="login-google" onclick="fansub_google_login();" class="btn-google btn-social-login btn btn-large">
        <svg class="flicon-google flip-icon" viewBox="0 0 30 28" height="448" width="256" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns="http://www.w3.org/2000/svg" version="1.1">
            <path d="M 17.471,2c0,0-6.28,0-8.373,0C 5.344,2, 1.811,4.844, 1.811,8.138c0,3.366, 2.559,6.083, 6.378,6.083 c 0.266,0, 0.524-0.005, 0.776-0.024c-0.248,0.475-0.425,1.009-0.425,1.564c0,0.936, 0.503,1.694, 1.14,2.313 c-0.481,0-0.945,0.014-1.452,0.014C 3.579,18.089,0,21.050,0,24.121c0,3.024, 3.923,4.916, 8.573,4.916 c 5.301,0, 8.228-3.008, 8.228-6.032c0-2.425-0.716-3.877-2.928-5.442c-0.757-0.536-2.204-1.839-2.204-2.604 c0-0.897, 0.256-1.34, 1.607-2.395c 1.385-1.082, 2.365-2.603, 2.365-4.372c0-2.106-0.938-4.159-2.699-4.837l 2.655,0 L 17.471,2z M 14.546,22.483c 0.066,0.28, 0.103,0.569, 0.103,0.863c0,2.444-1.575,4.353-6.093,4.353 c-3.214,0-5.535-2.034-5.535-4.478c0-2.395, 2.879-4.389, 6.093-4.354c 0.75,0.008, 1.449,0.129, 2.083,0.334 C 12.942,20.415, 14.193,21.101, 14.546,22.483z M 9.401,13.368c-2.157-0.065-4.207-2.413-4.58-5.246 c-0.372-2.833, 1.074-5.001, 3.231-4.937c 2.157,0.065, 4.207,2.338, 4.58,5.171 C 13.004,11.189, 11.557,13.433, 9.401,13.368zM 26,8L 26,2L 24,2L 24,8L 18,8L 18,10L 24,10L 24,16L 26,16L 26,10L 32,10L 32,8 z"/>
        </svg>
        <span>
            <?php
            if('register' == $action) {
                fansub_text('Đăng ký bằng Google', __('Register with Google', 'fansub'));
            } else {
                fansub_text('Đăng nhập bằng Google', __('Login with Google', 'fansub'));
            }
            ?>
        </span>
    </button>
    <?php
}

function fansub_entry_meta_terms($args = array()) {
    $taxonomy = fansub_get_value_by_key($args, 'taxonomy', 'category');
    if(empty($taxonomy)) {
        return;
    }
    $meta_class = 'entry-terms';
    fansub_add_string_with_space_before($meta_class, 'tax-' . fansub_sanitize_html_class($taxonomy));
    $icon = fansub_get_value_by_key($args, 'icon', '<i class="fa fa-list-alt icon-left"></i>');
    $before = fansub_get_value_by_key($args, 'before', '<span class="' . $meta_class . '">');
    $after = fansub_get_value_by_key($args, 'after', '</span>');
    $post_id = fansub_get_value_by_key($args, 'post_id', get_the_ID());
    $separator = fansub_get_value_by_key($args, 'separator', ', ');
    the_terms($post_id, $taxonomy, $before . $icon, $separator, $after);
}

function fansub_the_date() {
    ?>
    <time datetime="<?php the_time('c'); ?>" itemprop="datePublished" class="entry-time published date post-date"><?php echo get_the_date(); ?></time>
    <?php
}

function fansub_the_comment_link() {
    $post_id = get_the_ID();
    if(comments_open($post_id)) {
        $comment_count = fansub_get_post_comment_count($post_id);
        $comment_text = $comment_count . ' Bình luận';
        ?>
        <span class="entry-comments-link">
            <a href="<?php the_permalink(); ?>#comments"><?php echo $comment_text; ?></a>
        </span>
        <?php
    }
}

function fansub_the_author($args = array()) {
    $before = fansub_get_value_by_key($args, 'before');
    $author_url = fansub_get_author_posts_url();
    ?>
    <span itemtype="http://schema.org/Person" itemscope itemprop="author" class="entry-author vcard author post-author">
        <?php if(!empty($before)) : ?>
            <span class="before-text"><?php echo $before; ?></span>
        <?php endif; ?>
        <span class="fn">
            <a rel="author" itemprop="url" class="entry-author-link" href="<?php echo $author_url; ?>"><span itemprop="name" class="entry-author-name"><?php the_author(); ?></span></a>
        </span>
    </span>
    <?php
}

function fansub_entry_meta($args = array()) {
    $post_id = fansub_get_value_by_key($args, 'post_id', get_the_ID());
    $class = fansub_get_value_by_key($args, 'class');
    if(!isset($args['taxonomy'])) {
        $args['taxonomy'] = '';
    }
    $cpost = get_post($post_id);
    if(!is_a($cpost, 'WP_Post')) {
        return;
    }
    $author_url = fansub_get_author_posts_url();
    $comment_count = fansub_get_post_comment_count($post_id);
    $comment_text = $comment_count . ' Bình luận';
    fansub_add_string_with_space_before($class, 'entry-meta');
    $show_date = fansub_get_value_by_key($args, 'show_date', true);
    $show_updated = fansub_get_value_by_key($args, 'show_updated', true);
    $show_author = fansub_get_value_by_key($args, 'show_author', true);
    $show_term = fansub_get_value_by_key($args, 'show_term', false);
    $show_comment = fansub_get_value_by_key($args, 'show_comment', true);
    ?>
    <p class="<?php echo $class; ?>">
        <?php if($show_date) : ?>
            <?php fansub_the_date(); ?>
        <?php endif; ?>
        <?php if($show_updated) : ?>
            <time datetime="<?php the_modified_time('c'); ?>" itemprop="dateModified" class="entry-modified-time date modified post-date"><?php the_modified_date(); ?></time>
        <?php endif; ?>
        <?php if($show_author) : ?>
            <?php fansub_the_author(); ?>
        <?php endif; ?>
        <?php
        if($show_term) {
            $meta_term_args = $args;
            $term_icon = fansub_get_value_by_key($args, 'term_icon');
            if(!empty($term_icon)) {
                $meta_term_args['icon'] = $term_icon;
            }
            fansub_entry_meta_terms($meta_term_args);
        }
        ?>
        <?php if($show_comment && comments_open($post_id)) : ?>
            <?php fansub_the_comment_link(); ?>
        <?php endif; ?>
        <?php if(current_theme_supports('fansub-schema')) : ?>
            <?php
            global $authordata;
            $author_id = 0;
            $author_name = '';
            $author_avatar = '';
            if(fansub_object_valid($authordata)) {
                $author_id = $authordata->ID;
                $author_name = $authordata->display_name;
                $author_avatar = get_avatar_url($author_id, array('size' => 128));
            }
            $logo_url = apply_filters('fansub_publisher_logo_url', '');
            ?>
            <span itemprop="publisher" itemscope itemtype="https://schema.org/Organization" class="small hidden">
                <span itemprop="logo" itemscope itemtype="https://schema.org/ImageObject">
                    <img alt="" src="<?php echo $logo_url; ?>">
                    <meta itemprop="url" content="<?php echo $logo_url; ?>">
                    <meta itemprop="width" content="600">
                    <meta itemprop="height" content="60">
                </span>
                <meta itemprop="name" content="<?php echo $author_name; ?>">
            </span>
        <?php endif; ?>
    </p>
    <?php
}

function fansub_entry_meta_author_first($args = array()) {
    $post_id = fansub_get_value_by_key($args, 'post_id', get_the_ID());
    $class = fansub_get_value_by_key($args, 'class');
    $cpost = get_post($post_id);
    if(!is_a($cpost, 'WP_Post')) {
        return;
    }
    $author_url = fansub_get_author_posts_url();
    $comment_count = fansub_get_post_comment_count($post_id);
    $comment_text = $comment_count . ' Bình luận';
    fansub_add_string_with_space_before($class, 'entry-meta');
    ?>
    <p class="<?php echo $class; ?>">
        <span itemtype="http://schema.org/Person" itemscope itemprop="author" class="entry-author vcard author post-author">
            <span class="fn">
                <a rel="author" itemprop="url" class="entry-author-link" href="<?php echo $author_url; ?>"><span itemprop="name" class="entry-author-name"><?php the_author(); ?></span></a>
            </span>
        </span>
        <time datetime="<?php the_time('c'); ?>" itemprop="datePublished" class="entry-time published date post-date"><?php echo get_the_date(); ?></time>
        <time datetime="<?php the_modified_time('c'); ?>" itemprop="dateModified" class="entry-modified-time date modified post-date"><?php the_modified_date(); ?></time>
        <?php if(comments_open($post_id)) : ?>
            <span class="entry-comments-link">
                <a href="<?php the_permalink(); ?>#comments"><?php echo $comment_text; ?></a>
            </span>
        <?php endif; ?>
        <?php if(current_theme_supports('fansub-schema')) : ?>
            <?php
            global $authordata;
            $author_id = 0;
            $author_name = '';
            $author_avatar = '';
            if(fansub_object_valid($authordata)) {
                $author_id = $authordata->ID;
                $author_name = $authordata->display_name;
                $author_avatar = get_avatar_url($author_id, array('size' => 128));
            }
            $logo_url = apply_filters('fansub_publisher_logo_url', '');
            ?>
            <span itemprop="publisher" itemscope itemtype="https://schema.org/Organization" class="small hidden">
                <span itemprop="logo" itemscope itemtype="https://schema.org/ImageObject">
                    <img alt="" src="<?php echo $logo_url; ?>">
                    <meta itemprop="url" content="<?php echo $logo_url; ?>">
                    <meta itemprop="width" content="600">
                    <meta itemprop="height" content="60">
                </span>
                <meta itemprop="name" content="<?php echo $author_name; ?>">
            </span>
        <?php endif; ?>
    </p>
    <?php
}

function fansub_rel_canonical() {
    if(!is_singular() || has_action('wp_head', 'rel_canonical')) {
        return;
    }
    global $wp_the_query;
    if(!$id = $wp_the_query->get_queried_object_id()) {
        return;
    }
    $link = get_permalink($id);
    if($page = get_query_var('cpage')) {
        $link = get_comments_pagenum_link($page);
    }
    $link = apply_filters('fansub_head_rel_canonical', $link, $id);
    echo "<link rel='canonical' href='$link' />\n";
}

function fansub_posts_pagination($args = array()) {
    $defaults = array(
        'prev_text' => __('Trước', 'fansub'),
        'next_text' => __('Tiếp theo', 'fansub'),
        'screen_reader_text' => __('Phân trang', 'fansub')
    );
    $args = wp_parse_args($args, $defaults);
    the_posts_pagination($args);
}

function fansub_entry_content($content = '') {
    ?>
    <div class="entry-content" itemprop="text">
        <?php
        if(!empty($content)) {
            echo wpautop($content);
        } else {
            the_content();
        }
        ?>
    </div>
    <?php
}

function fansub_entry_summary() {
    echo '<div class="entry-summary" itemprop="text">';
    the_excerpt();
    echo '</div>';
}

function fansub_entry_tags() {
    echo '<div class="entry-tags">';
    the_tags('<span class="tag-label"><i class="fa fa-tag icon-left"></i><span class="text">Tags:</span></span>&nbsp;', ' ', '');
    echo '</div>';
}

function fansub_button_vote_group() {
    $post_id = get_the_ID();
    $vote_up = absint(get_post_meta($post_id, 'likes', true));
    $vote_down = absint(fansub_get_post_meta('dislikes', $post_id));
    ?>
    <div class="text-center vote-buttons">
        <p class="vote btn-group" data-post-id="<?php the_ID(); ?>">
            <a class="btn btn-default vote-up vote-post" data-vote-type="up" data-vote="<?php echo $vote_up; ?>">
                <i class="fa fa-thumbs-o-up"></i>
            </a>
            <a class="btn btn-default vote-down vote-post" data-vote-type="down" data-vote="<?php echo $vote_down; ?>">
                <i class="fa fa-thumbs-o-down"></i>
            </a>
        </p>
    </div>
    <?php
}