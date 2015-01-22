<?php

/**
 * Creating a class to handle a WordPress shortcode is overkill in most cases.
 * However, it is extremely handy when your shortcode requires helper functions.
 */
class ContentAd__Includes__Shortcode {

    protected
        $atts = array(),
        $content = false,
        $output = false;

    protected static $defaults = array();

    public static function shortcode( $atts, $content = '' ) {
        $shortcode = new self( $atts, $content );
        return $shortcode->output;
    }

    private function __construct( $atts, $content ) {
        $this->atts = shortcode_atts( self::$defaults, $atts );
        $this->content = $content;
        $this->init();
    }

    protected function init() {
        $this->output = ContentAd__Includes__API::get_ad_code('in_function');
    }

    function get_att( $name ) {
        return array_key_exists( $name, $this->atts ) ? $this->atts[$name] : false;
    }

}