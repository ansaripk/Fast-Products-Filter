<?php

class FWF_Helper {

    
    public static function get_attributes_tax() {
        global $wpdb;

        $attribute_taxonomies = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_name != '' ORDER BY attribute_name ASC;" );
        //set_transient( 'wc_attribute_taxonomies', $attribute_taxonomies );
    
        $attribute_taxonomies = array_filter( $attribute_taxonomies  );
        return $attribute_taxonomies;
    }



    public static function get_attributes() {


        $attributes = [];
        $t_data = [];

        $attibs = self::get_attributes_tax();
        

        foreach( $attibs as $attrib ) {
            //$terms = get_terms('pa_'.$attrib->attribute_name);
            $args = array(
                'taxonomy'      => 'pa_'.$attrib->attribute_name,
                'hide_empty'    => false
            );
            $terms = get_categories( $args );
            $attributes[] = array(
                'id'    => $attrib->attribute_name,
                'label' => $attrib->attribute_label,
                'terms' => $terms
            );
        }         
        return $attributes;
    }

    public static function is_woocommerce_active() {
        return class_exists( 'WooCommerce' );
    }
}