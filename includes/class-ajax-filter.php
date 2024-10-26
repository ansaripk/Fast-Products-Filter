<?php

class Fast_Ajax_Filter {

    public $plugin_slug;
    public $version;
    public $cache_key;
    public $cache_allowed;

    private $labels = [];
    private $accordion = false;
    private $relation = '';
    private $number_posts_per_page = 12;

    public function __construct() {

        $this->plugin_slug = plugin_basename( __DIR__ );
        $this->version = FWF_VERSION;
        $this->cache_key = 'fwf_custom_upd';
        $this->cache_allowed = false;

        $this->accordion = FWF_Options::$accordion; 
        $this->relation = FWF_Options::$query_type; 
        $this->number_posts_per_page = FWF_Options::$fwf_posts_per_page; 
        $this->register_hooks();
        
    }

    public function register_hooks() {
        
        add_action( 'init', [$this,'fwf_load_textdomain'] );
        add_action( 'admin_enqueue_scripts', [$this, 'load_custom_wp_admin_style']);        
        add_action( 'wp_enqueue_scripts', array($this,'ajax_filter_enqueue_script'));
        add_action( "wp_ajax_do_filter", array($this, 'do_ajax_query') );
        add_action( "wp_ajax_nopriv_do_filter", array($this, 'do_ajax_query') );
        add_action( 'pre_get_posts', array($this, 'set_query'), 30 );
        add_action( 'elementor/widgets/register', [$this, 'elementor_filter_widget'], 99);
        add_filter( 'af_query_no_args', [$this, 'query_no_args_callback'], 10, 1);
        add_filter( 'af_query_with_args', [$this, 'query_with_args_callback'], 10, 2);
        add_filter( 'woocommerce_locate_template', [$this,'override_woocommerce_template'], 10, 3 );
        add_action( 'wp_ajax_nopriv_leo_loadmore', [$this, 'leo_laodmore_callback'] );
        add_filter( 'fwf_pagination', [$this, 'fwf_pagination_callback'], 10, 1);
        add_action( 'admin_notices', [$this, 'wc_requirement_notice']);
        add_action( 'print_price_slider', [$this,'print_price_slider'] );
        add_shortcode( 'fast_products_filter', array($this,'ajax_filter'));

    }

    public function wc_requirement_notice() {

        if( !FWF_Helper::is_woocommerce_active() ) {
            $class = 'notice notice-error';
            $text    = esc_html__( 'WooCommerce', 'fast-products-filter' );
            $message = wp_kses( __( "<strong>Fast Products Filter</strong> is an add-on of ", 'fast-products-filter' ), array( 'strong' => array() ) );
            $line = __( 'Please install WooCommerce and then install this plugin.', 'fast-products-filter');

            printf( '<div class="%1$s"><p>%2$s <strong>%3$s</strong></p><p>%4$s</p></div>', 
            esc_attr($class), wp_kses($message), esc_html($text), esc_html($line) );
            deactivate_plugins('fast-products-filter/fast-products-filter.php');
        }
    }

    
    public function fwf_pagination_callback( $links ) {

        if( is_array( $links) ) {
            ob_start();

            $allowed_tags = array(
                //formatting
                'strong' => array(),
                'em'     => array(),
                'b'      => array(),
                'i'      => array(),
            
                //links
                'a'     => array(
                    'class' => array(),
                    'href' => array()
                ),
                'span'    => array(
                    'area-current'  => array()
                )
            );
    
            echo '<ul>';
            $number = 0;
            foreach ( $links as $page ) {

                if( strpos($page, '/span') ) {
                    preg_match('#<span[^<>]*>([\d,]+).*?</span>#',$page,$matches);
                    $number = isset($matches[1]) ? $matches[1] : '';
                } elseif( strpos($page, '/a') ) {
                    preg_match('#<a[^<>]*>([\d,]+).*?</a>#',$page,$matches);
                    $number = isset($matches[1]) ? $matches[1] : '';
                } 
    
                if(empty($matches)) {

                    preg_match('/href=["\']?([^"\'>]+)["\']?/', $page, $matches);
                    //$number = isset($matches[1]) ? $matches[1] : '';
                    $url = explode('?', $matches[1]);
                    $is_shop = (basename($url[0]) == 'shop' ) ? '1' : basename($url[0]);
                    echo '<li data-pagenum="'. esc_attr($is_shop) . '">'. wp_kses($page, $allowed_tags) . '</li>';
                    continue;
                }
                    
                echo '<li data-pagenum="'. esc_attr($number) . '">'. wp_kses($page, $allowed_tags) . '</li>';
            }
            echo '</ul>';
            $navi = ob_get_clean();
            return $navi;
        }
    }
    


    public function elementor_filter_widget( $widgets_manager ) {
        require_once( dirname(plugin_dir_path( __FILE__ )) . '/widgets/elementor-filter-widget.php' );
        $widgets_manager->register( new \Elementor_Filter_Widget() );
    }

    public function load_custom_wp_admin_style(){
        wp_register_style( 'jquery-ui-theme', dirname(plugin_dir_url( __FILE__ )) . '/assets/css/jquery-ui.css');
        wp_enqueue_style( 'jquery-ui-theme' );
        wp_register_style( 'af_admin_css', dirname(plugin_dir_url( __FILE__ )) . '/assets/css/admin-styles.css', false, '1.0.0' );
        wp_enqueue_style( 'af_admin_css' );
        wp_enqueue_script( 'jquery-ui-sortable' );
    }

    public function ajax_filter_enqueue_script() {
        
            wp_enqueue_script('jquery');
            wp_enqueue_script( 'noui', dirname(plugin_dir_url( __FILE__ )) . '/assets/js/nouislider.min.js' );
            wp_enqueue_script( 'bootstrap5', dirname(plugin_dir_url( __FILE__ )) . '/assets/js/bootstrap.bundle.min.js' );
            wp_enqueue_script( 'fwf_custom', dirname(plugin_dir_url( __FILE__ )) . '/assets/js/fwf-custom.js' );
            wp_localize_script( 
                'fwf_custom', 
                'ajax_object', 
                array( 
                    'ajaxurl'   => admin_url( 'admin-ajax.php' ),
                    'labels'    => wp_json_encode(FWF_Helper::get_attributes_tax()),
                    'slider'    => $this->price_included(),
                    'min_price' => FWF_Options::$min_price,
                    'max_price' => FWF_Options::$max_price,
                    'nonce'     => wp_create_nonce('fast-products-filter-nonce')
                ) 
            );
            wp_register_style( 'noui', dirname(plugin_dir_url( __FILE__ )) . '/assets/css/nouislider.min.css');
            wp_register_style( 'bootstrap5', dirname(plugin_dir_url( __FILE__ )) . '/assets/css/bootstrap.min.css');
            wp_register_style( 'fwf_styles', dirname(plugin_dir_url( __FILE__ )) . '/assets/css/styles.css', false, '1.0.0' );
            wp_enqueue_style( 'noui' );
            wp_enqueue_style( 'bootstrap5' );
            wp_enqueue_style( 'fwf_styles' );
    }

    
    /**
     * Load textdomain.  
     */
    public function fwf_load_textdomain() {
        load_plugin_textdomain( 'fast-products-filter', false, FWF_PLUGIN_DIRNAME . '/languages' );
    }

    public function set_query( $query ) {

        $_query = [];
        $_prices = [];

        $tax_query = $query->get( 'tax_query' );
        if ( ! $tax_query ) {
            $tax_query = [];
        }         

        $meta_query = $query->get( 'meta_query' );
        if ( ! $meta_query ) {
            $meta_query = [];
        } 

        if ( ! is_admin() && is_post_type_archive( 'product' ) && $query->is_main_query() ) {

            $attributes = FWF_Helper::get_attributes_tax();

            $_atttributes = [];
            foreach( $attributes as $item ) {
                $_atttributes[] = $item->attribute_name;
            }

            $_atttributes[] = 'cats';
            $_atttributes[] = 'min_price';
            $_atttributes[] = 'max_price';

            $q_attrib = [];
            $tax = '';



            foreach( $_atttributes as $item ) {
                if( isset($_GET[$item] )) {
                    if( $item == 'cats' ) {
                        $tax = 'product_cat';
                        $q_attrib[] = array(
                            'type'      => 'taxonomy',
                            'name'      => sanitize_text_field($item),
                            'taxonomy'  => $tax,
                            'value'     => explode(',', sanitize_text_field($_GET[$item]))
                        );
                        continue;                        
                    }

                    if( $item != 'min_price' && $item != 'max_price' ) {
                        $tax = 'pa_'.$item;
                        $q_attrib[] = array(
                            'type'      => 'taxonomy',
                            'name'      => $item,
                            'taxonomy'  => $tax,
                            'value'     => explode(',', $_GET[$item])
                        );
                        continue;                        
                    }

                    if( $item == 'min_price' || $item == 'max_price' ) {
                        $_prices[] = $_GET[$item];
                    }
                    
                }
            }


            foreach( $q_attrib as $item ) {
                if( $item['type'] == 'taxonomy' ) {
                    if( $item['taxonomy'] == 'product_cat' ) {
                        $_query['relation'] = 'AND';
                        $_query[] = array(
                            'taxonomy'  => $item['taxonomy'],
                            'field'     => 'id',
                            'terms'     => $item['value'],
                            'operator'  => 'IN'
                        );
                    } else {
                        $_query['relation'] = 'AND';
                        $_query[] = array(
                            'taxonomy'  => $item['taxonomy'],
                            'field'     => 'slug',
                            'terms'     => $item['value'],
                            'operator'  => 'IN'
                        );
                    }

                    $tax_query[] = $_query;
                } 
            }

            if( !empty($_prices) ) {
                $_query['relation'] = 'AND';
                $_query[] = array(
                    array(
                        'key' => '_price',
                        'value' => $_prices,
                        'compare' => 'BETWEEN',
                        'type' => 'NUMERIC'
                    )
                );

                $meta_query[] = $_query;
            }

            $query->set( 'tax_query', $tax_query );  
            $query->set( 'meta_query', $meta_query );
        }
        $query->set('posts_per_page', $this->number_posts_per_page);
        $query->set( 'orderby', 'date' );
        $query->set( 'order', 'DESC' );

    }

    public function do_ajax_query() {

        $args = wp_unslash($_POST['query']);
        $page = wp_unslash($_POST['page_num']);
        $nonce = wp_unslash($_POST['nonce']);
        if ( !wp_verify_nonce( $nonce, 'fast-products-filter-nonce' ) ) { 
            echo wp_json_encode(['status'  => 'error', 'message' => 'unauthorized ajax call.']);
            die;

        }
        $_args = $tax_query = $meta_query = $prices = [];
        $posts_html = '';
        $pagination = '';


        if( empty($args) ) {
            $result = apply_filters('af_query_no_args', $page);
            echo wp_json_encode($result);
            die;
        }

        foreach( $args as $arg ) {
            $temp = explode('=', $arg);
            $_args[] = array(
                'name'  => $temp[0],
                'value' => $temp[1]
            );
        }
        
        $attributes = FWF_Helper::get_attributes_tax();
        $_atttributes = [];
        foreach( $attributes as $item ) {
            $_atttributes[] = $item->attribute_name;
        }

        $_atttributes[] = 'cats';
        $_atttributes[] = 'min_price';
        $_atttributes[] = 'max_price';

        $found = [];

        foreach( $_atttributes as $attrib ) {

            foreach( $_args as $arg ) {
                if( $arg['name'] == $attrib && $arg['name'] == 'min_price' ) {
                    $prices[] = $arg['value'];
                    continue;
                }
                if( $arg['name'] == $attrib && $arg['name'] == 'max_price' ) {
                    $prices[] = $arg['value'];
                    continue;
                }

                if( $arg['name'] == $attrib ) {
                    if( $arg['name'] == 'cats') { // product categories
                        $slugs = [];
                        $arg_array = explode(',', $arg['value']);
                        $tax_query['relation'] = $this->relation;
                        $tax_query[] = array(
                            'taxonomy'  => 'product_cat',
                            'field'     => 'id',
                            'terms'     => $arg_array,
                            'operator'  => 'IN'
                        );

                    } else { // attributes
                        $arg_array = explode(',', $arg['value']);
                        $tax_query['relation'] = $this->relation;
                        $tax_query[] = array(
                            'taxonomy'  => 'pa_'.$arg['name'],
                            'field'     => 'slug',
                            'terms'     => $arg_array,
                            'operator'  => 'IN'
                        );
                    }
                }
            }
        }

        if( !empty($prices) ) {
            $meta_query[] = array(
                array(
                    'key' => '_price',
                    'value' => $prices,
                    'compare' => 'BETWEEN',
                    'type' => 'NUMERIC'
                )
            );
        }

        $loop = new WP_Query( array(
            'post_type'         => ['product'],
            'post_status'       => 'publish',
            'posts_per_page'    => $this->number_posts_per_page,
            'paged'             => $page,
            'tax_query'         => $tax_query,
            'meta_query'        => $meta_query
        ));

        $result = apply_filters('af_query_with_args', $loop, $page);
        echo wp_json_encode( $result );
        die;
    }

    public function price_included() {

        $filters_order = explode(',', FWF_Options::$filter_orders);
        $include = in_array("price", $filters_order) ? 'yes' : 'no';
        return $include;
    }

    public function get_attribute_name( $obj ) {
        return $obj->attribute_name;
    }

    public function ajax_filter() {

        global $wpdb;

           
        $attribute_taxonomies = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_name != '' ORDER BY attribute_name ASC;" );
        $attribute_taxonomies = array_filter( $attribute_taxonomies  ) ;

        $template = ( $this->accordion == true ) ? 'accordion' : 'standard';
        $args = array(
            'taxonomy'  => $attribute_taxonomies,
            'template'  => $template
        );

        do_action('render_filter', $args);

    }

    public function print_price_slider() {
        $this->price_slider();
    }


    public function price_slider() { 
        ?>
        <div id="price-slider">
            <h5><?php echo esc_html(__('Price Filter', 'fast-products-filter')) ?></h5>
            <div id="slider"></div>
            <div class="slider-output">
                <div id="min"></div>
                <div id="max"></div>
            </div>
        </div>
        <?php
    }

    public function query_no_args_callback( $page ) {

        $posts_html = $pagination = '';
        $loadmore = [];

        $loop = new WP_Query( array(
            'post_type'         => ['product'],
            'post_status'       => 'publish',
            'posts_per_page'    => $this->number_posts_per_page,
            'paged' => $page
        ));

        if( $loop->have_posts() ) {
            ob_start(); 
    
           while( $loop->have_posts() ): $loop->the_post();
    
               wc_get_template_part( 'content', 'product' );
    
           endwhile;
           wp_reset_postdata();

           $posts_html = ob_get_contents(); // we pass the posts to variable
           ob_end_clean(); // clear the buffer

        } else {
            $posts_html = '<p>' . __('No posts found.', 'fast-products-filter') . '</p>';
        }      
                 


        if( $loop->max_num_pages > 1) {
            ob_start();

            $total   = $loop->max_num_pages;
            $current = $page;

            $base = wp_get_referer() . '%_%';
            $format = 'page/%#%';

            $args = array( // WPCS: XSS ok.
                'base'      => $base,
                'format'    => $format,
                'add_args'  => array(),
                'current'   => max( 1, $current ),
                'total'     => $total,
                'prev_text' => is_rtl() ? '&rarr;' : '&larr;',
                'next_text' => is_rtl() ? '&larr;' : '&rarr;',
                'type'      => 'array',
                'end_size'  => 3,
                'mid_size'  => 3,
            );

       
            $pagination = apply_filters('fwf_pagination', paginate_links($args));
            $loadmore = [ 'total' => $loop->max_num_pages, 'current' => $current+1];
            ob_end_clean(); // clear the buffer
       } else {
            $pagination = '';
            $loadmore = [ 'total' => $loop->max_num_pages, 'current' => $page+1];
            
       }

       $per_page = $this->number_posts_per_page;
       $total   = $loop->found_posts -1 ;
       $current = $page;
       $results_count = '';

       if ( 1 === intval( $total ) ) {
            $results_count = __( 'Showing the single result', 'fast-products-filter' );
        } elseif ( $total <= $per_page || -1 === $per_page ) {
            /* translators: %d: total results */
            $results_count = sprintf( _n( 'Showing all %d result', 'Showing all %d results', $total, 'fast-products-filter' ), $total );
        } else {
            $first = ( $per_page * $current ) - $per_page + 1;
            $last  = min( $total, $per_page * $current );
            /* translators: 1: first result 2: last result 3: total results */
            $results_count = sprintf( _nx( 'Showing %1$d&ndash;%2$d of %3$d result', 'Showing %1$d&ndash;%2$d of %3$d results', $total, 'with first and last result', 'fast-products-filter' ), $first, $last, $total );
        }

        return ['posts' => $posts_html, 'navi' => $pagination, 'count' => $results_count ];

    }


    public function query_with_args_callback($query, $page_num) {
        
        $posts_html = $pagination = '';
        $loadmore = [];

        if( $query->have_posts() ) {
            ob_start(); 
    
           while( $query->have_posts() ): $query->the_post();
    
               wc_get_template_part( 'content', 'product' );
    
           endwhile;
           wp_reset_postdata();
    
           $posts_html = ob_get_contents(); // we pass the posts to variable
           ob_end_clean(); // clear the buffer
        } else {
           $posts_html = __('no posts.', 'fast-products-filter');
        }

        if( $query->max_num_pages > 1 ) {

            $total   = $query->max_num_pages;
            $current = $page_num;

            $url_parts = explode('?', wp_get_referer());

            $base = $url_parts[0] . '%_%';
            $format = 'page/%#%' . '?'. $url_parts[1];


            $args = array( // WPCS: XSS ok.
                'base'      => $base,
                'format'    => $format,
                'add_args'  => array(),
                'current'   => max( 1, $current ),
                'total'     => $total,
                'prev_text' => is_rtl() ? '&rarr;' : '&larr;',
                'next_text' => is_rtl() ? '&larr;' : '&rarr;',
                'type'      => 'array',
                'end_size'  => 3,
                'mid_size'  => 3,
            );

            $pagination = apply_filters('fwf_pagination', paginate_links($args));
       }

       $per_page = $this->number_posts_per_page;
       $total   = $query->found_posts;
       $current = $page_num;
       $results_count = '';

       if ( 1 === intval( $total ) ) {
            $results_count = __( 'Showing the single result', 'fast-products-filter' );
        } elseif ( $total <= $per_page || -1 === $per_page ) {
            /* translators: %d: total results */
            $results_count = sprintf( _n( 'Showing all %d result', 'Showing all %d results', $total, 'fast-products-filter' ), $total );
        } else {
            $first = ( $per_page * $current ) - $per_page + 1;
            $last  = min( $total, $per_page * $current );
            /* translators: 1: first result 2: last result 3: total results */
            $results_count = sprintf( _nx( 'Showing %1$d&ndash;%2$d of %3$d result', 'Showing %1$d&ndash;%2$d of %3$d results', $total, 'with first and last result', 'fast-products-filter' ), $first, $last, $total );
        }

        return ['posts' => $posts_html, 'navi' => $pagination, 'count' => $results_count ];
    }

    public function override_woocommerce_template( $template, $template_name, $template_path ) {

        $template_directory = untrailingslashit( FWF_PLUGIN_PATH ) . '/woocommerce/';
        $path = $template_directory . $template_name;
        return file_exists( $path ) ? $path : $template;
    }


    
    public function leo_laodmore_callback() {
        $page = $query = $url = $posts_html = '';
        $tax_query = $meta_query = $params = $prices = [];
        $min_price = $max_price = 0;
    
   
        if( isset($_POST['page'])) {
            $page = $_POST['page'];
            $query = $_POST['query'];
            $url = $_POST['url'];
            $posts_per_page = $_POST['ppp'];

            $buff = $_POST['params'];
            $params = json_decode( html_entity_decode( stripslashes ($buff ) ) );

            foreach( $params as $item ) {
                if( $item->key == 'cats' ) {
                    $tax_query[] = array(
                        'taxonomy'  => 'product_cat',
                        'field'     => 'id',
                        'terms'     => explode(',',$item->value)
                    );
                    continue;
                }
                if( $item->key == 'min_price' ) {
                    $min_price = $item->value;
                    continue;
                }

                if( $item->key == 'max_price' ) {
                    $max_price = $item->value;
                    continue;
                }

                if( $item->key != 'min_price' && $item->key != 'max_price') {
                    $tax_query[] = array(
                        'taxonomy'  => 'pa_'.$item->key,
                        'field'     => 'slug',
                        'terms'     => explode(',',$item->value)
                    );
                }
            }

            if( $min_price ) {
                $prices[] = $min_price;
                $prices[] = $max_price;
            }

            if( !empty($prices) ) {
                $meta_query[] = array(
                    array(
                        'key' => '_price',
                        'value' => $prices,
                        'compare' => 'BETWEEN',
                        'type' => 'NUMERIC'
                    )
                );
            }

            if( $params ) {
                $query = new WP_Query( array(
                    'post_type'         => ['product'],
                    'post_status'       => 'publish',
                    'posts_per_page'    => $posts_per_page,
                    'paged'             => $page,
                    'tax_query'         => $tax_query,
                    'meta_query'        => $meta_query
                ));       
            }

            if( $params ) {
                $query = new WP_Query( array(
                    'post_type'         => ['product'],
                    'post_status'       => 'publish',
                    'posts_per_page'    => $posts_per_page,
                    'paged'             => $page,
                    'tax_query'         => $tax_query,
                    'meta_query'        => $meta_query
                ));       
            } else {
                $query = new WP_Query( array(
                    'post_type'         => ['product'],
                    'post_status'       => 'publish',
                    'posts_per_page'    => $posts_per_page,
                    'paged'             => $page,
                ));                       
            }

    
 
    
            if( $query->have_posts() ) {
                ob_start(); 
        
               while( $query->have_posts() ): $query->the_post();
        
                   wc_get_template_part( 'content', 'product' );
        
               endwhile;
               wp_reset_postdata();
        
               $posts_html = ob_get_contents(); // we pass the posts to variable
               ob_end_clean(); // clear the buffer
    
               $response = array(
                'max_pages'     => $query->max_num_pages,
                'next_page'     => $page + 1,
                'posts'         => $posts_html
               );
               $page = 1;
               echo wp_json_encode($response);
               die;
            }
            
        }
        die;
    }    

}