<?php

if(!function_exists('add_filter')) exit;
class FANSUB_TinyMCE {
    private $mce_buttons;
    private $mce_buttons_2;
    private $mce_buttons_3;
    private $mce_buttons_4;

    public function __construct() {
        $this->init_properties();
    }

    public function init() {
        add_action('init', array($this, 'init_button'));
    }

    private function init_properties() {
        $this->mce_buttons = array();
        $this->mce_buttons_2 = array();
        $this->mce_buttons_3 = array();
        $this->mce_buttons_4 = array();
    }

    public function init_button() {
        if(!fansub_current_user_can_use_rich_editor()) {
            return;
        }
        add_filter('mce_external_plugins', array($this, 'mce_external_plugins'));
        add_filter('mce_buttons', array($this, 'mce_buttons'));
        add_filter('mce_buttons_2', array($this, 'mce_buttons_2'));
        add_filter('mce_buttons_3', array($this, 'mce_buttons_3'));
        add_filter('mce_buttons_4', array($this, 'mce_buttons_4'));
    }

    public function add_item($args, $toolbar = 1) {
        switch($toolbar) {
            case 2:
                array_push($this->mce_buttons_2, $args);
                break;
            case 3:
                array_push($this->mce_buttons_3, $args);
                break;
            case 4:
                array_push($this->mce_buttons_4, $args);
                break;
            default:
                array_push($this->mce_buttons, $args);
        }
    }

    public function mce_external_plugins($plugin_array) {
        $plugin_array = $this->add_mce_button_script($this->mce_buttons, $plugin_array);
        $plugin_array = $this->add_mce_button_script($this->mce_buttons_2, $plugin_array);
        $plugin_array = $this->add_mce_button_script($this->mce_buttons_3, $plugin_array);
        $plugin_array = $this->add_mce_button_script($this->mce_buttons_4, $plugin_array);
        return $plugin_array;
    }

    private function add_mce_plugin_script($plugin_array, $name, $script) {
        $plugin_array[$name] = $script;
        return $plugin_array;
    }

    private function add_mce_button_script($mce_buttons, $plugin_array) {
        foreach($mce_buttons as $args) {
            $name = fansub_get_value_by_key($args, 'name');
            $script = fansub_get_value_by_key($args, 'script');
            if(!empty($name) && !empty($script)) {
                $plugin_array = $this->add_mce_plugin_script($plugin_array, $name, $script);
            }
        }
        return $plugin_array;
    }

    private function add_to_mce_list_button($buttons, $button, $type = 'button') {
        if('button' == $type) {
            $backup = array_pop($buttons);
            $buttons[] = $button;
            $buttons[] = $backup;
        } else {
            array_unshift($buttons, $button);
        }
        return $buttons;
    }

    private function loop_list_mce_buttons($mce_buttons, $buttons) {
        foreach($mce_buttons as $args) {
            $name = fansub_get_value_by_key($args, 'name');
            $type = fansub_get_value_by_key($args, 'type', 'button');
            if(!empty($name)) {
                $buttons = $this->add_to_mce_list_button($buttons, $name, $type);
            }
        }
        return $buttons;
    }

    public function mce_buttons($buttons) {
        $buttons = $this->loop_list_mce_buttons($this->mce_buttons, $buttons);
        return $buttons;
    }

    public function mce_buttons_2($buttons) {
        $buttons = $this->loop_list_mce_buttons($this->mce_buttons_2, $buttons);
        return $buttons;
    }

    public function mce_buttons_3($buttons) {
        $buttons = $this->loop_list_mce_buttons($this->mce_buttons_3, $buttons);
        return $buttons;
    }

    public function mce_buttons_4($buttons) {
        $buttons = $this->loop_list_mce_buttons($this->mce_buttons_4, $buttons);
        return $buttons;
    }
}