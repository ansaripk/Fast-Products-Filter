<?php

class Plugin_Options {

    private $attributes; 
    private $slug;
    private $sections;
    private $options_group;

    // options pages objects
    private $sort;
    private $style;
    private $query;
    private $license;

    public function __construct() {
        
        $this->options_group = 'fwf_fast_products_filters_settings';
        $this->slug = 'fwf_fast_products_filters';
        $this->attributes = FWF_Helper::get_attributes_tax();
        $this->sections = $this->get_sections();

        add_action( 'admin_menu', [$this, 'fwf_admin_menu'] );
        add_action( 'admin_init', [$this, 'setup_sections'] );
        add_action( 'admin_init', [$this, 'setup_fields' ] );
        add_action( 'admin_init', [$this, 'setup_pages' ] );
    }

    public function setup_pages() {
        $this->sort = new Settings_Sorting;
        $this->sort->setup_fields();
    }

    public function get_sections() {
        $attr = FWF_Helper::get_attributes();
        $sections = [];
        foreach( $attr as $at ) {
            $sections[] = $at['label'];
        }            
        return $sections;
    }

    public function fwf_admin_menu() {
        add_menu_page(
            __('Products Filters', 'fast-products-filter'), // page <title>Title</title>
            __('Products Filters', 'fast-products-filter'), // link text
            'manage_options', // user capabilities
            $this->slug, // page slug
            [$this,'ajax_filters_render'], // this function prints the page content
            'dashicons-image-filter', // icon (from Dashicons for example)
            //4 // menu position
        );

        add_submenu_page( 
            $this->slug,
            __('FIlters Order', 'fast-products-filter'), 
            __('FIlters Order', 'fast-products-filter'), 
            'manage_options', 
            $this->slug . '&tab=filter_settings', 
            [$this, 'submenu_callback'] 
        );

        add_submenu_page( 
            $this->slug,
            __('FIlters Style', 'fast-products-filter'), 
            __('FIlters Style', 'fast-products-filter'), 
            'manage_options', 
            $this->slug . '&tab=filter_styles', 
            [$this, 'submenu_callback'] 
        );

        add_submenu_page( 
            $this->slug,
            __('FIlters Query', 'fast-products-filter'), 
            __('FIlters Query', 'fast-products-filter'), 
            'manage_options', 
            $this->slug . '&tab=filter_query', 
            [$this, 'submenu_callback'] 
        );


    }

    public function submenu_callback() {
    }

    public function ajax_filters_render() {       
        ?>

		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ) ?></h1>
            <?php settings_errors(); ?>
            <?php
                $active_tab = 'attributes_settings';

                if ( isset( $_GET['tab'] ) ) {
                    $active_tab = $_GET['tab'];
                } 
            ?>
        
            <h2 class="nav-tab-wrapper">
                <a href="?page=<?php echo esc_attr($_GET['page']); ?>&tab=attributes_settings" class="nav-tab <?php echo $active_tab == 'attributes_settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Attributes Settings', 'fast-products-filter') ?></a>
                <a href="?page=<?php echo esc_attr($_GET['page']); ?>&tab=filter_settings" class="nav-tab <?php echo $active_tab == 'filter_settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Filters Order', 'fast-products-filter') ?></a>
                <a href="?page=<?php echo esc_attr($_GET['page']); ?>&tab=filter_styles" class="nav-tab <?php echo $active_tab == 'filter_styles' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Filters Styles', 'fast-products-filter') ?></a>
                <a href="?page=<?php echo esc_attr($_GET['page']); ?>&tab=filter_query" class="nav-tab <?php echo $active_tab == 'filter_query' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Filters Query', 'fast-products-filter') ?></a> 
                <a href="?page=<?php echo esc_attr($_GET['page']); ?>&tab=filter_tutorial" class="nav-tab <?php echo $active_tab == 'filter_tutorial' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Tutorial', 'fast-products-filter') ?></a> 
            </h2>

            <br><br>

			<form method="post" action="options.php">
				<?php
                    switch( $active_tab ) {
                        case 'attributes_settings':
                            echo '<h3>'. esc_html__('Select attributes to include in filters.','fast-products-filter') .'</h3>';
                            settings_fields( 'fwf_fast_products_filters_settings' ); 
                            do_settings_sections( 'fwf_fast_products_filters' ); 
                            submit_button(); 
                            break;

                        case 'filter_settings':
                            $this->sort->render_page();
                            break;

                        case 'filter_styles':
                            echo '<p class="pro-info">PRO version only <a class="get-pro" href="https://demo1.wpcoding.nl/contact/" target="_blank"> Get Pro Version</a></p>';
                            echo '<div class="styles-page"></div>';
                            break;

                        case 'filter_query':
                            echo '<p class="pro-info">PRO version only <a class="get-pro" href="https://demo1.wpcoding.nl/contact/" target="_blank"> Get Pro Version</a></p>';
                            echo '<div class="query-page"></div>';
                            break;

                        case 'filter_tutorial':
                            echo '<div class="tutorial">';
                            echo '<p>Check online tutorial for this plugin. <a href="https://filters.wpcoding.nl/docs/" target="_blank">Goto Tutorial</a></p>';
                            echo '</div>';
                        
                    }
				?>
			</form>
		</div>
	<?php
    }

    public function setup_sections() {

        $attributes = FWF_Helper::get_attributes();

        foreach( $attributes as $section ) {
            $section_id = $section['id'];
            $section_title = $section['label'];
            add_settings_section( 
                $section_id, 
                $section_title,
                [$this, 'section_callback'], 
                $this->slug 
            );
        }

        add_settings_section( 
            'fwf_categories_section', 
            __('Categories Section', 'fast-products-filter'), 
            [$this, 'section_callback'], 
            $this->slug 
        );
    }


    public function section_callback( $arguments ) {

    }

    public function setup_fields() {

        if( !FWF_Helper::is_woocommerce_active() ) {
            return;
        }

        $_fields = [];

        $attributes = FWF_Helper::get_attributes();

        foreach( $attributes as $section ) {
            $section_id = $section['id'];
            foreach( $section['terms'] as $term ) {
                $_fields[] = [
                    'uid'       => $term->slug,
                    'label'     => $term->name,
                    'section'   => $section_id,
                    'type'      => 'checkbox',
                    'default'   => ''
                ];
            }

        }

        foreach( $_fields as $field ){ 
            add_settings_field( 
                $field['uid'], 
                $field['label'], 
                [$this, 'field_callback'], 
                $this->slug, 
                $field['section'], 
                $field 
            );
            register_setting( $this->options_group, $field['uid'] );
        }


        add_settings_field( 
            'fwf_categories', 
            __('Categories', 'fast-products-filter'), 
            [$this, 'field_callback'], 
            $this->slug, 
            'fwf_categories_section', 
            array(
                'uid'   => 'fwf_categories',
                'label' =>  __('Include Categories', 'fast-products-filter'),
                'type'  => 'checkbox',
                'default'  => ''
            )
        );
        register_setting( $this->options_group, 'fwf_categories' );

    }


    public function field_callback( $arguments ) {

        $value = get_option( $arguments['uid'] ); // Get the current value, if there is one
        if( ! $value ) { // If no value exists
            $value = $arguments['default']; // Set to our default
        }


        if( $arguments['type'] == 'checkbox' ) {
            echo '<label>';
			echo '<input type="checkbox" name="'. esc_attr($arguments['uid']).'"' . checked('on',$value, false) .' />';
		    echo '</label>';
        }
       
    }


    function sanitize_checkbox( $value ) {
        return 'on' == $value ? 'yes' : 'no';
    }

}

new Plugin_Options;