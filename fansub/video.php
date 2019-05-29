<?php
if(!function_exists('add_filter')) exit;
function fansub_video_source_meta_box($post_types = array()) {
    if(!fansub_array_has_value($post_types)) {
        $post_types[] = 'post';
    }
    $meta = new FANSUB_Meta('post');
    $meta->set_post_types($post_types);
    $meta->set_title(__('Video Source Information', 'fansub'));
    $meta->set_id('fansub_theme_video_source_information');
    $meta->add_field(array('field_args' => array('id' => 'video_url', 'label' => 'Video URL:')));
    $meta->add_field(array('field_args' => array('id' => 'video_code', 'label' => 'Video code:'), 'field_callback' => 'fansub_field_textarea'));
    $meta->init();
}

function fansub_video_play($args = array()) {
    $post_id = isset($args['post_id']) ? $args['post_id'] : get_the_ID();
    $video_url = get_post_meta($post_id, 'video_url', true);
    $video_code = get_post_meta($post_id, 'video_code', true);
    if(empty($video_url) && empty($video_code)) {
        if(fansub_automatic_video_posts_installed()) {
            $video_url = get_post_meta($post_id, '_ayvpp_video_url', true);
        }
    }
    $autoplay = isset($args['autoplay']) ? $args['autoplay'] : false;
    $width = isset($args['width']) ? $args['width'] : '';
    $height = isset($args['height']) ? $args['height'] : '';
    $rel = isset($args['rel']) ? $args['rel'] : false;
    $cc = isset($args['cc_load_policy']) ? $args['cc_load_policy'] : false;
    $iv = isset($args['iv_load_policy']) ? $args['iv_load_policy'] : false;
    $showinfo = isset($args['showinfo']) ? $args['showinfo'] : false;
    $player_id = fansub_get_value_by_key($args, 'player_id', 'fansub_player');
    if(empty($player_id)) {
        $player_id = 'fansub_player';
    }
    if(!empty($video_code)) {
        if($height > 0) {
            $video_code = preg_replace('/height="(.*?)"/i', 'height="' . $height . '"', $video_code);
        }
        if($width > 0) {
            $video_code = preg_replace('/width="(.*?)"/i', 'width="' . $width . '"', $video_code);
        }
        $video_code = preg_replace('/id="(.*?)"/i', 'id="' . $player_id . '"', $video_code);
        if(!fansub_string_contain($video_code, 'id="')) {
            $video_code = str_replace('<iframe', '<iframe id="' . $player_id . '"', $video_code);
        }
        $video_code = apply_filters('fansub_video_code_result', $video_code, $args);
        echo $video_code;
    } else {
        if(!empty($video_url)) {
            $video_args = array(
                'rel' => 0,
                'showinfo' => 0,
                'cc_load_policy' => 0,
                'iv_load_policy' => 3,
                'start' => 1
            );
            if($showinfo) {
                $video_args['showinfo'] = 1;
            }
            if($cc) {
                $video_args['cc_load_policy'] = 1;
            }
            if($iv) {
                $video_args['iv_load_policy'] = 1;
            }
            if((bool)$autoplay) {
                $video_args['autoplay'] = 1;
            }
            if($rel) {
                $video_args['rel'] = 1;
            }
            $video_args = apply_filters('fansub_embed_video_args', $video_args);
            $html = wp_oembed_get($video_url, $video_args);
            if($height > 0) {
                $html = preg_replace('/height="(.*?)"/i', 'height="' . $height . '"', $html);
            }
            if($width > 0) {
                $html = preg_replace('/width="(.*?)"/i', 'width="' . $width . '"', $html);
            }
            $html = preg_replace('/id="(.*?)"/i', 'id="' . $player_id . '"', $html);
            if(!fansub_string_contain($html, 'id="')) {
                $html = str_replace('<iframe', '<iframe id="' . $player_id . '"', $html);
            }
            $html = apply_filters('fansub_embed_video_result', $html, $video_args);
            echo $html;
        }
    }

    $video_id = get_post_meta($post_id, 'video_id', true);
    if(!empty($video_id)) {
        $video_server = get_post_meta($post_id, 'video_server', true);
        if('youtube' == $video_server) {

        }
    }
}

function fansub_detect_video_server_name($url) {
    $result = 'unknown';
    if(is_array($url)) {
        $url = array_shift($url);
    }
    if(false !== strrpos($url, 'youtube') || false !== strrpos($url, 'youtu.be')) {
        $result = 'youtube';
    } elseif(false !== strrpos($url, 'vimeo')) {
        $result = 'vimeo';
    } elseif(false !== strrpos($url, 'dailymotion') || false !== strrpos($url, 'dai.ly')) {
        $result = 'dailymotion';
    }
    return $result;
}

function fansub_detect_video_id($url) {
    $result = '';
    if(is_array($url)) {
        $url = array_shift($url);
    }
    $server = fansub_detect_video_server_name($url);
    $data = parse_url($url);
    $query = isset($data['query']) ? $data['query'] : '';
    parse_str($query, $output);
    switch($server) {
        case 'youtube':
            $result = isset($output['v']) ? $output['v'] : '';
            if(empty($result)) {
                $result = fansub_get_last_part_in_url($url);
            }
            break;
        case 'vimeo':
            $result = intval(fansub_get_last_part_in_url($url));
            break;
        case 'dailymotion':
            $result = fansub_get_last_part_in_url($url);
            break;
    }
    return $result;
}

function fansub_save_video_default_meta($post_id) {
    if(!fansub_can_save_post($post_id)) {
        return;
    }
    $video_url = get_post_meta($post_id, 'video_url');
    $server_name = fansub_detect_video_server_name($video_url);
    update_post_meta($post_id, 'video_server', $server_name);
    $video_id = fansub_detect_video_id($video_url);
    update_post_meta($post_id, 'video_id', $video_id);
    if(!has_post_thumbnail($post_id)) {
        $thumbnail_url = '';
        $thumbnails = array();
        switch($server_name) {
            case 'youtube':
                $api_key = fansub_get_google_api_key();
                $data = fansub_get_youtube_thumbnail_data_object($api_key, $video_id);
                $thumbnails = fansub_get_youtube_thumbnails($api_key, $video_id, $data);
                $thumbnail_url = fansub_get_youtube_thumbnail($api_key, $video_id, 'medium', $thumbnails);
                break;
            case 'vimeo':
                $thumbnails = fansub_get_vimeo_thumbnails($video_id);
                $thumbnail_url = fansub_get_vimeo_thumbnail($video_id, 'medium', $thumbnails);
                break;
            case 'dailymotion':
                $thumbnails = fansub_get_dailymotion_thumbnails($video_id);
                $thumbnail_url = fansub_get_dailymotion_thumbnail($video_id, 'medium', $thumbnails);
                break;
        }
        update_post_meta($post_id, 'thumbnail_url', $thumbnail_url);
        update_post_meta($post_id, 'thumbnails', $thumbnails);
    }
}

function fansub_convert_automatic_video_posts_data($post_id) {
    if(fansub_automatic_video_posts_installed()) {
        $video_id = get_post_meta($post_id, 'video_id', true);
        if(empty($video_id)) {
            $video_code = get_post_meta($post_id, 'video_code', true);
            if(empty($video_code)) {
                $video_url = get_post_meta($post_id, '_ayvpp_video_url', true);
                if(!empty($video_url)) {
                    update_post_meta($post_id, 'video_url', $video_url);
                    fansub_save_video_default_meta($post_id);
                }
            }
        }
    }
}

function fansub_add_parameter_to_oembed_result($html, $url, $args) {
    $args['ogenerated'] = 'fansub';
    $parameters = http_build_query($args);
    $html = str_replace('?feature=oembed', '?feature=oembed'. '&amp;' . $parameters, $html);
    return $html;
}
add_filter('oembed_result','fansub_add_parameter_to_oembed_result', 99, 3);

function fansub_get_youtube_data_object($api_key, $video_id) {
    $transient_name = 'fansub_theme_youtube_' . $video_id . '_data_object';
    $transient_name = strtolower($transient_name);
    if(false === ($data = get_transient($transient_name))) {
        $data = file_get_contents('https://www.googleapis.com/youtube/v3/videos?key=' . $api_key . '&part=snippet&id=' . $video_id);
        $data = json_decode($data);
        set_transient($transient_name, $data, YEAR_IN_SECONDS);
    }
    return $data;
}

function fansub_get_youtube_thumbnail_data_object($api_key, $video_id) {
    $transient_name = 'fansub_youtube_' . $video_id . '_thumbnail_object';
    $transient_name = strtolower($transient_name);
    if(false === ($data = get_transient($transient_name))) {
        $data = fansub_get_youtube_data_object($api_key, $video_id);
        $data = $data->items[0]->snippet->thumbnails;
        set_transient($transient_name, $data, YEAR_IN_SECONDS);
    }
    return $data;
}

function fansub_get_valid_video_thumbnail_data($arr, $key) {
    return fansub_find_valid_value_in_array($arr, $key);
}

function fansub_get_valid_youtube_thumbnail($arr, $key) {
    $result = '';
    if(is_array($arr)) {
        if(isset($arr[$key])) {
            $result = isset($arr[$key]['url']) ? $arr[$key]['url'] : '';
        } else {
            $index = absint(count($arr)/2);
            if(isset($arr[$index])) {
                $last = $arr[$index];
            } else {
                $last = current($arr);
            }
            $result = isset($last['url']) ? $last['url'] : '';
        }
    }
    return $result;
}

function fansub_get_youtube_thumbnails($api_key, $video_id, $data = null) {
    if(null == $data) {
        $data = fansub_get_youtube_thumbnail_data_object($api_key, $video_id);
        $data = fansub_std_object_to_array($data);
    } elseif(is_object($data)) {
        $data = fansub_std_object_to_array($data);
    }
    $result = array(
        'small' => fansub_get_value_by_key($data, array('default', 'url')),
        'medium' => fansub_get_value_by_key($data, array('medium', 'url')),
        'high' => fansub_get_value_by_key($data, array('high', 'url')),
        'standard' => fansub_get_value_by_key($data, array('standard', 'url')),
        'large' => fansub_get_value_by_key($data, array('maxres', 'url'))
    );
    return $result;
}

function fansub_get_youtube_thumbnail($api_key, $video_id, $type = 'medium', $thumbnails = null) {
    if(!is_array($thumbnails)) {
        $thumbnails = fansub_get_youtube_thumbnails($api_key, $video_id);
    }
    return fansub_get_valid_video_thumbnail_data($thumbnails, $type);
}

function fansub_get_youtube_thumbnail_url($api_key, $video_id, $type = 'medium', $data = null) {
    if(null == $data) {
        $data = fansub_get_youtube_thumbnail_data_object($api_key, $video_id);
        $data = fansub_std_object_to_array($data);
    } elseif(is_object($data)) {
        $data = fansub_std_object_to_array($data);
    }
    $result = fansub_get_valid_youtube_thumbnail($data, $type);
    return $result;
}

function fansub_get_vimeo_data($id) {
    $transient_name = 'fansub_vimeo_' . $id . '_data';
    $transient_name = strtolower($transient_name);
    if(false === ($data = get_transient($transient_name))) {
        $url = 'http://vimeo.com/api/v2/video/' . $id . '.php';
        $data = unserialize(file_get_contents($url));
        $data = isset($data[0]) ? $data[0] : array();
        set_transient($transient_name, $data, YEAR_IN_SECONDS);
    }
    return $data;
}

function fansub_get_vimeo_thumbnails($id) {
    $data = fansub_get_vimeo_data($id);
    $small = fansub_get_value_by_key($data, 'thumbnail_small');
    $medium = fansub_get_value_by_key($data, 'thumbnail_medium');
    $large = fansub_get_value_by_key($data, 'thumbnail_large');
    $result = array(
        'thumbnail_small' => $small,
        'thumbnail_medium' => $medium,
        'thumbnail_large' => $large,
        'small' => $small,
        'medium' => $medium,
        'large' => $large
    );
    return $result;
}

function fansub_get_vimeo_thumbnail($id, $type = 'medium', $thumbnails = null) {
    if(!is_array($thumbnails)) {
        $thumbnails = fansub_get_vimeo_thumbnails($id);
    }
    return fansub_get_valid_video_thumbnail_data($thumbnails, $type);
}

function fansub_get_dailymotion_data($id) {
    $transient_name = 'fansub_dailymotion_' . $id . '_data';
    $transient_name = strtolower($transient_name);
    if(false === ($data = get_transient($transient_name))) {
        $fields = array(
            'thumbnail_small_url',
            'thumbnail_medium_url',
            'thumbnail_large_url',
            'thumbnail_720_url'
        );
        $fields = apply_filters('fansub_dailymotion_data_fields', $fields);
        $fields = implode(',', $fields);
        $url = 'https://api.dailymotion.com/video/' . $id . '?fields=' . $fields;
        $data = file_get_contents($url);
        $data = fansub_json_string_to_array($data);
        set_transient($transient_name, $data, YEAR_IN_SECONDS);
    }
    return $data;
}

function fansub_get_dailymotion_thumbnails($id) {
    $data = fansub_get_dailymotion_data($id);
    $small = fansub_get_value_by_key($data, 'thumbnail_small_url');
    $medium = fansub_get_value_by_key($data, 'thumbnail_medium_url');
    $large = fansub_get_value_by_key($data, 'thumbnail_large_url');
    $result = array(
        'thumbnail_small' => $small,
        'thumbnail_medium' => $medium,
        'thumbnail_large' => $large,
        'small' => $small,
        'medium' => $medium,
        'large' => $large
    );
    return $result;
}

function fansub_get_dailymotion_thumbnail($id, $type = 'medium', $thumbnails = null) {
    if(!is_array($thumbnails)) {
        $thumbnails = fansub_get_dailymotion_thumbnails($id);
    }
    return fansub_get_valid_video_thumbnail_data($thumbnails, $type);
}

function fansub_automatic_video_posts_installed() {
    $result = false;
    if(function_exists('WP_ayvpp_activate_plugin')) {
        $result = true;
    }
    return $result;
}

function fansub_youtube_default_video_thumbnail_url($video_id) {
    $url = 'https://i.ytimg.com/vi/' . $video_id . '/default.jpg';
    $url = apply_filters('fansub_youtube_default_video_thumbnail_url', $url, $video_id);
    return $url;
}