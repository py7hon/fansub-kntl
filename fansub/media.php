<?php
if(!function_exists('add_filter')) exit;
function fansub_media_sanitize_upload_file_name($file) {
    $file_name = isset($file['name']) ? $file['name'] : '';
    $file['name'] = fansub_sanitize_file_name($file_name);
    return $file;
}
add_filter('wp_handle_upload_prefilter', 'fansub_media_sanitize_upload_file_name');

function fansub_get_media_file_path($media_id) {
    return get_attached_file($media_id);
}

function fansub_crop_image_helper($args = array()) {
    $source = fansub_get_value_by_key($args, 'source');
    $dest = fansub_get_value_by_key($args, 'dest');
    $width = fansub_get_value_by_key($args, 'width');
    $height = fansub_get_value_by_key($args, 'height');
    $crop_center = (bool)fansub_get_value_by_key($args, 'crop_center');
    $x = absint(fansub_get_value_by_key($args, 'x', 0));
    $y = absint(fansub_get_value_by_key($args, 'y', 0));
    $info = pathinfo($source);
    $extension = fansub_get_value_by_key($info, 'extension', 'jpg');
    $image = null;
    switch($extension) {
        case 'png':
            $image = imagecreatefrompng($source);
            break;
        case 'gif':
            $image = imagecreatefromgif($source);
            break;
        case 'jpeg':
        case 'jpg':
            $image = imagecreatefromjpeg($source);
            break;
    }
    if(null === $image) {
        return $dest;
    }
    $thumb_width = $width;
    $thumb_height = $height;
    $width = imagesx($image);
    $height = imagesy($image);
    $original_aspect = $width / $height;
    $thumb_aspect = $thumb_width / $thumb_height;
    if($original_aspect >= $thumb_aspect) {
        $new_width = $width / ($height / $thumb_height);
        $new_height = $thumb_height;
    } else {
        $new_width = $thumb_width;
        $new_height = $height / ($width / $thumb_width);
    }
    $thumb = imagecreatetruecolor($thumb_width, $thumb_height);
    if($crop_center) {
        $x = 0 - ($new_width - $thumb_width) / 2;
        $y = 0 - ($new_height - $thumb_height) / 2;
    }
    imagecopyresampled($thumb, $image, $x, $y,  0, 0, $new_width, $new_height, $width, $height);
    $quality = absint(apply_filters('fansub_image_quality', 80));
    if(!is_numeric($quality) || $quality < 0 || $quality > 100) {
        $quality = 80;
    }
    switch($extension) {
        case 'png':
            $first_char = fansub_get_first_char($quality);
            $quality = absint($first_char);
            imagepng($thumb, $dest, $quality);
            break;
        case 'gif':
            imagegif($thumb, $dest);
            break;
        case 'jpeg':
        case 'jpg':
            imagejpeg($thumb, $dest, $quality);
            break;
    }
    unset($image);
    unset($thumb);
    return $dest;
}

function fansub_crop_image($args = array()) {
    $attachment_id = fansub_get_value_by_key($args, 'attachment_id');
    $url = fansub_get_value_by_key($args, 'url');
    $base_url = '';
    if(!fansub_id_number_valid($attachment_id) && !empty($url)) {
        $attachment_id = fansub_get_media_id($url);
    }
    if(!fansub_id_number_valid($attachment_id)) {
        if(empty($url)) {
            return new WP_Error('crop_image_size', __('Attachment ID is not valid.', 'fansub'));
        } else {
            $cropped = $url;
        }
    } else {
        $file_path = fansub_get_media_file_path($attachment_id);
        $width = fansub_get_value_by_key($args, 'width');
        $height = fansub_get_value_by_key($args, 'height');
        $size = fansub_get_image_sizes($attachment_id);
        $size = fansub_sanitize_size($size);
        if(empty($width) && empty($height)) {
            $cropped = $file_path;
        } else {
            if(empty($width)) {
                $width = $size[0];
            }
            if(empty($height)) {
                $height = $size[1];
            }
            $x = apply_filters('fansub_crop_image_x', 0, $args);
            $y = apply_filters('fansub_crop_image_y', 0, $args);
            $x = fansub_get_value_by_key($args, 'x', $x);
            $y = fansub_get_value_by_key($args, 'y', $y);
            $dest_file = fansub_get_value_by_key($args, 'dest_file', '');
            $path_info = pathinfo($file_path);
            if(empty($dest_file)) {
                $upload_dir = fansub_get_upload_folder_details();
                $base_path = apply_filters('fansub_custom_thumbnail_base_path', untrailingslashit($upload_dir['path']) . '/fansub/thumbs/', $args);
                if(!file_exists($base_path)) {
                    wp_mkdir_p($base_path);
                }
                $base_url = apply_filters('fansub_custom_thumbnail_base_url', untrailingslashit($upload_dir['url']) . '/fansub/thumbs/', $args);
                $filename = $path_info['filename'];
                $dest_file = $base_path . str_replace($filename, $filename . '-' . $width . '-' . $height, basename($file_path));
            }
            $crop_args = array(
                'source' => get_attached_file($attachment_id),
                'dest' => $dest_file,
                'width' => $width,
                'height' => $height,
                'x' => $x,
                'y' => $y
            );
            $crop_args = wp_parse_args($args, $crop_args);
            if(file_exists($dest_file)) {
                $override = fansub_get_value_by_key($args, 'override', false);
                if($override) {
                    unlink($dest_file);
                    $cropped = fansub_crop_image_helper($crop_args);
                } else {
                    $cropped = $dest_file;
                }
            } else {
                $cropped = fansub_crop_image_helper($crop_args);
            }
        }
    }
    if(file_exists($cropped)) {
        $output = fansub_get_value_by_key($args, 'output', 'url');
        if('url' == $output) {
            $cropped = fansub_media_path_to_url($attachment_id, $cropped, $base_url);
        }
    } else {
        $cropped = $url;
    }
    return apply_filters('fansub_crop_image', $cropped, $args);
}

function fansub_media_path_to_url($attachment_id, $file_path, $base_url = '') {
    if(empty($base_url)) {
        $parent_url = wp_get_attachment_url($attachment_id);
        $url = str_replace(basename($parent_url), basename($file_path), $parent_url);
    } else {
        $url = trailingslashit($base_url) . basename($file_path);
    }
    return apply_filters('fansub_media_path_to_url', $url, $attachment_id, $file_path);
}

function fansub_post_thumbnail_by_ajax($url, $thumbnail_url, $params) {
    if(FANSUB_DOING_AJAX) {
        $params['url'] = $thumbnail_url;
        $params['ajax_thumbnail'] = true;
        $params['crop_center'] = true;
        $params['override'] = true;
        $url = fansub_crop_image($params);
    }
    return $url;
}
add_filter('fansub_pre_bfi_thumb', 'fansub_post_thumbnail_by_ajax', 10, 3);