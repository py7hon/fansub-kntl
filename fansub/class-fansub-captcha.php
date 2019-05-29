<?php

if(!function_exists('add_filter')) exit;
class FANSUB_Captcha {
    private $chars;
    private $length;
    private $fonts;
    private $background;
    private $foreground;
    private $code;
    private $uppercase;
    private $lowercase;
    private $size;
    private $base;
    private $font_size;
    private $file_mode;
    private $image_type;
    private $font_char_width;
    private $save_path;
    private $save_url;
    private $session_name;
    private $expired_minutes;
    private $pixel;
    private $pixel_color;
    private $line;
    private $line_color;

    public function set_expired_minutes($minutes) {
        $this->expired_minutes = $minutes;
    }

    public function get_expired_minutes() {
        return $this->expired_minutes;
    }

    public function set_session_name($name) {
        $this->session_name = $name;
    }

    public function get_session_name() {
        return $this->session_name;
    }

    public function set_save_path($path) {
        $this->save_path = $path;
    }

    public function get_save_path() {
        return $this->save_path;
    }

    public function set_save_url($url) {
        $this->save_url = $url;
    }

    public function get_save_url() {
        return $this->save_url;
    }

    public function set_font_char_width($width) {
        $this->font_char_width = $width;
    }

    public function get_font_char_width() {
        return $this->font_char_width;
    }

    public function set_image_type($type) {
        $this->image_type = $type;
    }

    public function get_image_type() {
        return $this->image_type;
    }

    public function set_file_mode($mode) {
        $this->file_mode = $mode;
    }

    public function get_file_mode() {
        return $this->file_mode;
    }

    public function set_font_size($font_size) {
        $this->font_size = $font_size;
    }

    public function get_font_size() {
        return $this->font_size;
    }

    public function set_base($base) {
        $this->base = $base;
    }

    public function get_base() {
        return $this->base;
    }

    public function set_size($size) {
        $this->size = $size;
    }

    public function get_size() {
        return $this->size;
    }

    public function set_width($width) {
        $this->size[0] = $width;
    }

    public function set_height($height) {
        $this->size[1] = $height;
    }

    public function set_uppercase($uppercase) {
        $this->uppercase = $uppercase;
    }

    public function get_uppercase() {
        return $this->uppercase;
    }

    public function set_lowercase($lowercase) {
        $this->lowercase = $lowercase;
    }

    public function get_lowercase() {
        return $this->lowercase;
    }

    public function set_code($code) {
        $this->code = $code;
    }

    public function get_code() {
        return $this->code;
    }

    public function set_foreground($fg) {
        $this->foreground = $fg;
    }

    public function get_foreground() {
        return $this->foreground;
    }

    public function set_background($bg) {
        $this->background = $bg;
    }

    public function get_background() {
        return $this->background;
    }

    public function set_fonts($fonts) {
        $this->fonts = $fonts;
    }

    public function add_font($font) {
        $this->fonts[] = $font;
    }

    public function get_fonts() {
        return $this->fonts;
    }

    public function set_length($length) {
        $this->length = $length;
    }

    public function get_length() {
        return $this->length;
    }

    public function set_chars($chars) {
        $this->chars = $chars;
    }

    public function get_chars() {
        return $this->chars;
    }

    public function set_pixel($bool) {
        $this->pixel = $bool;
    }

    public function get_pixel() {
        return (bool)$this->pixel;
    }

    public function set_pixel_color($color) {
        $this->pixel_color = $color;
    }

    public function get_pixel_color() {
        return $this->pixel_color;
    }

    public function set_line($bool) {
        $this->line = $bool;
    }

    public function get_line() {
        return (bool)$this->line;
    }

    public function set_line_color($color) {
        $this->line_color = $color;
    }

    public function get_line_color() {
        return $this->line_color;
    }

    public function __construct() {
        $defaults = array(
            'chars' => fansub_get_safe_captcha_characters(),
            'length' => 5,
            'uppercase' => true,
            'lowercase' => true,
            'size' => array(87, 25),
            'background' => array(255, 255, 255),
            'foreground' => array(138, 200, 67),
            'pixel' => true,
            'pixel_color' => array(205, 255, 205),
            'line' => true,
            'line_color' => array(205, 215, 205),
            'font_char_width' => 14,
            'file_mode' => 0444,
            'image_type' => 'png',
            'font_size' => 13,
            'base' => array(10, 20),
            'save_path' => FANSUB_CONTENT_PATH . '/captcha',
            'save_url' => content_url('fansub/captcha'),
            'session_name' => 'fansub_captcha',
            'expired_minutes' => 3,
            'fonts' => array(FANSUB_PATH . '/fonts/Tahoma.ttf')
        );
        $args = apply_filters('fansub_captcha_default_args', array());
        $args = wp_parse_args($args, $defaults);
        $this->set_chars($args['chars']);
        $this->set_length($args['length']);
        $this->set_uppercase($args['uppercase']);
        $this->set_lowercase($args['lowercase']);
        $this->set_size($args['size']);
        $this->set_background($args['background']);
        $this->set_foreground($args['foreground']);
        $this->set_line($args['line']);
        $this->set_line_color($args['line_color']);
        $this->set_pixel($args['pixel']);
        $this->set_pixel_color($args['pixel_color']);
        $this->set_font_char_width($args['font_char_width']);
        $this->set_file_mode($args['file_mode']);
        $this->set_image_type($args['image_type']);
        $this->set_font_size($args['font_size']);
        $this->set_base($args['base']);
        $this->set_save_path($args['save_path']);
        $this->set_save_url($args['save_url']);
        $this->set_session_name($args['session_name']);
        $this->set_fonts($args['fonts']);
        $this->set_expired_minutes($args['expired_minutes']);
    }

    public function generate_image() {
        $dir = wp_normalize_path($this->get_save_path());
        if(!wp_mkdir_p($dir)) {
            return false;
        }
        $this->cleanup_expired();
        $code = fansub_random_string($this->get_length(), $this->get_chars());
        $filename = '';
        $dir = trailingslashit($dir);
        if($this->get_uppercase()) {
            $code = strtoupper($code);
        }
        $size = $this->get_size();
        if($im = @imagecreatetruecolor($size[0], $size[1])) {
            $fonts = $this->get_fonts();
            if(!fansub_array_has_value($fonts)) {
                return false;
            }
            $filename = md5(fansub_random_string());
            $background = $this->get_background();
            $foreground = $this->get_foreground();
            $bg = @imagecolorallocate($im, $background[0], $background[1], $background[2]);
            $fg = @imagecolorallocate($im, $foreground[0], $foreground[1], $foreground[2]);
            @imagefill($im, 0, 0, $bg);

            if($this->get_pixel()) {
                $pixel_colors = $this->get_pixel_color();
                $pixel_color = @imagecolorallocate($im, $pixel_colors[0], $pixel_colors[1], $pixel_colors[2]);
                $pixels = rand(300, 600);
                for($i = 0; $i < $pixels; $i++) {
                    @imagesetpixel($im, rand(1, 100), rand(1, 100), $pixel_color);
                }
            }

            $base = $this->get_base();
            $x = $base[0] + mt_rand(-2, 2);
            for($i = 0; $i < strlen($code); $i++) {
                $font = $fonts[array_rand($fonts)];
                $font = wp_normalize_path($font);
                $y = $base[1] + mt_rand(-2, 2);
                $angle = mt_rand(-20, 20);
                @imagettftext($im, $this->get_font_size(), $angle, $x, $y, $fg, $font, $code[$i]);
                $x += $this->get_font_char_width();
            }

            if($this->get_line()) {
                $lines = rand(5, 10);
                $line_colors = $this->get_line_color();
                $line_color = @imagecolorallocate($im, $line_colors[0], $line_colors[1], $line_colors[2]);
                for($i = 0; $i < $lines; $i++) {
                    @imageline($im, rand(1, 100), rand(1, 100), rand(1, 100), rand(1, 100), $line_color);
                }
            }

            switch($this->get_image_type()) {
                case 'jpeg':
                    $filename = sanitize_file_name($filename. '.jpeg');
                    $file = wp_normalize_path($dir . $filename);
                    @imagejpeg($im, $file);
                    break;
                case 'gif':
                    $filename = sanitize_file_name($filename . '.gif');
                    $file = wp_normalize_path($dir . $filename);
                    @imagegif($im, $file);
                    break;
                case 'png':
                default:
                    $filename = sanitize_file_name($filename . '.png');
                    $file = wp_normalize_path($dir . $filename);
                    @imagepng($im, $file);
            }
            @imagedestroy($im);
            @chmod($file, $this->get_file_mode());
        }
        if(!empty($filename)) {
            if($this->get_lowercase()) {
                $code = strtolower($code);
            }
            $data = array(
                'hashed' => wp_hash_password($code),
                'timestamp' => strtotime(fansub_get_current_date('Y-m-d H:i:s'))
            );
            $this->set_session_data($data);
        }
        $this->set_code($code);
        return trailingslashit($this->get_save_url()) . $filename;
    }

    public function get_session_data() {
        $session_name = $this->get_session_name();
        $captcha_session = isset($_SESSION[$session_name]) ? maybe_unserialize($_SESSION[$session_name]) : array();
        return $captcha_session;
    }

    public function set_session_data($data) {
        $session_name = $this->get_session_name();
        $_SESSION[$session_name] = maybe_serialize($data);
    }

    public function check($code) {
        $current_datetime = fansub_get_current_date('Y-m-d H:i:s');
        $captcha_session = $this->get_session_data();
        $timestamp = fansub_get_value_by_key($captcha_session, 'timestamp');
        if(!is_numeric($timestamp)) {
            return false;
        }
        if(strtotime('+' . strval($this->get_expired_minutes()) . ' minutes', $timestamp) < strtotime($current_datetime)) {
            return false;
        }
        if($this->get_lowercase()) {
            $code = strtolower($code);
        }
        $save_code = fansub_get_value_by_key($captcha_session, 'hashed');
        return wp_check_password($code, $save_code);
    }

    public function remove($prefix) {
        $suffixes = array('.jpeg', '.gif', '.png', '.php', '.txt');
        foreach($suffixes as $suffix) {
            $dir = trailingslashit($this->get_save_path());
            $filename = sanitize_file_name($prefix . $suffix);
            $file = wp_normalize_path($dir . $filename);
            if(@is_file($file)) {
                @unlink($file);
            }
        }
    }

    public function cleanup_expired($minutes = 60) {
        $dir = trailingslashit($this->get_save_path());
        $dir = wp_normalize_path($dir);
        if(!@is_dir($dir) || !@is_readable($dir)) {
            return false;
        }
        $is_win = ('WIN' === strtoupper(substr(PHP_OS, 0, 3)));
        if(!($is_win ? win_is_writable($dir) : @is_writable($dir))) {
            return false;
        }
        fansub_delete_old_file($dir, $minutes * MINUTE_IN_SECONDS);
        $count = 0;
        if($handle = @opendir($dir)) {
            while(false !== ($filename = readdir($handle))) {
                if(!preg_match('/^[0-9]+\.(php|txt|png|gif|jpeg)$/', $filename)) {
                    continue;
                }
                $file = wp_normalize_path($dir . $filename);
                $stat = @stat($file);
                if(($stat['mtime'] + $minutes * MINUTE_IN_SECONDS) < time()) {
                    @unlink($file);
                    $count += 1;
                }
            }
            closedir($handle);
        }
        return $count;
    }
}