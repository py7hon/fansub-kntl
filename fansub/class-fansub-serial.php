<?php
if(!function_exists('add_filter')) exit;
if(defined('FANSUB_SERIAL_VERSION')) {
    return;
}
define('FANSUB_SERIAL_VERSION', '1.0.0');
class FANSUB_Serial {
    private $characters;
    public $amount;
    public $pattern;
    public $splitter;

    public function __construct($pattern = '', $amount = 1, $splitter = '-') {
        if(empty($pattern)) {
            $pattern = 'XXXXX-XXXXX-XXXXX-XXXXX-XXXXX';
        }
        $this->set_amount($amount);
        $this->set_pattern($pattern);
        $this->set_splitter($splitter);
        $this->set_characters(fansub_get_safe_captcha_characters());
    }

    public function set_characters($characters) {
        $this->characters = $characters;
    }

    public function set_amount($amount) {
        $this->amount = $amount;
    }

    public function set_pattern($pattern) {
        $pattern = strtoupper($pattern);
        $this->pattern = $pattern;
    }

    public function set_splitter($splitter) {
        $this->splitter = $splitter;
    }

    public function get_characters() {
        return $this->characters;
    }

    public function get_amount() {
        return absint($this->amount);
    }

    public function get_pattern() {
        return strtoupper($this->pattern);
    }

    public function get_splitter() {
        return $this->splitter;
    }

    public function generate() {
        $amount = $this->get_amount();
        $splitter = $this->get_splitter();
        $pattern = $this->get_pattern();
        $pieces = explode($splitter, $pattern);
        $result = array();
        for($i = 0; $i < $amount; $i++) {
            $serial = array();
            foreach($pieces as $piece) {
                $len = strlen($piece);
                $item = fansub_random_string($len, $this->get_characters());
                $serial[] = $item;
            }
            $serial = implode($splitter, $serial);
            $result[] = strtoupper($serial);
        }
        if(2 > $amount) {
            $result = current($result);
        }
        return $result;
    }
}