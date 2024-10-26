<?php

//define('AF_TEMPLATES_PATH', dirname(plugin_dir_path( __FILE__ )));

class Filter_Frontend {

    private $style;
    private $count = 1;

    public function __construct() {
        add_action('open_accordion', [$this, 'open_accordion_callback']);
        add_action('insert_accordion', [$this, 'accordion'], 10, 3);
        add_action('close_accordion', [$this, 'close_accordion_callback']);
        add_filter('categories_filter', [$this, 'categories_filter_callback'], 10, 1);
        add_action('render_filter', [$this, 'render_filter_callback'], 10, 1);
        add_action('woocommerce_before_shop_loop', [$this, 'mobile_filter_button'], 10);
        add_action('render_mobile_filters', [$this, 'output_mobile_menu'], 10);
    }

    public static function Show($params, $obj) {
        
        $opt_name = $params['attribute_name'] . '_style';
        $style = get_option($opt_name);

        if( isset( $params['heading'] )) {
            echo '<h5>'. esc_html( $params['heading'] ) . '</h5>';
        }

        self::show_filter($params, $obj);

    }

    public static function show_filter($params, $obj ) {
        echo '<ul class="filter-list '. esc_attr($params['attribute_name']) .'">';
        foreach( $obj as $term ) {
            if( get_option($term->slug) ) {
                ?>
                <li>
                    <label>
                        <input 
                            type="checkbox" 
                            class="filter-checkbox checkbox"
                            name="<?php echo esc_attr($term->slug) ?>" 
                            id="<?php echo esc_attr($term->term_id) ?>" 
                            data-tax="<?php echo esc_attr($term->taxonomy) ?>" 
                            data-slug="<?php echo esc_attr($term->slug) ?>"
                            value="<?php echo esc_attr($term->name) ?>"
                        >
                        <?php if( $params['attribute_name'] == 'color' ): ?>
                            <span style="background:<?php echo esc_attr($term->slug) ?>"></span>
                        <?php endif; ?>
                        <?php echo esc_html($term->name) ?>
                    </label>
                </li>
                <?php
            } 
        }
        echo '</ul>';
    }

    public function open_accordion_callback() { 
        // pro version only
    }

    public function accordion($title, $body, $flag) {
        // pro version only
    }

    public function close_accordion_callback() { 
        // pro version only
    }

    public function categories_filter_callback( $params ) {

        $categories = get_terms( $params );
        if( !$categories ) {
            return;
        }

        //ob_start();
  
        echo '<div class="block">';
    
        echo '<ul class="filter-cats">';
        
        foreach( $categories as $cat ) {
            if( $cat->name === 'Uncategorized')
                continue;
            $sub_cats = get_terms(array(
                'taxonomy'      => 'product_cat',
                'hide_empty'    => false,
                'parent'        => $cat->term_id
            )); 
            $class = '';       
            $count = ' (' . $cat->count . ')';
            if(!empty($sub_cats)) {
                $class = 'class="have-childs"';
            }
            if( $class ) {
                
                echo '<li '. esc_attr($class) .' data-id="'. esc_attr($cat->term_id) . '"><a href="'. esc_url(get_term_link($cat->term_id)).  '">'.esc_html($cat->name . $count).'</a><span class="arrow-link"></span></li>';
    
            } else {
                echo '<li '. esc_attr($class) .' data-id="'. esc_attr($cat->term_id) . '"><a href="'. esc_url(get_term_link($cat->term_id)).  '">'.esc_html($cat->name . $count). '</a></li>';
            }
    
            if( !empty($sub_cats) ) {
                echo '<div class="sub-cats">';
                echo '<ul>';
                    foreach( $sub_cats as $sub_cat ) {
                        $_sub = $this->get_sub_cats_struct($sub_cat->term_id);
                        $count = ' (' . $sub_cat->count . ')';
   
                        
                        if( $_sub ) {
                            echo '<li data-id="'. esc_attr($sub_cat->term_id) . '"><a href="'. esc_url(get_term_link($sub_cat->term_id)).  '">'.esc_html($sub_cat->name . $count). '</a><span class="arrow-link"></span></li>';
                            echo '<div class="sub-cats">';
                            echo '<ul>';
                                foreach( $_sub as $item ) {
                                    $__sub = $this->get_sub_cats_struct($item->term_id);
                                    $count = ' (' . $item->count . ')';
                                    if( $__sub ) {
                                        echo '<li data-id="'. esc_attr($item->term_id) . '"><a href="'. esc_url(get_term_link($item->term_id)).  '">'.esc_html($item->name . $count).'</a><span class="arrow-link"></span></li>';
                                        echo '<div class="sub-cats">';
                                        echo '<ul>';
                                            foreach( $__sub as $__item ) { 
                                                echo '<li data-id="'. esc_attr($__item->term_id) . '"><a href="'. esc_url(get_term_link($__item->term_id)).  '">'.esc_html($__item->name) . '</a></li>'; 
                                            }
                                        echo '</ul>';
                                        echo '</div>';
                                    } else {
                                        echo '<li data-id="'. esc_attr($item->term_id) . '"><a href="'. esc_url(get_term_link($item->term_id)).  '">'.esc_html($item->name .$count).'</a></li>';                                    
                                    }
                                    
                                }
                            echo '</ul>';
                            echo '</div>';
                        } else {
                            echo '<li data-id="'. esc_attr($sub_cat->term_id) . '"><a href="'. esc_url(get_term_link($sub_cat->term_id)).  '">'.esc_html($sub_cat->name .  $count).'</a></li>';
                        }
                        
                    }
                echo '</ul>';
                echo '</div>';
            }
        }
    
        echo '</ul>';
        echo '</div>';

        //return ob_get_clean();

    }


    public function get_sub_cats_struct( $s_cat_id ) {
        $list = [];
    
        $sub_sub_cats = get_terms(array(
            'taxonomy'      => 'product_cat',
            'hide_empty'    => false,
            'parent'        => $s_cat_id
        )); 
    
        if( empty( $sub_sub_cats )) {
            return false;
        }
    
        foreach( $sub_sub_cats as $sub ) {
            $list[] = $sub;
        }
        return $list;
    }    

    public function render_filter_callback( $params ) {


        $attribute_taxonomies = $params['taxonomy'];
        $filters_order = explode(',', FWF_Options::$filter_orders);

        // if no taxonomies are selected then return
        if( empty( $filters_order[0]) ) {
            echo esc_html(__('Please add taxonomies/attributes to filter.', 'fast-products-filter'));
            return;
        }

        include( FWF_PLUGIN_PATH . '/templates/template-simple-filters.php');

        $this->output_mobile_menu();
        return;
    }

    public function render_mobile_filter_callback( $params ) {

        $attribute_taxonomies = $params['taxonomy'];
        $filters_order = explode(',', FWF_Options::$filter_orders);

        include( FWF_PLUGIN_PATH . '/templates/template-mobile-filters.php');
        return;
    }

    public function categories_filter() {

        $args = array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'parent'    => 0
        );

        $this->categories_filter_callback( $args );

    } // categories filter    

    public function price_slider() { 
        ?>
        <div id="price-slider">
            <h5><?php esc_html_e('Price Filter', 'fast-products-filter')  ?></h5>
            <div id="slider"></div>
            <div class="slider-output">
                <div id="min"></div>
                <div id="max"></div>
            </div>
        </div>
        <?php
    }

    public function mobile_price_slider() { 
        ?>
        <div id="mobile-price-slider">
            <h5><?php esc_html_e('Price Filter', 'fast-products-filter')  ?></h5>
            <div id="mobile-slider"></div>
            <div class="slider-output">
                <div id="mobile-min"></div>
                <div id="mobile-max"></div>
            </div>
        </div>
        <?php
    }

    public function mobile_filter_button() { ?>
        <div id="filter-mobile-btn-wrapper">
        <a class="btn-filters" href="#">Filters<svg data-name="Layer 3" id="Layer_3" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><line class="cls-1" x1="15" x2="26" y1="9" y2="9"></line><line class="cls-1" x1="6" x2="9" y1="9" y2="9"></line><line class="cls-1" x1="23" x2="26" y1="16" y2="16"></line><line class="cls-1" x1="6" x2="17" y1="16" y2="16"></line><line class="cls-1" x1="17" x2="26" y1="23" y2="23"></line><line class="cls-1" x1="6" x2="11" y1="23" y2="23"></line><path class="cls-2" d="M14.5,8.92A2.6,2.6,0,0,1,12,11.5,2.6,2.6,0,0,1,9.5,8.92a2.5,2.5,0,0,1,5,0Z"></path><path class="cls-2" d="M22.5,15.92a2.5,2.5,0,1,1-5,0,2.5,2.5,0,0,1,5,0Z"></path><path class="cls-3" d="M21,16a1,1,0,1,1-2,0,1,1,0,0,1,2,0Z"></path><path class="cls-2" d="M16.5,22.92A2.6,2.6,0,0,1,14,25.5a2.6,2.6,0,0,1-2.5-2.58,2.5,2.5,0,0,1,5,0Z"></path></svg></a>
        </div>
        <?php

        remove_action('woocommerce_before_shop_loop', 'output_mobile_menu', 1);
    }

    public function output_mobile_menu() {
        global $wpdb;

        echo '<div id="mobile-filters-wrap" class="fancy-scroll">';

           
        $attribute_taxonomies = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_name != '' ORDER BY attribute_name ASC;" );
        $attribute_taxonomies = array_filter( $attribute_taxonomies  ) ;

        $template = 'standard';
        $args = array(
            'taxonomy'  => $attribute_taxonomies,
            'template'  => $template
        );

        $this->render_mobile_filter_callback($args);
        echo  '</div>';
    }


}

new Filter_Frontend;