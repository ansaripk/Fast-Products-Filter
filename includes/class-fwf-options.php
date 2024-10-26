<?php

class FWF_Options {

    public static $pagination_style;
    public static $accordion;
    public static $query_type;
    public static $fwf_posts_per_page;
    public static $filter_orders;
    public static $min_price;
    public static $max_price;

    public function __construct() {
        $this->get_options();
    }

    public function get_options() {
        self::$pagination_style = 'pagination'; 
        self::$accordion = get_option('fwf_accordion');
        self::$query_type = get_option('fwf_filter_query_type');
        self::$fwf_posts_per_page = get_option( 'posts_per_page' );
        self::$filter_orders = get_option('fwf_filters_order_field');
        self::$min_price = get_option('fwf_min_price');
        self::$max_price = get_option('fwf_max_price');
    }
}

new FWF_Options;