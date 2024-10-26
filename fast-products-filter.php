<?php
/**
 *
 *
 * @wordpress-plugin
 * Plugin Name:       Fast Products Filter
 * Plugin URI:        https://wpcoding.nl
 * Description:       Fast products filter helps shop visitors to quickly find products by categories, brands, colors, prices range and more. Fast Products Filter is fully optimized for speed and works seamlessly with any WooCommerce store, delivering a flawless filtering experience even with large product inventories.
 * Version:           1.7
 * Author:            Hafeez Ansari
 * Author URI:        https://www.linkedin.com/in/ansaripk/
 * License:           GPLv3 or later
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       fast-products-filter
 * Domain Path:       /languages/
 * 
 * 
 * 
 */


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}



 define( 'FWF_VERSION', '1.7' );
 define( 'FWF_PLUGIN_URI', plugin_dir_url( __FILE__ ) );
 define( 'FWF_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

 define( 'FWF_PLUGIN_INCLUDE_PATH', trailingslashit( plugin_dir_path( __FILE__ ) . 'includes' ) );
 define( 'FWF_PLUGIN_TEMPLATES_PATH', trailingslashit( plugin_dir_path( __FILE__ ) . 'templates' ) );
 define( 'FWF_PLUGIN_TEMPLATES_URI', trailingslashit( plugin_dir_url( __FILE__ ) . 'templates' ) );

 define( 'FWF_PLUGIN_DIRNAME', dirname( plugin_basename( __FILE__ ) ) );
 define( 'FWF_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
 define( 'FWF_PLUGIN_FILE', __FILE__ );
 define( 'FWF_IMAGES_URI', trailingslashit( plugin_dir_url( __FILE__ ) . 'images' ) );
 define( 'FWF_ASSETS_URI', trailingslashit( plugin_dir_url( __FILE__ ) . 'assets' ) );



/**
 * The code that runs during plugin activation.
 * This action is documented in includes/fwf-plugin-name-activator.php
 */
function activate_ajax_filter() {
	require FWF_PLUGIN_PATH . 'includes/fwf-plugin-activator.php';
	FWF_Plugin::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
/*
function deactivate_ajax_filter() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/ajax-filter-deactivator.php';
	Plugin_Name_Deactivator::deactivate();
}
	*/

//register_activation_hook( __FILE__, 'activate_ajax_filter' );
//register_deactivation_hook( __FILE__, 'deactivate_ajax_filter' );

require FWF_PLUGIN_PATH . 'includes/class-helper-functions.php';
require FWF_PLUGIN_PATH . 'includes/class-settings-sorting.php';
require FWF_PLUGIN_PATH . 'includes/class-fwf-options.php';
require FWF_PLUGIN_PATH . 'includes/class-filters-frontend.php';
require FWF_PLUGIN_PATH . 'includes/class-ajax-filter.php';
require FWF_PLUGIN_PATH . 'includes/plugin-options.php';



new Fast_Ajax_Filter;