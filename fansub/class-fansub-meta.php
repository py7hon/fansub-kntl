<?php
if(!function_exists('add_filter')) exit;

class FANSUB_Meta {
    private $type;
    private $fields;
    private $callback;
    private $id;
    private $post_types;
    private $title;
    private $context;
    private $priority;
    private $callback_args;
    private $show;
    private $taxonomies;
    private $add_callback;
    private $translation;
    private $column;
    private $edit_callback;

    private $use_media_upload = false;
    private $use_color_picker = false;
    private $use_datetime_picker = false;
    private $use_select_chosen = false;

    public function set_use_select_chosen($use) {
        $this->use_select_chosen = $use;
    }

    public function get_use_select_chosen() {
        return (bool)$this->use_select_chosen;
    }

    public function set_use_datetime_picker($use) {
        $this->use_datetime_picker = $use;
    }

    public function get_use_datetime_picker() {
        return (bool)$this->use_datetime_picker;
    }

    public function set_use_color_picker($use) {
        $this->use_color_picker = $use;
    }

    public function get_use_color_picker() {
        return (bool)$this->use_color_picker;
    }

    public function set_edit_callback($edit_callback) {
        $this->edit_callback = $edit_callback;
    }

    public function get_edit_callback() {
        return $this->edit_callback;
    }

    public function set_use_media_upload($use) {
        $this->use_media_upload = $use;
    }

    public function get_use_media_upload() {
        return (bool)$this->use_media_upload;
    }

    public function set_column($column) {
        $this->column = $column;
    }

    public function get_column() {
        return $this->column;
    }

    public function set_translation($translation) {
        $this->translation = $translation;
    }

    public function get_translation() {
        return $this->translation;
    }

    public function set_add_callback($add_callback) {
        $this->add_callback = $add_callback;
    }

    public function get_add_callback() {
        return $this->add_callback;
    }

    public function set_taxonomies($taxonomies) {
        $this->taxonomies = $taxonomies;
    }

    public function get_taxonomies() {
        return $this->taxonomies;
    }

    public function add_taxonomy($taxonomy) {
        $this->taxonomies[] = $taxonomy;
        $this->set_taxonomies(fansub_sanitize_array($this->get_taxonomies()));
    }

    public function set_show($show) {
        $this->show = $show;
    }

    public function get_show() {
        return $this->show;
    }

    public function set_callback_args($callback_args) {
        $this->callback_args = $callback_args;
    }

    public function get_callback_args() {
        return $this->callback_args;
    }

    public function set_priority($priority) {
        $this->priority = $priority;
    }

    public function get_priority() {
        return $this->priority;
    }

    public function set_context($context) {
        $this->context = $context;
    }

    public function get_context() {
        return $this->context;
    }

    public function set_callback($callback) {
        $this->callback = $callback;
    }

    public function get_callback() {
        return $this->callback;
    }

    public function set_id($id) {
        $this->id = $id;
    }

    public function get_id() {
        return $this->id;
    }

    public function set_post_types($post_types) {
        if(!is_array($post_types)) {
            $post_types = array($post_types);
        }
        $this->post_types = $post_types;
    }

    public function get_post_types() {
        return $this->post_types;
    }

    public function add_post_type($post_type) {
        $this->post_types[] = $post_type;
        $this->set_post_types(fansub_sanitize_array($this->get_post_types()));
    }

    public function set_title($title) {
        $this->title = $title;
    }

    public function get_title() {
        return $this->title;
    }

    public function set_fields($fields) {
        $this->fields = $fields;
    }

    public function get_fields() {
        return $this->fields;
    }

    public function add_field($args) {
        $callback = fansub_get_value_by_key($args, 'field_callback');
        $field_args = isset($args['field_args']) ? $args['field_args'] : $args;
        $data_type = fansub_get_value_by_key($args, 'data_type', 'default');
        if('fansub_field_datetime_picker' == $callback) {
            $this->set_use_datetime_picker(true);
            $field_args['type'] = 'datetime';
            $args['type'] = 'datetime';
            $data_type = 'datetime';
        } elseif('fansub_field_color_picker' == $callback) {
            $this->set_use_color_picker(true);
        } elseif('fansub_field_media_upload' == $callback) {
            $this->set_use_media_upload(true);
        } elseif('fansub_field_select_chosen' == $callback) {
            $this->set_use_select_chosen(true);
        }

        $this->sanitize_field_args($field_args);
        if(isset($args['options'])) {
            $field_args['options'] = $args['options'];
        }
        if($this->get_type() != 'post' && !isset($field_args['label']) && isset($field_args['title'])) {
            $field_args['label'] = $field_args['title'];
        }
        $field_args['data_type'] = $data_type;
        $args['field_args'] = $field_args;
        $this->fields[] = $args;
    }

    public function set_type($type) {
        $this->type = $type;
    }

    public function get_type() {
        return $this->type;
    }

    public function __construct($type) {
        $this->set_type($type);
        $this->set_post_types(array());
        $this->set_taxonomies(array());
        $this->set_fields(array());
        $this->set_title(__('Extra information', 'fansub'));
        $this->set_id('fansub_custom_meta');
        $this->set_context('normal');
        $this->set_priority('high');
    }

    public function init() {
        if($this->is_term_meta()) {
            $this->term_meta_init();
        } elseif($this->is_menu_item_meta()) {
            $this->menu_item_meta_init();
        } else {
            $this->post_meta_box_init();
        }
    }

    public function add_custom_menu_item_meta_field($item, $args, $depth) {
        echo '<div class="fansub-custom-fields menu-item-fields">';
        if(fansub_callback_exists($this->get_callback())) {
            call_user_func($this->get_callback());
        } else {
            $menu_id = $item->ID;
            foreach($this->get_fields() as $field) {
                $field_args = isset($field['field_args']) ? $field['field_args'] : array();
                $callback = isset($field['field_callback']) ? $field['field_callback'] : 'fansub_field_input';
                $id = fansub_get_value_by_key($field_args, 'id');
                $name = fansub_get_value_by_key($field_args, 'name');
                $data = $this->build_menu_item_field_data($name, $menu_id);
                $field_args['id'] = $data['id'];
                $field_args['name'] = $data['name'];
                $field_args['class'] = $data['class'];
                if(!isset($field_args['value'])) {
                    $field_args['value'] = $item->{$name};
                }
                if(fansub_callback_exists($callback)) {
                    call_user_func($callback, $field_args);
                } else {
                    echo '<p>' . sprintf(__('The callback function %s does not exists!', 'fansub'), '<strong>' . $callback . '</strong>') . '</p>';
                }
            }
        }
        echo '</div>';
    }

    public function load_style_and_script() {
        if($this->get_use_media_upload()) {
            add_filter('fansub_wp_enqueue_media', '__return_true');
        }
        if($this->get_use_color_picker()) {
            add_filter('fansub_use_color_picker', '__return_true');
        }
        if($this->get_use_datetime_picker()) {
            add_filter('fansub_admin_jquery_datetime_picker', '__return_true');
        }
        if($this->get_use_select_chosen()) {
            add_filter('fansub_use_chosen_select', '__return_true');
        }
    }

    public function add_menu_item_meta($menu_item) {
        foreach($this->get_fields() as $field) {
            $name = $field['field_args']['name'];
            $menu_item->{$name} = fansub_get_post_meta($this->build_menu_item_meta_key($name), $menu_item->ID);
        }
        return $menu_item;
    }

    public function build_menu_item_meta_key($key_name) {
        $key_name = str_replace('-', '_', $key_name);
        return '_menu_item_' . $key_name;
    }

    public function menu_item_meta_init() {
        global $pagenow;
        $this->load_style_and_script();
        if('nav-menus.php' == $pagenow) {
            add_filter('fansub_use_admin_style_and_script', '__return_true');
        }
        add_filter('wp_setup_nav_menu_item', array($this, 'setup_nav_menu_item'));
        add_action('wp_update_nav_menu_item', array($this, 'update_nav_menu_item'), 10, 3);
        add_filter('wp_edit_nav_menu_walker', array($this, 'edit_nav_menu_walker'), 10, 2);
        add_action('fansub_edit_menu_item_field', array($this, 'add_custom_menu_item_meta_field'), 10, 3);
        add_filter('fansub_setup_nav_menu_item', array($this, 'add_menu_item_meta'));
    }

    public function setup_nav_menu_item($menu_item) {
        $menu_item = apply_filters('fansub_setup_nav_menu_item', $menu_item);
        return $menu_item;
    }

    public function update_nav_menu_item($menu_id, $menu_item_db_id, $args) {
        do_action('fansub_update_nav_menu_item', $menu_id, $menu_item_db_id, $args);
        foreach($this->get_fields() as $field) {
            $name = $field['field_args']['name'];
            $this->update_menu_item_meta_on_save($name, $menu_item_db_id);
        }
    }

    public function update_menu_item_meta_on_save($field_name, $menu_item_db_id) {
        $field_name = str_replace('_', '-', $field_name);
        $key = 'menu-item-' . $field_name;
        if(isset($_REQUEST[$key]) && is_array($_REQUEST[$key])) {
            $value = $_REQUEST[$key][$menu_item_db_id];
            update_post_meta($menu_item_db_id, $this->build_menu_item_meta_key($field_name), $value);
        }
    }

    function edit_nav_menu_walker($walker, $menu_id) {
        $walker = apply_filters('fansub_edit_nav_menu_walker', 'FANSUB_Menu_Edit_Walker', $menu_id);
        return $walker;
    }

    public function build_menu_item_field_id($field_name, $item_id) {
        $id = str_replace('_', '-', $field_name);
        return 'edit-menu-item-' . $id . '-' . $item_id;
    }

    public function build_menu_item_field_name($field_name, $item_id) {
        $name = str_replace('_', '-', $field_name);
        return 'menu-item-' . $name . '[' . $item_id . ']';
    }

    public function build_menu_item_field_class($field_name) {
        $field_name = str_replace('_', '-', $field_name);
        return 'edit-menu-item-' . $field_name;
    }

    public function build_menu_item_field_data($field_name, $item_id) {
        $result = array(
            'id' => $this->build_menu_item_field_id($field_name, $item_id),
            'name' => $this->build_menu_item_field_name($field_name, $item_id),
            'class' => $this->build_menu_item_field_class($field_name)
        );
        return $result;
    }

    public function term_meta_init() {
        global $pagenow;
        $this->load_style_and_script();
        if('edit-tags.php' == $pagenow || 'term.php' == $pagenow) {
            add_filter('fansub_use_admin_style_and_script', '__return_true');
        }
        foreach($this->get_taxonomies() as $taxonomy) {
            add_action($taxonomy . '_add_form_fields', array($this, 'term_field_add_page'));
            add_action($taxonomy . '_edit_form_fields', array($this, 'term_field_edit_page'));
            add_action('edited_' . $taxonomy, array($this, 'save_term_data'));
            add_action('created_' . $taxonomy, array($this, 'save_term_data'));
        }
    }

    public function term_field_add_page($taxonomy) {
        if(fansub_callback_exists($this->get_add_callback())) {
            call_user_func($this->get_add_callback(), $taxonomy);
        } else {
            foreach($this->get_fields() as $field) {
                $on_add_page = isset($field['on_add_page']) ? $field['on_add_page'] : false;
                if($on_add_page) {
                    $callback = isset($field['field_callback']) ? $field['field_callback'] : 'fansub_field_input';
                    if(fansub_callback_exists($callback)) {
                        $field_args = isset($field['field_args']) ? $field['field_args'] : array();
                        $id = isset($field_args['id']) ? $field_args['id'] : '';
                        $name = isset($field_args['name']) ? $field_args['name'] : '';
                        fansub_transmit_id_and_name($id, $name);
                        $class = 'term-' . $name . '-wrap';
                        $class = fansub_sanitize_file_name($class);
                        fansub_add_string_with_space_before($class, 'form-field fansub');
                        ?>
                        <div class="<?php echo $class; ?>">
                            <?php call_user_func($callback, $field_args); ?>
                        </div>
                        <?php
                    }
                }
            }
        }
    }

    public function term_field_edit_page($term) {
        if(fansub_callback_exists($this->get_edit_callback())) {
            call_user_func($this->get_edit_callback(), $term);
        } else {
            $term_id = $term->term_id;
            foreach($this->get_fields() as $field) {
                $field_args = isset($field['field_args']) ? $field['field_args'] : array();
                $callback = isset($field['field_callback']) ? $field['field_callback'] : 'fansub_field_input';
                $label = fansub_get_value_by_key($field_args, 'title', fansub_get_value_by_key($field_args, 'label'));
                if('fansub_field_input_checkbox' != $callback && 'fansub_field_input_radio' != $callback) {
                    unset($field_args['label']);
                }
                $id = isset($field_args['id']) ? $field_args['id'] : '';
                $name = isset($field_args['name']) ? $field_args['name'] : '';
                fansub_transmit_id_and_name($id, $name);
                if(!isset($field_args['value'])) {
                    $value = fansub_term_get_meta($term_id, $name);
                    if(empty($value) && 'fansub_field_input_radio' == $callback) {
                        $value = fansub_get_value_by_key($field, 'default');
                    }
                    $field_args['value'] = $value;
                }
                $class = 'term-' . $name . '-wrap';
                $class = fansub_sanitize_file_name($class);
                fansub_add_string_with_space_before($class, 'form-field fansub');
                ?>
                <tr class="<?php echo $class; ?>">
                    <th scope="row"><label for="<?php echo esc_attr(fansub_sanitize_id($id)); ?>"><?php echo $label; ?></label></th>
                    <td>
                        <?php
                        if(fansub_callback_exists($callback)) {
                            call_user_func($callback, $field_args);
                        } else {
                            _e('Please set a valid callback for this field', 'fansub');
                        }
                        ?>
                    </td>
                </tr>
                <?php
            }
        }
    }

    public function save_term_data($term_id) {
        $fields = $this->get_fields();
        foreach($fields as $field) {
            $type = isset($field['type']) ? $field['type'] : 'default';
            $name = isset($field['field_args']['name']) ? $field['field_args']['name'] : '';
            if(empty($name)) {
                continue;
            }
            $value = fansub_sanitize_form_post($name, $type);
            fansub_term_update_meta($term_id, $name, $value);
        }
        return $term_id;
    }

    public function sanitize_field_args(&$args) {
        $id = isset($args['id']) ? $args['id'] : '';
        $name = isset($args['name']) ? $args['name'] : '';
        fansub_transmit_id_and_name($id, $name);
        $args['id'] = $id;
        $args['name'] = $name;
        if($this->is_term_meta()) {

        } elseif($this->is_menu_item_meta()) {
            $args['before'] = '<div class="field-' . fansub_sanitize_html_class($name) . ' description description-wide">';
            $args['after'] = '</div>';
        } else {
            $args['before'] = '<div class="meta-row">';
            $args['after'] = '</div>';
        }
        return $args;
    }

    public function is_term_meta() {
        if('term' == $this->get_type()) {
            return true;
        }
        return false;
    }

    public function is_menu_item_meta() {
        if('menu_item' == $this->get_type()) {
            return true;
        }
        return false;
    }

    public function post_meta_box_init() {
        global $pagenow;
        $this->load_style_and_script();
        if('post-new.php' == $pagenow || 'post.php' == $pagenow || $this->get_use_media_upload()) {
            add_filter('fansub_use_admin_style_and_script', '__return_true');
        }
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post'));
    }

    public function add_meta_box() {
        $post_type = fansub_get_current_post_type();
        if(in_array($post_type, $this->get_post_types())) {
            add_meta_box($this->get_id(), $this->get_title(), array($this, 'post_meta_box_callback'), $post_type, $this->get_context(), $this->get_priority(), $this->get_callback_args());
        }
    }

    public function post_meta_box_callback() {
        $class = 'fansub-meta-box';
        fansub_add_string_with_space_before($class, $this->get_context());
        fansub_add_string_with_space_before($class, $this->get_priority());
        foreach($this->get_post_types() as $post_type) {
            fansub_add_string_with_space_before($class, 'post-type-' . $post_type);
        }
        ?>
        <div class="<?php echo $class; ?>">
            <?php
            if(fansub_callback_exists($this->get_callback())) {
                call_user_func($this->get_callback());
            } else {
                global $post;
                $post_id = $post->ID;
                foreach($this->get_fields() as $field) {
                    $field_args = isset($field['field_args']) ? $field['field_args'] : array();
                    $callback = isset($field['field_callback']) ? $field['field_callback'] : 'fansub_field_input';
                    if(!isset($field_args['value'])) {
                        $field_args['value'] = get_post_meta($post_id, $field_args['name'], true);
                    }
                    if(fansub_callback_exists($callback)) {
                        call_user_func($callback, $field_args);
                    } else {
                        echo '<p>' . sprintf(__('The callback function %s does not exists!', 'fansub'), '<strong>' . $callback . '</strong>') . '</p>';
                    }
                }
            }
            do_action('fansub_post_meta_box_field', $this);
            $current_post_type = fansub_get_current_post_type();
            if(!empty($current_post_type)) {
                do_action('fansub_' . $current_post_type . '_meta_box_field');
            }
            do_action('fansub_meta_box_' . $this->get_id() . '_field');
            ?>
        </div>
        <?php
    }

    public function save_post($post_id) {
        if(!fansub_can_save_post($post_id)) {
            return $post_id;
        }
        foreach($this->get_fields() as $field) {
            $type = isset($field['type']) ? $field['type'] : fansub_get_value_by_key($field, 'data_type', 'default');
            $name = isset($field['field_args']['name']) ? $field['field_args']['name'] : '';
            if(empty($name)) {
                continue;
            }
            $value = fansub_sanitize_form_post($name, $type);
            update_post_meta($post_id, $name, $value);
            $names = fansub_get_value_by_key($field, 'names');
            if(fansub_array_has_value($names)) {
                $names = array_combine($names, $names);
                foreach($names as $child_name => $child) {
                    $type = fansub_get_value_by_key($child, 'type', fansub_get_value_by_key($field, 'data_type', 'default'));
                    $value = fansub_sanitize_form_post($child_name, $type);
                    update_post_meta($post_id, $child_name, $value);
                }
            }
        }
        return $post_id;
    }
}