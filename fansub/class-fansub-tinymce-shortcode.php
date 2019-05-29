<?php

if(!function_exists('add_filter')) exit;
class FANSUB_TinyMCE_Shortcode {
    public function __construct() {
        $mce = new FANSUB_TinyMCE();
        $script_url = FANSUB_URL . '/js/fansub-tinymce-shortcode-button' . FANSUB_JS_SUFFIX;
        $item_args = array(
            'name' => 'fansub_shortcode',
            'script' => $script_url,
            'type' => 'listbox'
        );
        $mce->add_item($item_args, 2);
        $mce->init();
    }
}