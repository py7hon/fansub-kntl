<?php
if(!function_exists('add_filter')) exit;

function fansub_field_google_maps($args = array()) {
    fansub_field_sanitize_args($args);
    $lat_lng = fansub_get_default_lat_long();
    $id = fansub_get_value_by_key($args, 'id', 'maps_content');
    if(empty($id)) {
        $id = 'maps_content';
    }
    $address = fansub_get_value_by_key($args, 'address');
    $long = fansub_get_value_by_key($args, 'long');
    $lat = fansub_get_value_by_key($args, 'lat');
    $lang = fansub_get_language();
    $zoom = fansub_get_value_by_key($args, 'zoom', 15);
    $google_maps = fansub_get_value_by_key($args, 'google_maps');
    if(empty($long) || empty($lat)) {
        $lat = $lat_lng['lat'];
        $long = $lat_lng['lng'];
        $zoom = 5;
    }
    if(empty($google_maps)) {
        $google_maps = json_encode(array('lat' => $lat, 'lng' => $long));
    }
    $draggable = fansub_get_value_by_key($args, 'draggable', false);
    $marker_title = fansub_get_value_by_key($args, 'marker_title');
    $post_id = fansub_get_value_by_key($args, 'post_id');
    $scrollwheel = fansub_get_value_by_key($args, 'scrollwheel', false);
    if(empty($marker_title)) {
        if('vi' == $lang) {
            $marker_title = 'Di chuyển để tìm địa điểm!';
        } else {
            $marker_title = __('Drag to find address!', 'fansub');
        }
    }
    fansub_field_before($args);
    ?>
    <div id="<?php echo $id; ?>" class="fansub-field-maps" data-scrollwheel="<?php echo fansub_bool_to_int($scrollwheel); ?>" data-post-id="<?php echo $post_id; ?>" data-zoom="<?php echo $zoom; ?>" data-marker-title="<?php echo $marker_title; ?>" data-draggable="<?php echo fansub_bool_to_int($draggable); ?>" data-address="<?php echo $address; ?>" data-long="<?php echo $long; ?>" data-lat="<?php echo $lat; ?>" style="width: 100%; height: 350px; position: relative; background-color: rgb(229, 227, 223); overflow: hidden;"></div>
    <?php
    fansub_field_input_hidden(array('id' => 'google_maps', 'label' => '', 'field_callback' => 'fansub_field_input_hidden', 'value' => $google_maps));
    fansub_field_after($args);
}

function fansub_field_before(&$args = array()) {
    //$container_class = isset($args['container_class']) ? $args['container_class'] : '';
    $before = isset($args['before']) ? $args['before'] : '';
    echo $before;
    $label = isset($args['label']) ? $args['label'] : '';
    if(!empty($label)) {
        $class = isset($args['label_class']) ? $args['label_class'] : '';
        fansub_field_label(array('for' => $args['id'], 'text' => $label, 'class' => $class));
    }
    unset($args['label']);
}

function fansub_field_after($args = array()) {
    fansub_field_description($args);
    $after = isset($args['after']) ? $args['after'] : '';
    $label = isset($args['label']) ? $args['label'] : '';
    if(!empty($label)) {
        $class = isset($args['label_class']) ? $args['label_class'] : '';
        fansub_field_label(array('for' => $args['id'], 'text' => $args['label'], 'class' => $class));
    }
    echo $after;
}

function fansub_field_captcha($args = array()) {
    $lang = fansub_get_language();
    fansub_sanitize_field_args($args);
    $captcha = new FANSUB_Captcha();
    $id = isset($args['id']) ? $args['id'] : '';
    if(fansub_string_empty($id)) {
        $id = 'fansub_captcha';
    }
    $placeholder = isset($args['placeholder']) ? $args['placeholder'] : ('vi' == $lang) ? 'Nhập mã bảo mật' : __('Enter captcha code', 'fansub');
    $class = isset($args['class']) ? $args['class'] : '';
    $input_width = isset($args['input_width']) ? absint($args['input_width']) : 125;
    if(is_numeric($input_width) && '%' !== fansub_get_last_char($input_width)) {
        $input_width .= 'px';
    }
    $name = fansub_get_value_by_key($args, 'name', 'captcha');
    if(empty($name)) {
        $name = 'captcha';
        fansub_transmit_id_and_name($id, $name);
    }
    fansub_add_string_with_space_before($class, 'fansub-captcha-code');
    $args['id'] = $id;
    fansub_field_before($args);
    $image_url = $captcha->generate_image();
    ?>
    <input autocomplete="off" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" placeholder="<?php echo esc_attr($placeholder); ?>" class="<?php echo esc_attr($class); ?>" type="text" style="width: <?php echo $input_width; ?>;" required>
    <img class="fansub-captcha-image" src="<?php echo $image_url; ?>">
    <img class="fansub-captcha-reload" src="<?php echo FANSUB_URL . '/images/icon-refresh-captcha.png'; ?>">
    <?php
    fansub_field_after($args);
}

function fansub_field_label($args = array()) {
    fansub_sanitize_field_args($args);
    $text = isset($args['text']) ? $args['text'] : '';
    if(empty($text)) {
        return;
    }
    $html = new FANSUB_HTML('label');
    $atts = array(
        'for' => isset($args['for']) ? fansub_sanitize_id($args['for']) : '',
        'text' => isset($args['text']) ? $args['text'] : '',
        'class' => isset($args['class']) ? $args['class'] : ''
    );
    $html->set_attribute_array($atts);
    $attributes = isset($args['attributes']) ? $args['attributes'] : array();
    $html->set_attribute_array($attributes);
    $html->output();
}

function fansub_field_description($args = array()) {
    fansub_sanitize_field_args($args);
    $description = $args['description'];
    if(!empty($description)) {
        $id = $args['id'];
        if(!empty($id)) {
            $id .= '_description';
        }
        $p = new FANSUB_HTML('p');
        $p->set_text($description);
        $p->set_class('description');
        $p->set_attribute('id', $id);
        $p->output();
    }
}

function fansub_field_sanitize_args(&$args = array()) {
    return fansub_sanitize_field_args($args);
}

function fansub_field_sanitize_widget_args(&$args = array()) {
    $args['before'] = isset($args['before']) ? $args['before'] : '<p>';
    $args['after'] = isset($args['after']) ? $args['after'] : '</p>';
    $class = isset($args['class']) ? $args['class'] : '';
    fansub_add_string_with_space_before($class, 'widefat');
    $args['class'] = $class;
    $args['sanitize_id'] = false;
    return $args;
}

function fansub_field_sanitize_publish_box_args(&$args = array()) {
    $name = isset($args['name']) ? $args['name'] : '';
    $class = 'misc-pub-section';
    if(!empty($name)) {
        fansub_add_string_with_space_before($class, fansub_sanitize_file_name('misc-pub-'. $name));
    }
    $args['before'] = isset($args['before']) ? $args['before'] : '<div class="' . $class . '">';
    $args['after'] = isset($args['after']) ? $args['after'] : '</div>';
    return $args;
}

function fansub_field_color_picker($args = array()) {
    fansub_sanitize_field_args($args);
    //$value = fansub_get_value_by_key($args, 'value');
    $class = fansub_get_value_by_key($args, 'class');
    fansub_add_string_with_space_before($class, 'fansub-color-picker');
    $args['class'] = $class;
    $atts = fansub_get_value_by_key($args, 'attributes');
    $atts['autocomplete'] = 'off';
    $args['attributes'] = $atts;
    fansub_field_input($args);
}

function fansub_field_datetime_picker($args = array()) {
    fansub_sanitize_field_args($args);
    $class = fansub_get_value_by_key($args, 'class');
    fansub_add_string_with_space_before($class, 'fansub-datetime-picker');
    $min_date = fansub_get_value_by_key($args, 'min_date');
    $max_date = fansub_get_value_by_key($args, 'max_date');
    $date_format = fansub_get_value_by_key($args, 'date_format', fansub_get_date_format());
    $value = fansub_get_value_by_key($args, 'value');
    $args['class'] = $class;
    $atts = fansub_get_value_by_key($args, 'attributes');
    $atts['autocomplete'] = 'off';
    $atts['data-min-date'] = $min_date;
    $atts['data-max-date'] = $max_date;
    $atts['data-date-format'] = fansub_convert_datetime_format_to_jquery($date_format);
    $args['attributes'] = $atts;
    $args['type'] = 'text';
    if(is_numeric($value) && $value > 0) {
        $value = date($date_format, $value);
    }
    if(is_numeric($value) && 0 == $value) {
        $value = '';
    }
    $args['value'] = $value;
    fansub_field_input($args);
}

function fansub_field_sortable($args = array()) {
    fansub_sanitize_field_args($args);
    $value = isset($args['value']) ? $args['value'] : '';
    $connect = isset($args['connect']) ? $args['connect'] : false;
    $class = isset($args['class']) ? $args['class'] : '';
    fansub_add_string_with_space_before($class, 'sortable fansub-sortable');
    if($connect) {
        fansub_add_string_with_space_before($class, 'connected-list');
    }
    $items = isset($args['items']) ? $args['items'] : '';
    fansub_field_before($args);
    $ul = new FANSUB_HTML('ul');
    $ul->set_class($class);
    $ul->set_text($items);
    $ul->output();
    if($connect) {
        fansub_add_string_with_space_before($class, 'connected-result');
        $active_items = isset($args['active_items']) ? $args['active_items'] : '';
        $ul_connect = new FANSUB_HTML('ul');
        $ul_connect->set_class($class);
        $ul_connect->set_text($active_items);
        $ul_connect->output();
    }
    $input_args = array(
        'type' => 'hidden',
        'name' => isset($args['name']) ? $args['name'] : '',
        'value' => $value,
        'class' => 'input-result',
        'attributes' => array(
            'autocomplete' => 'off'
        )
    );
    fansub_field_input($input_args);
    fansub_field_after($args);
}

function fansub_field_sortable_term($args = array()) {
    $value = isset($args['value']) ? $args['value'] : '';
    $items = isset($args['items']) ? $args['items'] : '';
    $connect = fansub_get_value_by_key($args, 'connect', false);
    $active_terms = fansub_json_string_to_array($value);
    $save_ids = array();
    if($connect) {
        foreach($active_terms as $data) {
            $id = isset($data['id']) ? $data['id'] : '';
            if(is_numeric($id)) {
                $save_ids[] = $id;
            }
        }
    }
    if(empty($items)) {
        $taxonomy = isset($args['taxonomy']) ? $args['taxonomy'] : 'category';
        $term_args = isset($args['term_args']) ? $args['term_args'] : array();
        $defaults = array(
            'hide_empty' => false,
            'exclude' => $save_ids
        );
        $term_args = wp_parse_args($term_args, $defaults);
        $terms = fansub_get_terms($taxonomy, $term_args);
        if(!$connect) {
            $results = $active_terms;
            if(fansub_array_has_value($results)) {
                $new_lists = array();
                foreach($results as $data) {
                    $id = isset($data['id']) ? $data['id'] : '';
                    $dtax = fansub_get_value_by_key($data, 'taxonomy');
                    if(!fansub_id_number_valid($id) || empty($dtax)) {
                        continue;
                    }
                    $item = get_term($id, $dtax);
                    if(fansub_object_valid($item)) {
                        foreach($terms as $key => $aitem) {
                            if($aitem->term_id == $item->term_id) {
                                $new_lists[] = $item;
                                unset($terms[$key]);
                                break;
                            }
                        }
                    }
                }
                $terms = $new_lists + $terms;
            }
        }
        foreach($terms as $term) {
            $li = new FANSUB_HTML('li');
            $li->set_class('ui-state-default');
            $attributes = array(
                'data-taxonomy' => $term->taxonomy,
                'data-id' => $term->term_id
            );
            $li->set_attribute_array($attributes);
            $li->set_text($term->name);
            $items .= $li->build();
        }
    }
    if($connect) {
        $active_items = isset($args['active_items']) ? $args['active_items'] : '';
        if(empty($active_items)) {
            foreach($active_terms as $data) {
                $id = isset($data['id']) ? $data['id'] : '';
                $id = absint($id);
                $taxonomy = isset($data['taxonomy']) ? $data['taxonomy'] : '';
                $term = get_term_by('id', $id, $taxonomy);
                if(fansub_object_valid($term) && is_a($term, 'WP_Term') && term_exists($term->term_id, $taxonomy)) {
                    $li = new FANSUB_HTML('li');
                    $li->set_class('ui-state-default');
                    $attributes = array(
                        'data-taxonomy' => $term->taxonomy,
                        'data-id' => $term->term_id
                    );
                    $li->set_attribute_array($attributes);
                    $li->set_text($term->name);
                    $active_items .= $li->build();
                }
            }
        }
        $args['active_items'] = $active_items;
    }
    $args['items'] = $items;
    $class = isset($args['class']) ? $args['class'] : '';
    fansub_add_string_with_space_before($class, 'term-sortable');
    $args['class'] = $class;
    fansub_field_sortable($args);
}

function fansub_field_sortable_post_type($args = array()) {
    $value = isset($args['value']) ? $args['value'] : '';
    $items = isset($args['items']) ? $args['items'] : '';
    if(empty($items)) {
        $active_items = fansub_json_string_to_array($value);
        $default_args = array(
            'public' => true
        );
        $post_type_args = isset($args['post_type_args']) ? $args['post_type_args'] : array();
        $post_type_args = wp_parse_args($post_type_args, $default_args);
        $lists = get_post_types($post_type_args, 'objects');
        unset($lists['nav_menu_item']);
        unset($lists['attachment']);
        unset($lists['revision']);
        foreach($active_items as $aitem) {
            unset($lists[$aitem['id']]);
        }
        foreach($lists as $key => $list_item) {
            $li = new FANSUB_HTML('li');
            $li->set_class('ui-state-default');
            $attributes = array(
                'data-id' => $key
            );
            $li->set_attribute_array($attributes);
            $li->set_text($list_item->labels->singular_name);
            $items .= $li->build();
        }
    }
    $active_items = isset($args['active_items']) ? $args['active_items'] : '';
    if(empty($active_items)) {
        $lists = fansub_json_string_to_array($value);
        foreach($lists as $data) {
            $id = isset($data['id']) ? $data['id'] : '';
            $post_type = get_post_type_object($id);
            if(fansub_object_valid($post_type) && isset($post_type->name) && post_type_exists($post_type->name)) {
                $li = new FANSUB_HTML('li');
                $li->set_class('ui-state-default');
                $attributes = array(
                    'data-id' => $id
                );
                $li->set_attribute_array($attributes);
                $li->set_text($post_type->labels->singular_name);
                $active_items .= $li->build();
            }
        }
    }
    $args['items'] = $items;
    $args['active_items'] = $active_items;
    $class = isset($args['class']) ? $args['class'] : '';
    fansub_add_string_with_space_before($class, 'post-type-sortable');
    $args['class'] = $class;
    fansub_field_sortable($args);
}

function fansub_field_sortable_taxonomy($args = array()) {
    $value = isset($args['value']) ? $args['value'] : '';
    $items = isset($args['items']) ? $args['items'] : '';
    $connect = fansub_get_value_by_key($args, 'connect', false);
    if(empty($items)) {
        $active_items = fansub_json_string_to_array($value);
        $default_args = array(
            'public' => true
        );
        $taxonomy_args = isset($args['taxonomy_args']) ? $args['taxonomy_args'] : array();
        $taxonomy_args = wp_parse_args($taxonomy_args, $default_args);
        $lists = get_taxonomies($taxonomy_args, 'objects');
        fansub_exclude_special_taxonomies($lists);
        if((bool)$connect) {
            foreach($active_items as $aitem) {
                if(fansub_array_has_value($aitem)) {
                    unset($lists[$aitem['id']]);
                }
            }
        } else {
            $results = fansub_json_string_to_array($value);
            if(fansub_array_has_value($results)) {
                $new_lists = array();
                foreach($results as $data) {
                    $id = isset($data['id']) ? $data['id'] : '';
                    $item = get_taxonomy($id);
                    if(fansub_object_valid($item) && taxonomy_exists($item)) {
                        foreach($lists as $key => $taxonomy) {
                            if($taxonomy->name == $item->name) {
                                $new_lists[] = $item;
                                unset($lists[$key]);
                                break;
                            }
                        }
                    }
                }
                $lists = $new_lists + $lists;
            }
        }
        foreach($lists as $key => $list_item) {
            $li = new FANSUB_HTML('li');
            $li->set_class('ui-state-default');
            $attributes = array(
                'data-id' => $key
            );
            $li->set_attribute_array($attributes);
            $li->set_text($list_item->labels->singular_name);
            $items .= $li->build();
        }
    }
    if((bool)$connect) {
        $active_items = isset($args['active_items']) ? $args['active_items'] : '';
        if(empty($active_items)) {
            $lists = fansub_json_string_to_array($value);
            foreach($lists as $data) {
                $id = isset($data['id']) ? $data['id'] : '';
                $item = get_taxonomy($id);
                if(fansub_object_valid($item) && isset($item->name) && taxonomy_exists($item->name)) {
                    $li = new FANSUB_HTML('li');
                    $li->set_class('ui-state-default');
                    $attributes = array(
                        'data-id' => $id
                    );
                    $li->set_attribute_array($attributes);
                    $li->set_text($item->labels->singular_name);
                    $active_items .= $li->build();
                }
            }
        }
        $args['active_items'] = $active_items;
    }
    $args['items'] = $items;
    $class = isset($args['class']) ? $args['class'] : '';
    fansub_add_string_with_space_before($class, 'taxonomy-sortable');
    $args['class'] = $class;
    fansub_field_sortable($args);
}

function fansub_field_recaptcha($args = array()) {
    $site_key = isset($args['site_key']) ? $args['site_key'] : '';
    if(empty($site_key)) {
        return;
    }
    $div = new FANSUB_HTML('div');
    $div->set_class('g-recaptcha');
    $div->set_attribute('data-sitekey', $site_key);
    if(isset($args['id'])) {
        $div->set_attribute('id', $args['id']);
    }
    $div->output();
    ?>
    <noscript>
        <div style="width: 302px; height: 425px;">
            <div style="width: 302px; height: 425px; position: relative;">
                <div style="width: 302px; height: 425px; position: absolute;">
                    <iframe src="https://www.google.com/recaptcha/api/fallback?k=<?php echo $site_key; ?>&hl=<?php echo fansub_get_recaptcha_language(); ?>" frameborder="0" scrolling="no" style="width: 302px; height:425px; border-style: none;"></iframe>
                </div>
                <div style="width: 300px; height: 60px; bottom: 12px; left: 25px; margin: 0; padding: 0; right: 25px; background: #f9f9f9; border: 1px solid #c1c1c1; border-radius: 3px;">
                    <label for="g-recaptcha-response" style="display: none"></label>
                    <textarea id="g-recaptcha-response" name="g-recaptcha-response" class="g-recaptcha-response" style="width: 250px; height: 40px; border: 1px solid #c1c1c1; margin: 10px 25px; padding: 0; resize: none;"></textarea>
                </div>
            </div>
        </div>
    </noscript>
    <?php
}

function fansub_field_headline($args = array()) {
    $args = fansub_field_sanitize_args($args);
    $tag = isset($args['tag']) ? $args['tag'] : 'h2';
    $text = isset($args['text']) ? $args['text'] : '';
    $headline = new FANSUB_HTML($tag);
    $headline->set_text($text);
    $headline->output();
}

function fansub_field_fieldset($args = array()) {
    $args = fansub_field_sanitize_args($args);
    $label = isset($args['label']) ? $args['label'] : '';
    $callback = fansub_sanitize_callback($args);
    $container_class = isset($args['container_class']) ? $args['container_class'] : '';
    $func_args = fansub_sanitize_callback_args($args);
    if(!is_array($callback)) {
        fansub_add_string_with_space_before($container_class, fansub_sanitize_file_name($callback));
    } else {
        $cb_class = isset($callback[1]) ? $callback[1] : '';
        if(!empty($cb_class)) {
            fansub_add_string_with_space_before($container_class, fansub_sanitize_file_name($cb_class));
        }
    }
    fansub_add_string_with_space_before($container_class, 'fansub-fieldset');
    unset($args['label']);
    fansub_field_before($args);
    ?>
    <fieldset class="<?php echo $container_class; ?>">
        <legend><?php echo $label; ?></legend>
        <?php call_user_func($callback, $func_args); ?>
    </fieldset>
    <?php
    fansub_field_after($args);
}

function fansub_field_input_size($args = array()) {
    fansub_field_size($args);
}

function fansub_field_size($args = array()) {
    $args = fansub_field_sanitize_args($args);
    $field_class = $args['class'];
    $id_width = isset($args['id_width']) ? $args['id_width'] : '';
    $id_height = isset($args['id_height']) ? $args['id_height'] : '';
    $name_width = isset($args['name_width']) ? $args['name_width'] : '';
    $name_height = isset($args['name_height']) ? $args['name_height'] : '';
    $value = isset($args['value']) ? (array)$args['value'] : array(0, 0);
    if(!fansub_array_has_value($value)) {
        $value = array(0, 0);
    }
    fansub_add_string_with_space_before($field_class, 'fansub-number image-size');
    $sep = isset($args['sep']) ? $args['sep'] : '<span>x</span>';
    $id = explode('_', $id_width);
    $id = fansub_sanitize_array($id);
    //$last = array_pop($id);
    $args['id'] = implode('_', $id);
    $args['label_class'] = 'label-input-size';
    fansub_field_before($args);
    $input_args = array(
        'id' => $id_width,
        'field_class' => $field_class,
        'name' => $name_width,
        'autocomplete' => false,
        'value' => isset($value[0]) ? $value[0] : 0,
        'only' => true
    );
    fansub_field_input_number($input_args);
    echo $sep;
    $input_args['id'] = $id_height;
    $input_args['name'] = $name_height;
    $input_args['value'] = isset($value[1]) ? $value[1] : (isset($value[0]) ? $value[0] : 0);
    fansub_field_input_number($input_args);
    fansub_field_after($args);
}

function fansub_field_textarea($args = array()) {
    $tmp_class = isset($args['class']) ? $args['class'] : 'widefat';
    fansub_sanitize_field_args($args);
    $id = isset($args['id']) ? $args['id'] : '';
    $name = isset($args['name']) ? $args['name'] : '';
    $value = isset($args['value']) ? $args['value'] : '';
    //$description = isset($args['description']) ? $args['description'] : '';
    $class = isset($args['class']) ? $args['class'] : '';
    fansub_add_string_with_space_before($class, $tmp_class);
    //$container_class = isset($args['container_class']) ? $args['container_class'] : '';
    $value = trim($value);
    if(empty($value)) {
        $value = fansub_get_value_by_key($args, 'default');
    }
    $autocomplete = isset($args['autocomplete']) ? $args['autocomplete'] : false;
    $row = isset($args['row']) ? $args['row'] : 5;
    if(isset($args['textarea_rows'])) {
        $row = $args['textarea_rows'];
    }
    $args['label_class'] = 'vertical-align-top';
    fansub_field_before($args);
    $html = new FANSUB_HTML('textarea');
    $atts = array(
        'id' => $id,
        'name' => $name,
        'text' => $value,
        'class' => $class,
        'rows' => $row
    );
    if($autocomplete) {
        $atts['autocomplete'] = 'off';
    }
    $html->set_attribute_array($atts);
    $html->output();
    fansub_field_after($args);
}

function fansub_field_input($args) {
    fansub_sanitize_field_args($args);
    $name = isset($args['name']) ? $args['name'] : '';
    if(empty($name)) {
        _e('Please setup name for this field.', 'fansub');
        return;
    }
    $right_label = false;
    $checked = false;
    $value = isset($args['value']) ? $args['value'] : '';
    $readonly = (bool)fansub_get_value_by_key($args, 'readonly', false);
    $attributes = isset($args['attributes']) ? $args['attributes'] : array();
    $placeholder = fansub_get_value_by_key($args, 'placeholder');
    $required = (bool)fansub_get_value_by_key($args, 'required', false);
    if(!empty($placeholder)) {
        $attributes['placeholder'] = $placeholder;
    }
    if($required) {
        $attributes['required'] = 'true';
    }
    if(fansub_string_empty($value)) {
        $value = isset($args['default']) ? $args['default'] : '';
    }
    if(is_array($value)) {
        $value = json_encode($value);
    }
    $sanitize_id = isset($args['sanitize_id']) ? $args['sanitize_id'] : true;
    $id = isset($args['id']) ? $args['id'] : '';
    if(!empty($id) && $sanitize_id) {
        $id = fansub_sanitize_id($id);
    }
    if(empty($id)) {
        $id = fansub_sanitize_id($name);
    }
    $option_value = isset($args['option_value']) ? $args['option_value'] : '';
    $type = isset($args['type']) ? $args['type'] : 'text';
    $class = isset($args['class']) ? $args['class'] : '';
    if('checkbox' == $type) {
        fansub_add_string_with_space_before($class, 'checkbox');
        $option_value = 1;
    }
    if('radio' == $type) {
        fansub_add_string_with_space_before($class, 'radio');
    }
    if('radio' == $type || 'checkbox' == $type) {
        if($value == $option_value) {
            $checked = true;
        }
        $right_label = true;
        $attributes['autocomplete'] = 'off';
    }
    $widefat = $args['widefat'];
    if($widefat && !$right_label && 'button' != $type) {
        fansub_add_string_with_space_before($class, 'widefat');
    }
    $regular_text = isset($args['regular_text']) ? (bool)$args['regular_text'] : ($GLOBALS['pagenow'] == 'widgets.php' || defined('DOING_AJAX')) ? false : true;
    if($regular_text && !$right_label && 'button' != $type) {
        fansub_add_string_with_space_before($class, 'regular-text');
    }
    $atts = array(
        'type' => $type,
        'class' => $class,
        'value' => $value
    );
    if($checked) {
        $atts['checked'] = 'checked';
    }
    $description = isset($args['description']) ? $args['description'] : '';
    if(!empty($description)) {
        $atts['aria-describedby'] = $id . '_description';
    }
    $atts['id'] = $id;
    $atts['name'] = $name;
    $label = isset($args['label']) ? $args['label'] : '';
    if($right_label) {
        unset($args['label']);
    }
    fansub_field_before($args);
    $input = new FANSUB_HTML('input');
    if('radio' == $type || 'checkbox' == $type) {
        if(!empty($option_value) || is_numeric($option_value)) {
            $atts['value'] = $option_value;
        }
    }
    if($readonly) {
        $attributes['readonly'] = 'readonly';
    }
    $input->set_attribute_array($attributes);
    $input->set_attribute_array($atts);
    $input->output();
    if($right_label) {
        $args['label_class'] = 'full-width';
        $args['label'] = $label;
    }
    fansub_field_after($args);
}

function fansub_field_button($args = array()) {
    $class = fansub_get_value_by_key($args, 'class');
    fansub_add_string_with_space_before($class, 'button');
    $args['class'] = $class;
    $args['type'] = 'button';
    $args['regular_text'] = false;
    fansub_field_input($args);
}

function fansub_field_input_file($args = array()) {
    $args['type'] = 'file';
    $image = (bool)fansub_get_value_by_key($args, 'image');
    $max = fansub_get_value_by_key($args, 'max');
    $attributes = fansub_get_value_by_key($args, 'attributes');
    $attributes = fansub_sanitize_array($attributes);
    $multiple = fansub_get_value_by_key($args, 'multiple');
    $class = fansub_get_value_by_key($args, 'class');
    $name = fansub_get_value_by_key($args, 'name');
    if(empty($name)) {
        $name = 'file_names';
    }
    fansub_add_string_with_space_before($class, 'fansub-field-upload');
    if($image) {
        $attributes['accept'] = 'image/*';
    }
    if(fansub_id_number_valid($max)) {
        $attributes['data-max'] = $max;
        if(1 < $max) {
            $multiple = true;
        }
    }
    if($multiple) {
        $attributes['multiple'] = 'multiple';
        if(false === strpos($name, '[]')) {
            $name .= '[]';
        }
        fansub_add_string_with_space_before($class, 'multiple-file');
    } else {
        fansub_add_string_with_space_before($class, 'single-file');
    }
    $args['attributes'] = $attributes;
    $args['class'] = $class;
    $after = fansub_get_value_by_key($args, 'after');
    $after = '<div class="image-preview"></div>' . $after;
    $args['after'] = $after;
    $args['name'] = $name;
    fansub_field_input($args);
}

function fansub_field_input_text($args = array()) {
    fansub_field_input($args);
}

function fansub_field_input_number($args = array()) {
    $args['type'] = 'number';
    $atts = array();
    if(isset($args['min'])) {
        $atts['min'] = $args['min'];
    }
    if(isset($args['max'])) {
        $atts['max'] = $args['max'];
    }
    if(fansub_array_has_value($atts)) {
        $args['attributes'] = $atts;
    }
    fansub_field_input($args);
}

function fansub_field_input_hidden($args = array()) {
    $args['type'] = 'hidden';
    fansub_field_input($args);
}

function fansub_field_input_url($args = array()) {
    $args['type'] = 'url';
    fansub_field_input($args);
}

function fansub_field_input_right_label($type, $args = array()) {
    $options = isset($args['options']) ? $args['options'] : array();
    $value = isset($args['value']) ? $args['value'] : '';
    $label = isset($args['label']) ? $args['label'] : '';
    $id = fansub_get_value_by_key($args, 'id');
    $name = fansub_get_value_by_key($args, 'name');
    fansub_transmit_id_and_name($id, $name);
    if(!fansub_array_has_value($options)) {
        $option_item = array(
            'label' => $label,
            'value' => $value,
            'default' => fansub_get_value_by_key($args, 'default'),
            'id' => $id,
            'name' => $name
        );
        $options[] = $option_item;
    }
    $count = 0;
    foreach($options as $option) {
        $value = isset($option['value']) ? $option['value'] : $value;
        $option['type'] = $type;
        $option['before'] = isset($args['before']) ? $args['before'] : '<p>';
        $option['after'] = isset($args['after']) ? $args['after'] : '</p>';
        $option['name'] = isset($option['name']) ? $option['name'] : $name;
        $option['value'] = $value;
        if(fansub_string_empty($value) && 0 == $count && 'radio' == $type) {
            $option['attributes']['checked'] = 'checked';
        }
        fansub_field_input($option);
        $count++;
    }
}

function fansub_field_input_radio($args = array()) {
    fansub_field_input_right_label('radio', $args);
}

function fansub_field_input_checkbox($args = array()) {
    fansub_field_input_right_label('checkbox', $args);
}

function fansub_field_publish_box($callback, $args = array()) {
    $args['before'] = '<div class="misc-pub-section misc-pub-visibility">';
    $args['after'] = '</div>';
    call_user_func($callback, $args);
}

function fansub_field_media_upload($args = array()) {
    fansub_field_sanitize_args($args);
    $id = isset($args['id']) ? $args['id'] : '';
    $name = isset($args['name']) ? $args['name'] : '';
    $value = isset($args['value']) ? $args['value'] : '';
    $value = fansub_sanitize_media_value($value);
    $media_url = $value['url'];
    fansub_field_before($args);
    $media_preview = new FANSUB_HTML('span');
    $media_preview->set_class('media-preview');
    if(!empty($media_url)) {
        $image = new FANSUB_HTML('img');
        $image->set_attribute('src', $media_url);
        if(isset($value['is_image']) && !(bool)$value['is_image']) {
            $type_icon = fansub_get_value_by_key($value, 'type_icon');
            if(!empty($type_icon)) {
                $image->set_attribute('src', $type_icon);
            }
        }
        $media_preview->set_text($image->build());
    }
    $media_preview->output();
    if(empty($id)) {
        $id = fansub_sanitize_id($name);
    }
    $url_args = array(
        'id' => $id . '_url',
        'name' => $name . '[url]',
        'class' => 'media-url',
        'type' => 'url',
        'value' => $media_url,
        'attributes' => array(
            'autocomplete' => 'off'
        )
    );
    fansub_field_input($url_args);
    $btn_insert_args = array(
        'data_editor' => $id . '_url'
    );
    if(!empty($media_url)) {
        $btn_insert_args['class'] = 'hidden';
    }
    fansub_field_insert_media_button($btn_insert_args);
    $btn_remove_args = array();
    if(empty($media_url)) {
        $btn_remove_args['class'] = 'hidden';
    }
    fansub_field_remove_button($btn_remove_args);
    $id_args = array(
        'id' => $id . '_id',
        'name' => $name . '[id]',
        'class' => 'media-id',
        'value' => $value['id']
    );
    fansub_field_input_hidden($id_args);
    fansub_field_after($args);
}

function fansub_field_media_upload_simple($args = array()) {
    fansub_field_sanitize_args($args);
    $id = fansub_get_value_by_key($args, 'id');
    $name = fansub_get_value_by_key($args, 'name');
    if(empty($name)) {
        _e('Please setup name for this field.', 'fansub');
        return;
    }
    $value = fansub_get_value_by_key($args, 'value');
    $value = fansub_sanitize_media_value($value);
    $media_url = $value['url'];
    $btn_insert_class = 'btn-insert-media simple';
    $btn_remove_class = 'btn-remove simple';
    $img = '';
    if(!empty($value['url'])) {
        fansub_add_string_with_space_before($btn_insert_class, 'hidden');
        $image = new FANSUB_HTML('img');
        $image->set_attribute('src', $media_url);
        if(isset($value['is_image']) && !(bool)$value['is_image']) {
            $type_icon = fansub_get_value_by_key($value, 'type_icon');
            if(!empty($type_icon)) {
                $image->set_attribute('src', $type_icon);
            }
        }
        $img = $image->build();
    } else {
        fansub_add_string_with_space_before($btn_remove_class, 'hidden');
    }
    ?>
    <p class="hide-if-no-js">
        <span class="media-preview"><?php echo $img; ?></span>
        <a class="<?php echo $btn_insert_class; ?>" href="#" title="<?php _e('Set image', 'fansub'); ?>"><?php _e('Set image', 'fansub'); ?></a>
        <a class="<?php echo $btn_remove_class; ?>" href="#" title="<?php _e('Remove image', 'fansub'); ?>"><?php _e('Remove image', 'fansub'); ?></a>
        <input id="<?php echo $id; ?>_url" type="hidden" value="<?php echo $value['url']; ?>" name="<?php echo $name; ?>[url]" class="media-url">
        <input id="<?php echo $id; ?>_id" type="hidden" value="<?php echo $value['id']; ?>" name="<?php echo $name; ?>[id]" class="media-id">
    </p>
    <?php
}

function fansub_field_insert_media_button($args = array()) {
    //$data_editor = isset($args['data_editor']) ? $args['data_editor'] : 'content';
    //$id = isset($args['id']) ? $args['id'] : $data_editor . '_insert_media_button';
    $class = isset($args['class']) ? $args['class'] : '';
    fansub_add_string_with_space_before($class, 'button btn-add-media btn btn-insert-media');
    $button = new FANSUB_HTML('button');
    $button->set_class($class);
    $button->set_text(__('Add media', 'fansub'));
    $button->output();
}

function fansub_field_remove_button($args = array()) {
    $class = isset($args['class']) ? $args['class'] : '';
    fansub_add_string_with_space_before($class, 'btn button btn-remove');
    $button = new FANSUB_HTML('button');
    $button->set_class($class);
    $button->set_text(__('Remove', 'fansub'));
    $button->output();
}

function fansub_field_rich_editor($args = array()) {
    fansub_field_editor($args);
}

function fansub_field_editor($args = array()) {
    fansub_sanitize_field_args($args);
    $value = isset($args['value']) ? $args['value'] : '';
    $id = isset($args['id']) ? $args['id'] : '';
    $name = isset($args['name']) ? $args['name'] : '';
    if(empty($id)) {
        $id = fansub_sanitize_id($name);
    }
    $textarea_rows = isset($args['textarea_rows']) ? $args['textarea_rows'] : fansub_get_value_by_key($args, 'rows', 5);
    $class = isset($args['class']) ? $args['class'] : '';
    fansub_field_before($args);
    $args['textarea_name'] = $name;
    $args['editor_class'] = $class;
    $args['textarea_rows'] = $textarea_rows;
    $teeny = fansub_get_value_by_key($args, 'teeny', false);
    if($teeny) {
        $args['tinymce'] = false;
        $args['wpautop'] = false;
    }
    $toolbar = fansub_get_value_by_key($args, 'toolbar', true);
    if(!$toolbar) {
        $args['quicktags'] = false;
    }
    $prevent_id = array(
        'gallery'
    );
    if(in_array($prevent_id, $prevent_id)) {
        $id = 'fansub_' . $id;
    }
    wp_editor($value, $id, $args);
    fansub_field_after($args);
}

function fansub_field_get_option($args = array()) {
    $value = isset($args['value']) ? $args['value'] : '';
    $text = isset($args['text']) ? $args['text'] : '';
    $selected = isset($args['selected']) ? $args['selected'] : '';
    $option = new FANSUB_HTML('option');
    $option->set_attribute('value', $value);
    $option->set_text($text);
    $attributes = isset($args['attributes']) ? $args['attributes'] : array();
    foreach($attributes as $data_name => $att_value) {
        $option->set_attribute($data_name, $att_value);
    }
    if($selected == $value) {
        $option->set_attribute('selected', 'selected');
    }
    return $option->build();
}

function fansub_field_option($args = array()) {
    echo fansub_field_get_option($args);
}

function fansub_field_select_chosen($args = array()) {
    fansub_field_sanitize_args($args);
    $class = isset($args['class']) ? $args['class'] : '';
    $controller_class = isset($args['controller_class']) ? $args['controller_class'] : 'chosen-select';
    $controller_class = apply_filters('fansub_chosen_select_controller_class', $controller_class);
    fansub_add_string_with_space_before($class, $controller_class);
    fansub_add_string_with_space_before($class, 'chooseable');
    $args['field_class'] = $class;
    $multiple = isset($args['multiple']) ? $args['multiple'] : false;
    $attributes = isset($args['attributes']) ? $args['attributes'] : array();
    if((bool)$multiple) {
        $attributes['multiple'] = 'multiple';
    }
    $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
    if(!empty($placeholder)) {
        $attributes['data-placeholder'] = $placeholder;
    }
    $args['attributes'] = $attributes;
    $before = fansub_get_value_by_key($args, 'before', '<div class="fansub-chosen-field fansub-field-group">');
    $after = fansub_get_value_by_key($args, 'after', '</div>');
    $args['before'] = $before;
    $args['after'] = $after;
    if($multiple) {
        $name = isset($args['name']) ? $args['name'] : '';
        $id = isset($args['id']) ? $args['id'] : '';
        $args['name'] = $name . '_chosen';
        $args['id'] = $id . '_chosen';
        $after = isset($args['after']) ? $args['after'] : '';
        $value = isset($args['value']) ? $args['value'] : '';
        if(is_array($value)) {
            $value = json_encode($value);
        }
        $input_result = '<input type="hidden" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" class="chosen-result" value="' . esc_attr($value) . '" autocomplete="off">';
        $args['after'] = $input_result . $after;
        fansub_field_select($args);
    } else {
        fansub_field_select($args);
    }
}

function fansub_field_select($args = array()) {
    $args = fansub_field_sanitize_args($args);
    $id = isset($args['id']) ? $args['id'] : '';
    $name = isset($args['name']) ? $args['name'] : '';
    $list_options = isset($args['list_options']) ? $args['list_options'] : array();
    $options = isset($args['options']) ? $args['options'] : array();
    $load_item = isset($args['load_item']) ? $args['load_item'] : true;
    $value = isset($args['value']) ? $args['value'] : '';
    $field_class = isset($args['field_class']) ? $args['field_class'] : 'widefat';
    if(!is_array($options) || count($options) < 1) {
        $options = $list_options;
    }
    $all_option = isset($args['all_option']) ? $args['all_option'] : '';
    $autocomplete = isset($args['autocomplete']) ? $args['autocomplete'] : false;
    if(!$autocomplete) {
        $autocomplete = 'off';
    }
    $select_option = isset($args['default_option']) ? $args['default_option'] : '';
    if($load_item && empty($all_option)) {
        foreach($options as $key => $text) {
            $select_option .= fansub_field_get_option(array('value' => $key, 'text' => $text, 'selected' => $value));
        }
    } else {
        $select_option .= $all_option;
    }
    if(!$load_item) {
        $custom_options = isset($args['custom_options']) ? $args['custom_options'] : '';
        $select_option .= $custom_options;
    }
    fansub_field_before($args);
    $html = new FANSUB_HTML('select');
    $attributes = isset($args['attributes']) ? fansub_sanitize_array($args['attributes']) : array();
    $atts = array(
        'id' => fansub_sanitize_id($id),
        'name' => $name,
        'class' => $field_class,
        'autocomplete' => $autocomplete,
        'text' => $select_option
    );
    $html->set_attribute_array($atts);
    foreach($attributes as $key => $value) {
        $html->set_attribute($key, $value);
    }
    $html->output();
    fansub_field_after($args);
}

function fansub_field_select_language($args = array()) {
    fansub_field_sanitize_args($args);
    $class = isset($args['class']) ? $args['class'] : '';
    fansub_add_string_with_space_before($class, 'select-language');
    $args['field_class'] = $class;
    $select_none = isset($args['select_none']) ? $args['select_none'] : '<option value="">--' . __('Choose language', 'fansub') . '--</option>';
    $lists = fansub_supported_languages();
    $value = isset($args['value']) ? $args['value'] : '';
    $all_option = $select_none;
    foreach($lists as $key => $data) {
        $option = fansub_field_get_option(array('value' => $key, 'text' => $data, 'selected' => $value));
        $all_option .= $option;
    }
    $args['all_option'] = $all_option;
    fansub_field_select($args);
}

function fansub_field_select_country($args = array()) {
    fansub_field_sanitize_args($args);
    $class = isset($args['class']) ? $args['class'] : '';
    fansub_add_string_with_space_before($class, 'select-country');
    $args['field_class'] = $class;
    $select_none = isset($args['select_none']) ? $args['select_none'] : '<option value="">--' . __('Choose country', 'fansub') . '--</option>';
    $countries = fansub_get_countries();
    $value = isset($args['value']) ? $args['value'] : '';
    $all_option = $select_none;
    foreach($countries as $code => $country) {
        $option = fansub_field_get_option(array('value' => $code, 'text' => $country['name'], 'selected' => $value));
        $all_option .= $option;
    }
    $args['all_option'] = $all_option;
    fansub_field_select($args);
}

function fansub_field_select_page($args = array()) {
    $query = fansub_query_all('page');
    $choose_text = __('Choose page', 'fansub');
    $choose_text = apply_filters('fansub_theme_select_page_text', $choose_text);
    $all_option = '<option value="0">-- ' . $choose_text . ' --</option>';
    $value = isset($args['value']) ? $args['value'] : '';
    while($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        $all_option .= '<option value="' . esc_attr($post_id) . '" ' . selected($value, $post_id, false) . '>' . get_the_title() . '</option>';
    }
    wp_reset_postdata();
    $args['all_option'] = $all_option;
    fansub_field_select($args);
}

function fansub_field_select_sidebar($args = array()) {
    $sidebars = fansub_get_sidebars();
    $choose_text = __('Choose sidebar', 'fansub');
    $choose_text = apply_filters('fansub_theme_select_sidebar_text', $choose_text);
    $all_option = '<option value="0">-- ' . $choose_text . ' --</option>';
    $value = isset($args['value']) ? $args['value'] : '';
    foreach($sidebars as $key => $sidebar) {
        $sidebar_name = fansub_get_value_by_key($sidebar, 'name', $key);
        $all_option .= '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . $sidebar_name . '</option>';
    }
    $args['all_option'] = $all_option;
    fansub_field_select($args);
}

function fansub_field_select_theme($args = array()) {
    $themes = wp_get_themes();
    $choose_text = __('Choose theme', 'fansub');
    $choose_text = apply_filters('fansub_theme_select_theme_text', $choose_text);
    $all_option = '<option value="0">-- ' . $choose_text . ' --</option>';
    $value = isset($args['value']) ? $args['value'] : '';
    if(fansub_array_has_value($themes)) {
        foreach($themes as $name => $data) {
            $all_option .= '<option value="' . esc_attr($name) . '" ' . selected($value, $name, false) . '>' . $data->get('Name') . '</option>';
        }
    }
    $args['all_option'] = $all_option;
    fansub_field_select($args);
}

function fansub_field_select_plugin($args = array()) {
    $lists = fansub_get_my_plugins();
    $choose_text = __('Choose plugin', 'fansub');
    $choose_text = apply_filters('fansub_theme_select_plugin_text', $choose_text);
    $all_option = '<option value="0">-- ' . $choose_text . ' --</option>';
    $value = isset($args['value']) ? $args['value'] : '';
    if(fansub_array_has_value($lists)) {
        foreach($lists as $name => $data) {
            $all_option .= '<option value="' . esc_attr($name) . '" ' . selected($value, $name, false) . '>' . $data['Name'] . '</option>';
        }
    }
    $args['all_option'] = $all_option;
    fansub_field_select($args);
}

function fansub_field_select_term($args = array()) {
    fansub_field_sanitize_args($args);
    $taxonomy = fansub_get_value_by_key($args, 'taxonomy');
    $taxonomies = fansub_get_value_by_key($args, 'taxonomies');
    $taxonomies = fansub_sanitize_array($taxonomies);
    $taxonomies = fansub_remove_empty_array_item($taxonomies);
    if(!fansub_array_has_value($taxonomies) && empty($taxonomy)) {
        $taxonomy = 'category';
    }
    $taxonomies[] = $taxonomy;
    $taxonomies = fansub_sanitize_array($taxonomies);
    $options = isset($args['options']) ? $args['options'] : array();
    $force_empty = isset($args['force_empty']) ? (bool)$args['force_empty'] : false;
    $tax = get_taxonomy($taxonomy);
    if(!$force_empty) {
        if(!fansub_array_has_value($taxonomies) && !fansub_array_has_value($options)) {
            _e('Please pass a taxonomy or set options for arguments.', 'fansub');
            return;
        }
    }
    $only_parent = isset($args['only_parent']) ? $args['only_parent'] : false;
    $id = isset($args['id']) ? $args['id'] : '';
    $name = isset($args['name']) ? $args['name'] : '';
    $field_class = isset($args['field_class']) ? $args['field_class'] : '';
    if(is_object($tax)) {
        fansub_add_string_with_space_before($field_class, 'select-' . $tax->rewrite['slug'] . '-terms');
    }
    $args['field_class'] = fansub_add_string_with_space_before($field_class, 'select-term');
    $label = isset($args['label']) ? $args['label'] : '';
    $value = isset($args['value']) ? $args['value'] : '';
    $description = isset($args['description']) ? $args['description'] : '';
    $taxonomy_id = isset($args['taxonomy_id']) ? $args['taxonomy_id'] : '';
    $taxonomy_name = isset($args['taxonomy_name']) ? $args['taxonomy_name'] : '';
    $show_count = isset($args['show_count']) ? $args['show_count'] : true;
    $load_item = isset($args['load_item']) ? (bool)$args['load_item'] : true;
    $option_default = '';
    if(isset($args['option_default'])) {
        $option_default = $args['option_default'];
    } else {
        $default_text = isset($args['default_text']) ? $args['default_text'] : __('Choose term');
        $option_default = '<option value="0" data-taxonomy="">-- ' . $default_text . ' --</option>';
    }
    $all_option = $option_default;
    $args['before'] = '';
    if($load_item) {
        $options = wp_parse_args($options, $taxonomies);
        $options = fansub_sanitize_array($options);
        if(fansub_array_has_value($options)) {
            foreach($options as $tax) {
                if(!is_object($tax)) {
                    $tax = get_taxonomy($tax);
                }
                $term_args = array();
                if($only_parent) {
                    $term_args['parent'] = 0;
                }
                if(!is_object($tax)) {
                    continue;
                }
                $terms = fansub_get_terms($tax->name, $term_args);
                if(fansub_array_has_value($terms)) {
                    $show_count = isset($args['show_count']) ? $args['show_count'] : true;
                    $hirachical = isset($args['hirachical']) ? $args['hirachical'] : true;
                    $option_group = isset($args['option_group']) ? $args['option_group'] : true;
                    $select_args = array(
                        'selected' => $value,
                        'taxonomy' => $tax->name,
                        'show_count' => $show_count,
                        'hirachical' => $hirachical
                    );
                    $select = fansub_get_term_drop_down($select_args);
                    $select = fansub_remove_select_tag_keep_content($select);
                    $tmp = '';
                    if(!empty($select)) {
                        if($option_group) {
                            $tmp = '<optgroup label="' . $tax->labels->singular_name . '" data-taxonomy="' . $tax->name . '">';
                            $tmp .= $select;
                            $tmp .= '</optgroup>';
                        } else {
                            $tmp .= $select;
                        }
                    }
                    $all_option .= $tmp;
                }
            }
        }
    }
    $args['all_option'] = $all_option;
    $args['label'] = '';
    if(!isset($args['attributes']['data-taxonomy'])) {
        $args['attributes']['data-taxonomy'] = $taxonomy;
    }
    $args['attributes']['data-show-count'] = absint($show_count);
    fansub_field_select($args);
}

function fansub_field_widget_before($class = '', $inner = false) {
    if($inner) {
        fansub_add_string_with_space_before($class, 'fansub-widget-field-group');
    } else {
        fansub_add_string_with_space_before($class, 'fansub-widget');
    }
    echo '<div class="' . $class . '">';
}

function fansub_field_widget_after() {
    echo '</div>';
}

function fansub_widget_field($callback, $args) {
    if(fansub_callback_exists($callback)) {
        $hidden = isset($args['hidden']) ? $args['hidden'] : false;
        $container_class = '';
        if($hidden) {
            fansub_add_string_with_space_before($container_class, 'hidden');
        }
        fansub_field_widget_before($container_class, true);
        $args = fansub_field_sanitize_widget_args($args);
        if('fansub_field_select_chosen' == $callback) {
            $args['before'] = '<div class="fansub-widget-field">';
            $args['after'] = '</div>';
        }
        call_user_func($callback, $args);
        fansub_field_widget_after();
    }
}

function fansub_field_widget_field_title($id, $name, $value) {
    $args = array(
        'id' => $id,
        'name' => $name,
        'value' => $value,
        'label' => __('Title:', 'fansub')
    );
    fansub_widget_field('fansub_field_input', $args);
}

function fansub_widget_field_title($id, $name, $value) {
    fansub_field_widget_field_title($id, $name, $value);
}

function fansub_field_widget_field_show_title($id, $name, $value) {
    $args = array(
        'id' => $id,
        'name' => $name,
        'value' => $value,
        'label' => __('Show widget title?', 'fansub')
    );
    fansub_widget_field('fansub_field_input_checkbox', $args);
}

function fansub_field_admin_postbox($args = array()) {
    $title = fansub_get_value_by_key($args, 'title');
    $content = fansub_get_value_by_key($args, 'content');
    ?>
    <div class="meta-box-sortables ui-sortable">
        <div class="postbox">
            <button aria-expanded="true" class="handlediv button-link" type="button">
                <span class="screen-reader-text"><?php printf(__('Toggle panel: %s', 'fansub'), $title); ?></span>
                <span aria-hidden="true" class="toggle-indicator"></span>
            </button>
            <h2 class="hndle ui-sortable-handle">
                <span><?php echo $title; ?></span>
            </h2>
            <div class="inside">
                <div class="main">
                    <?php echo $content; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}