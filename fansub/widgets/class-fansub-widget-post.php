<?php
if(!function_exists('add_filter')) exit;
class FANSUB_Widget_Post extends WP_Widget {
    public $args = array();
    public $admin_args;

    private function get_defaults() {
        $defaults = array(
            'bys' => array(
                'recent' => __('Recent posts', 'fansub'),
                'recent_modified' => __('Recent modified posts', 'fansub'),
                'random' => __('Random posts', 'fansub'),
                'comment' => __('Most comment posts', 'fansub'),
                'category' => __('Posts by category', 'fansub'),
                'featured' => __('Featured posts', 'fansub'),
                'related' => __('Related posts', 'fansub'),
                'like' => __('Most likes posts', 'fansub'),
                'view' => __('Most views posts', 'fansub'),
                'favorite' => __('Most favorite posts', 'fansub'),
                'rate' => __('Most rate posts', 'fansub')
            ),
            'post_type' => array(array('value' => 'post')),
            'by' => 'recent',
            'number' => 5,
            'category' => array(),
            'excerpt_length' => 75,
            'thumbnail_size' => array(64, 64),
            'title_length' => 50,
            'show_author' => 0,
            'show_date' => 0,
            'show_comment_count' => 0,
            'only_thumbnail' => 0,
            'hide_thumbnail' => 0,
            'widget_title_link_category' => 0,
            'category_as_widget_title' => 1,
            'full_width_posts' => array(
                'none' => __('None', 'fansub'),
                'first' => __('First post', 'fansub'),
                'last' => __('Last post', 'fansub'),
                'first_last' => __('First post and last post', 'fansub'),
                'odd' => __('Odd posts', 'fansub'),
                'even' => __('Even posts', 'fansub'),
                'all' => __('All posts', 'fansub')
            ),
            'full_width_post' => 'none',
            'times' => array(
                'all' => __('All time', 'fansub'),
                'daily' => __('Daily', 'fansub'),
                'weekly' => __('Weekly', 'fansub'),
                'monthly' => __('Monthly', 'fansub'),
                'yearly' => __('Yearly', 'fansub')
            ),
            'time' => 'all',
            'orders' => array(
                'desc' => __('DESC', 'fansub'),
                'asc' => __('ASC', 'fansub')
            ),
            'order' => 'desc',
            'orderbys' => array(
                'title' => __('Title', 'fansub'),
                'date' => __('Post date', 'fansub')
            ),
            'orderby' => 'date',
            'slider' => 0
        );
        $defaults = apply_filters('fansub_widget_post_defaults', $defaults);
        $args = apply_filters('fansub_widget_post_args', array());
        $args = wp_parse_args($args, $defaults);
        return $args;
    }

    public function __construct() {
        $this->args = $this->get_defaults();
        $this->admin_args = array(
            'id' => 'fansub_widget_post',
            'name' => 'HocWP Post',
            'class' => 'fansub-widget-post',
            'description' => __('Your site’s most recent Posts and more.', 'fansub'),
            'width' => 400
        );
        $this->admin_args = apply_filters('fansub_widget_post_admin_args', $this->admin_args);
        parent::__construct($this->admin_args['id'], $this->admin_args['name'],
            array(
                'classname' => $this->admin_args['class'],
                'description' => $this->admin_args['description'],
            ),
            array(
                'width' => $this->admin_args['width']
            )
        );
    }

    private function get_post_type_from_instance($instance) {
        $post_type = isset($instance['post_type']) ? $instance['post_type'] : json_encode($this->args['post_type']);
        $post_type = fansub_json_string_to_array($post_type);
        if(!fansub_array_has_value($post_type)) {
            $post_type = array(
                array(
                    'value' => apply_filters('fansub_widget_post_default_post_type', 'post')
                )
            );
        }
        return $post_type;
    }

    public function widget($args, $instance) {
        global $post;
        $title = fansub_widget_title($args, $instance, false);
        $post_type = $this->get_post_type_from_instance($instance);
        $post_types = array();
        foreach($post_type as $fvdata) {
            $ptvalue = isset($fvdata['value']) ? $fvdata['value'] : '';
            if(!empty($ptvalue)) {
                $post_types[] = $ptvalue;
            }
        }
        $number = isset($instance['number']) ? $instance['number'] : $this->args['number'];
        $by = isset($instance['by']) ? $instance['by'] : $this->args['by'];
        $category = isset($instance['category']) ? $instance['category'] : json_encode($this->args['category']);
        $category = fansub_json_string_to_array($category);
        $excerpt_length = isset($instance['excerpt_length']) ? $instance['excerpt_length'] : $this->args['excerpt_length'];
        $thumbnail_size = isset($instance['thumbnail_size']) ? $instance['thumbnail_size'] : $this->args['thumbnail_size'];
        $thumbnail_size = fansub_sanitize_size($thumbnail_size);
        $full_width_post = fansub_get_value_by_key($instance, 'full_width_post', $this->args['full_width_post']);
        $content_class = 'widget-content';
        $slider = fansub_get_value_by_key($instance, 'slider', fansub_get_value_by_key($this->args, 'slider'));
        $title_length = fansub_get_value_by_key($instance, 'title_length', fansub_get_value_by_key($this->args, 'title_length'));
        $hide_thumbnail = fansub_get_value_by_key($instance, 'hide_thumbnail', fansub_get_value_by_key($this->args, 'hide_thumbnail'));
        $widget_title_link_category = fansub_get_value_by_key($instance, 'widget_title_link_category', fansub_get_value_by_key($this->args, 'widget_title_link_category'));
        $category_as_widget_title = fansub_get_value_by_key($instance, 'category_as_widget_title', fansub_get_value_by_key($this->args, 'category_as_widget_title'));
        if((bool)$slider) {
            fansub_add_string_with_space_before($content_class, 'post-slider');
        }
        if('category' == $by && (bool)$widget_title_link_category) {
            $cvalue = current($category);
            $term_id = isset($cvalue['value']) ? $cvalue['value'] : '';
            $term_id = absint($term_id);
            if($term_id > 0) {
                $taxonomy = isset($cvalue['taxonomy']) ? $cvalue['taxonomy'] : '';
                $link = new FANSUB_HTML('a');
                $link->set_class('term-link');
                $link->set_href(get_term_link($term_id, $taxonomy));
                if((bool)$category_as_widget_title) {
                    $term = get_term($term_id, $taxonomy);
                    $title = $term->name;
                } else {
                    $title = apply_filters('widget_title', fansub_get_value_by_key($instance, 'title'));
                }
                $link->set_text($title);
                $title = fansub_get_value_by_key($args, 'before_title') . $link->build() . fansub_get_value_by_key
                        ($args, 'after_title');
            }
        }
        $widget_html = fansub_get_value_by_key($args, 'before_widget') . $title;
        $widget_html .= '<div class="' . $content_class . '">';
        $query_args = array(
            'posts_per_page' => $number,
            'post_type' => $post_types
        );
        $w_query = new WP_Query();
        $get_by = false;
        switch($by) {
            case 'recent':
                $get_by = true;
                break;
            case 'recent_modified':
                $get_by = true;
                $query_args['orderby'] = 'modified';
                $query_args['order'] = 'DESC';
                break;
            case 'random':
                $get_by = true;
                $query_args['orderby'] = 'rand';
                break;
            case 'comment':
                $get_by = true;
                $query_args['orderby'] = 'comment_count';
                break;
            case 'category':
                foreach($category as $cvalue) {
                    $term_id = isset($cvalue['value']) ? $cvalue['value'] : '';
                    $term_id = absint($term_id);
                    if($term_id > 0) {
                        $taxonomy = isset($cvalue['taxonomy']) ? $cvalue['taxonomy'] : '';
                        if(!empty($taxonomy)) {
                            $tax_item = array(
                                'taxonomy' => $taxonomy,
                                'field' => 'term_id',
                                'terms' => $term_id
                            );
                            $query_args = fansub_query_sanitize_tax_query($tax_item, $query_args);
                            $get_by = true;
                        }
                    }
                }
                break;
            case 'related':
                $get_by = true;
                break;
            case 'like':
                $get_by = true;
                $query_args['meta_key'] = 'likes';
                $query_args['orderby'] = 'meta_value_num';
                break;
            case 'view':
                $get_by = true;
                $query_args['meta_key'] = 'views';
                $query_args['orderby'] = 'meta_value_num';
                break;
            case 'featured':
                $get_by = true;
                fansub_query_sanitize_featured_args($query_args);
                break;
            case 'favorite':
                break;
            case 'rate':
                break;
        }
        if($get_by) {
            $query_args = apply_filters('fansub_widget_post_query_args', $query_args, $args, $instance, $this);
            if('related' == $by) {
                $w_query = fansub_query_related_post($query_args);
            } else {
                $w_query = fansub_query($query_args);
            }
        }
        if($w_query->have_posts()) {
            $list_class = 'list-unstyled';
            foreach($post_types as $ptvalue) {
                fansub_add_string_with_space_before($list_class, 'list-' . $ptvalue . 's');
            }
            $list_class = apply_filters('fansub_widget_post_list_class', $list_class);

            if((bool)$slider) {
                $four_posts = array_slice($w_query->posts, 0, 4);
                $next_posts = array_slice($w_query->posts, 4, absint($w_query->post_count - 4));
                $widget_content = apply_filters('fansub_widget_post_slider_html', '', $args, $instance, $this);
                if(empty($widget_content)) {
                    $carousel_id = $this->id;
                    $carousel_id = fansub_sanitize_id($carousel_id);
                    $count = 0;
                    ob_start();
                    ?>
                    <div id="<?php echo $carousel_id; ?>" class="carousel slide" data-ride="carousel">
                        <div class="carousel-inner" role="listbox">
                            <?php
                            foreach($four_posts as $post) {
                                setup_postdata($post);
                                $class = 'item';
                                if(0 == $count) {
                                    fansub_add_string_with_space_before($class, 'active');
                                }
                                ?>
                                <div class="<?php echo $class; ?>">
                                    <?php
                                    fansub_article_before();
                                    do_action('fansub_widget_post_before_post', $args, $instance, $this);
                                    if(!(bool)$hide_thumbnail) {
                                        fansub_post_thumbnail(array('width' => 300, 'height' => 200));
                                    }
                                    do_action('fansub_widget_post_before_post_title', $args, $instance, $this);
                                    fansub_post_title_link(array('title' => fansub_substr(get_the_title(), $title_length)));
                                    do_action('fansub_widget_post_after_post_title', $args, $instance, $this);
                                    fansub_entry_summary();
                                    do_action('fansub_widget_post_after_post', $args, $instance, $this);
                                    fansub_article_after();
                                    ?>
                                </div>
                                <?php
                                $count++;
                            }
                            wp_reset_postdata();
                            ?>
                        </div>
                        <ol class="carousel-indicators list-inline list-unstyled">
                            <?php
                            $count = count($four_posts);
                            for($i = 0; $i < $count; $i++) {
                                $indicator_class = 'indicator-item';
                                if(0 == $i) {
                                    fansub_add_string_with_space_before($indicator_class, 'active');
                                }
                                ?>
                                <li data-target="#<?php echo $carousel_id; ?>" data-slide-to="<?php echo $i; ?>" class="<?php echo $indicator_class; ?>">
                                    <span><?php echo ($i + 1); ?></span>
                                </li>
                                <?php
                            }
                            ?>
                        </ol>
                        <a class="left carousel-control" href="#<?php echo $carousel_id; ?>" role="button" data-slide="prev">
                            <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
                            <span class="sr-only">Previous</span>
                        </a>
                        <a class="right carousel-control" href="#<?php echo $carousel_id; ?>" role="button" data-slide="next">
                            <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                            <span class="sr-only">Next</span>
                        </a>
                    </div>
                    <?php
                    $widget_content = ob_get_clean();
                    if(fansub_array_has_value($next_posts)) {
                        ?>
                        <div class="more-posts">
                            <?php
                            $widget_content .= '<ul class="' . $list_class . '">';
                            $loop_html = apply_filters('fansub_widget_post_loop_html', '', $args, $instance, $this);
                            if(empty($loop_html)) {
                                $count = 0;
                                ob_start();
                                foreach($next_posts as $post) {
                                    setup_postdata($post);
                                    $class = 'a-widget-post';
                                    $full_width = fansub_widget_item_full_width_result($full_width_post, $w_query->post_count, $count);
                                    if($full_width) {
                                        fansub_add_string_with_space_before($class, 'full-width');
                                    }
                                    ?>
                                    <li <?php post_class($class); ?>>
                                        <?php
                                        do_action('fansub_widget_post_before_post', $args, $instance, $this);
                                        if(!(bool)$hide_thumbnail) {
                                            fansub_post_thumbnail(array('width' => $thumbnail_size[0], 'height' => $thumbnail_size[1]));
                                        }
                                        do_action('fansub_widget_post_before_post_title', $args, $instance, $this);
                                        fansub_post_title_link(array('title' => fansub_substr(get_the_title(), $title_length)));
                                        do_action('fansub_widget_post_after_post_title', $args, $instance, $this);
                                        the_excerpt();
                                        do_action('fansub_widget_post_after_post', $args, $instance, $this);
                                        ?>
                                    </li>
                                    <?php
                                    $count++;
                                }
                                wp_reset_postdata();
                                $loop_html .= ob_get_clean();
                            }
                            $widget_content .= $loop_html;
                            $widget_content .= '</ul>';
                            ?>
                        </div>
                        <?php
                    }
                }
                $widget_html .= $widget_content;
            } else {
                $widget_html .= '<ul class="' . $list_class . '">';
                $loop_html = apply_filters('fansub_widget_post_loop_html', '', $args, $instance, $this);
                if(empty($loop_html)) {
                    $count = 0;
                    ob_start();
                    while($w_query->have_posts()) {
                        $w_query->the_post();
                        $class = 'a-widget-post';
                        $full_width = false;
                        if('all' == $full_width_post) {
                            $full_width = true;
                        } elseif('first' == $full_width_post && 0 == $count) {
                            $full_width = true;
                        } elseif('last' == $full_width_post && $count == $w_query->post_count) {
                            $full_width = true;
                        } elseif('first_last' == $full_width_post && (0 == $count || $count == $w_query->post_count)) {
                            $full_width = true;
                        } elseif('odd' == $full_width_post && ($count % 2) != 0) {
                            $full_width = true;
                        } elseif('even' == $full_width_post && ($count % 2) == 0) {
                            $full_width = true;
                        }
                        if($full_width) {
                            fansub_add_string_with_space_before($class, 'full-width');
                        }
                        if($hide_thumbnail) {
                            fansub_add_string_with_space_before($class, 'hide-thumbnail');
                        }
                        ?>
                        <li <?php post_class($class); ?>>
                            <?php
                            do_action('fansub_widget_post_before_post', $args, $instance, $this);
                            if(!(bool)$hide_thumbnail) {
                                fansub_post_thumbnail(array('width' => $thumbnail_size[0], 'height' => $thumbnail_size[1]));
                            }
                            do_action('fansub_widget_post_before_post_title', $args, $instance, $this);
                            fansub_post_title_link(array('title' => fansub_substr(get_the_title(), $title_length)));
                            do_action('fansub_widget_post_after_post_title', $args, $instance, $this);
                            do_action('fansub_widget_post_after_post', $args, $instance, $this);
                            ?>
                        </li>
                        <?php
                        $count++;
                    }
                    wp_reset_postdata();
                    $loop_html .= ob_get_clean();
                }
                $widget_html .= $loop_html;
                $widget_html .= '</ul>';
                $widget_html .= apply_filters('fansub_widget_post_after_loop', '', $args, $instance, $this);
            }
        } else {
            $widget_html .= '<p class="nothing-found">' . __('Nothing found!', 'fansub') . '</p>';
        }
        $widget_html = apply_filters('fansub_widget_post_html', $widget_html, $args, $instance, $this);
        $widget_html .= '</div>';
        $widget_html .= fansub_get_value_by_key($args, 'after_widget');
        echo $widget_html;
    }

    public function form($instance) {
        $title = fansub_get_value_by_key($instance, 'title');
        $post_type = $this->get_post_type_from_instance($instance);
        $number = fansub_get_value_by_key($instance, 'number', fansub_get_value_by_key($this->args, 'number'));
        $by = fansub_get_value_by_key($instance, 'by', fansub_get_value_by_key($this->args, 'by'));
        $category = fansub_get_value_by_key($instance, 'category', json_encode(fansub_get_value_by_key($this->args, 'category')));
        $category = fansub_json_string_to_array($category);
        $thumbnail_size = fansub_get_value_by_key($instance, 'thumbnail_size', fansub_get_value_by_key($this->args, 'thumbnail_size'));
        $full_width_post = fansub_get_value_by_key($instance, 'full_width_post', fansub_get_value_by_key($this->args, 'full_width_post'));

        $title_length = fansub_get_value_by_key($instance, 'title_length', fansub_get_value_by_key($this->args, 'title_length'));
        $hide_thumbnail = fansub_get_value_by_key($instance, 'hide_thumbnail', fansub_get_value_by_key($this->args, 'hide_thumbnail'));
        $widget_title_link_category = fansub_get_value_by_key($instance, 'widget_title_link_category', fansub_get_value_by_key($this->args, 'widget_title_link_category'));
        $category_as_widget_title = fansub_get_value_by_key($instance, 'category_as_widget_title', fansub_get_value_by_key($this->args, 'category_as_widget_title'));

        fansub_field_widget_before($this->admin_args['class']);

        fansub_widget_field_title($this->get_field_id('title'), $this->get_field_name('title'), $title);

        $lists = get_post_types(array('_builtin' => false, 'public' => true), 'objects');
        if(!array_key_exists('post', $lists)) {
            $lists[] = get_post_type_object('post');
        }
        $all_option = '';
        foreach($lists as $lvalue) {
            $selected = '';
            if(!fansub_array_has_value($post_type)) {
                $post_type[] = array('value' => 'post');
            }
            foreach($post_type as $ptvalue) {
                $ptype = isset($ptvalue['value']) ? $ptvalue['value'] : '';
                if($lvalue->name == $ptype) {
                    $selected = $lvalue->name;
                    break;
                }
            }
            $all_option .= fansub_field_get_option(array('value' => $lvalue->name, 'text' => $lvalue->labels->singular_name, 'selected' => $selected));
        }
        $args = array(
            'id' => $this->get_field_id('post_type'),
            'name' => $this->get_field_name('post_type'),
            'all_option' => $all_option,
            'value' => $post_type,
            'label' => __('Post type:', 'fansub'),
            'placeholder' => __('Choose post types', 'fansub'),
            'multiple' => true
        );
        fansub_widget_field('fansub_field_select_chosen', $args);

        $args = array(
            'id' => $this->get_field_id('number'),
            'name' => $this->get_field_name('number'),
            'value' => $number,
            'label' => __('Number posts:', 'fansub')
        );
        fansub_widget_field('fansub_field_input_number', $args);

        $lists = $this->args['bys'];
        $all_option = '';
        foreach($lists as $lkey => $lvalue) {
            $all_option .= fansub_field_get_option(array('value' => $lkey, 'text' => $lvalue, 'selected' => $by));
        }
        $args = array(
            'id' => $this->get_field_id('by'),
            'name' => $this->get_field_name('by'),
            'value' => $by,
            'all_option' => $all_option,
            'label' => __('Get by:', 'fansub'),
            'class' => 'get-by'
        );
        fansub_widget_field('fansub_field_select', $args);

        $all_option = '';
        $taxonomies = fansub_get_hierarchical_taxonomies();
        foreach($taxonomies as $tkey => $tax) {
            $lists = fansub_get_hierarchical_terms(array($tkey));
            if(fansub_array_has_value($lists)) {
                $all_option .= '<optgroup label="' . $tax->labels->singular_name . '">';
                foreach($lists as $lvalue) {
                    $selected = '';
                    if(fansub_array_has_value($category)) {
                        foreach($category as $cvalue) {
                            $term_id = isset($cvalue['value']) ? $cvalue['value'] : 0;
                            if($lvalue->term_id == $term_id) {
                                $selected = $lvalue->term_id;
                                break;
                            }
                        }
                    }
                    $all_option .= fansub_field_get_option(array('value' => $lvalue->term_id, 'text' => $lvalue->name, 'selected' => $selected, 'attributes' => array('data-taxonomy' => $tkey)));
                }
                $all_option .= '</optgroup>';
            }
        }
        $args = array(
            'id' => $this->get_field_id('category'),
            'name' => $this->get_field_name('category'),
            'all_option' => $all_option,
            'value' => $category,
            'label' => __('Category:', 'fansub'),
            'placeholder' => __('Choose terms', 'fansub'),
            'multiple' => true,
            'class' => 'select-category'
        );
        if('category' != $by) {
            $args['hidden'] = true;
        }
        fansub_widget_field('fansub_field_select_chosen', $args);

        $args = array(
            'id_width' => $this->get_field_id('thumbnail_size_width'),
            'name_width' => $this->get_field_name('thumbnail_size_width'),
            'id_height' => $this->get_field_id('thumbnail_size_height'),
            'name_height' => $this->get_field_name('thumbnail_size_height'),
            'value' => $thumbnail_size,
            'label' => __('Thumbnail size:', 'fansub')
        );
        fansub_widget_field('fansub_field_size', $args);

        $lists = $this->args['full_width_posts'];
        $all_option = '';
        foreach($lists as $lkey => $lvalue) {
            $all_option .= fansub_field_get_option(array('value' => $lkey, 'text' => $lvalue, 'selected' => $full_width_post));
        }
        $args = array(
            'id' => $this->get_field_id('full_width_post'),
            'name' => $this->get_field_name('full_width_post'),
            'value' => $full_width_post,
            'all_option' => $all_option,
            'label' => __('Full width posts:', 'fansub'),
            'class' => 'full-width-post'
        );
        fansub_widget_field('fansub_field_select', $args);

        $args = array(
            'id' => $this->get_field_id('title_length'),
            'name' => $this->get_field_name('title_length'),
            'value' => $title_length,
            'label' => __('Title length:', 'fansub')
        );
        fansub_widget_field('fansub_field_input_number', $args);

        $slider = fansub_get_value_by_key($instance, 'slider', fansub_get_value_by_key($this->args, 'slider'));
        $args = array(
            'id' => $this->get_field_id('slider'),
            'name' => $this->get_field_name('slider'),
            'value' => $slider,
            'label' => __('Display post as slider?', 'fansub')
        );
        fansub_widget_field('fansub_field_input_checkbox', $args);

        $args = array(
            'id' => $this->get_field_id('hide_thumbnail'),
            'name' => $this->get_field_name('hide_thumbnail'),
            'value' => $hide_thumbnail,
            'label' => __('Hide post thumbnail?', 'fansub')
        );
        fansub_widget_field('fansub_field_input_checkbox', $args);

        $args = array(
            'id' => $this->get_field_id('widget_title_link_category'),
            'name' => $this->get_field_name('widget_title_link_category'),
            'value' => $widget_title_link_category,
            'label' => __('Link widget title with category?', 'fansub')
        );
        fansub_widget_field('fansub_field_input_checkbox', $args);

        $args = array(
            'id' => $this->get_field_id('category_as_widget_title'),
            'name' => $this->get_field_name('category_as_widget_title'),
            'value' => $category_as_widget_title,
            'label' => __('Display category name as widget title?', 'fansub')
        );
        fansub_widget_field('fansub_field_input_checkbox', $args);

        fansub_field_widget_after();
    }

    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags(fansub_get_value_by_key($new_instance, 'title'));
        $instance['post_type'] = fansub_get_value_by_key($new_instance, 'post_type', json_encode($this->args['post_type']));
        $instance['number'] = fansub_get_value_by_key($new_instance, 'number', $this->args['number']);
        $instance['by'] = fansub_get_value_by_key($new_instance, 'by', $this->args['by']);
        $instance['category'] = fansub_get_value_by_key($new_instance, 'category', json_encode($this->args['category']));
        $instance['full_width_post'] = fansub_get_value_by_key($new_instance, 'full_width_post', $this->args['full_width_post']);
        $width = fansub_get_value_by_key($new_instance, 'thumbnail_size_width', $this->args['thumbnail_size'][0]);
        $height = fansub_get_value_by_key($new_instance, 'thumbnail_size_height', $this->args['thumbnail_size'][1]);
        $instance['thumbnail_size'] = array($width, $height);
        $instance['slider'] = fansub_checkbox_post_data_value($new_instance, 'slider');
        $instance['title_length'] = fansub_get_value_by_key($new_instance, 'title_length', $this->args['title_length']);
        $instance['hide_thumbnail'] = fansub_checkbox_post_data_value($new_instance, 'hide_thumbnail');
        $instance['widget_title_link_category'] = fansub_checkbox_post_data_value($new_instance, 'widget_title_link_category');
        $instance['category_as_widget_title'] = fansub_checkbox_post_data_value($new_instance, 'category_as_widget_title');
        return $instance;
    }
}