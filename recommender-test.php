<?php
/**
 * @package Recommender Test
 * @version 0.1
 */
/*
Plugin Name: Recommender Test
Plugin URI: 
Description: A tool for testing the quality of the plugin recommender engine.
Author: Tellyworth
Version: 0.1
Author URI: http://flightpathblog.com
License: GPLv2
*/


add_action( 'admin_menu', 'akismet_unit_test_page' );
function akismet_unit_test_page() {
	if ( function_exists('add_submenu_page') )
		add_submenu_page('plugins.php', __('Recommender Test'), __('Recommender Test'), 'manage_options', 'recommender-test', 'recommender_test');
}


add_action( 'admin_enqueue_scripts', 'recommender_test_admin_stylesheet' );
function recommender_test_admin_stylesheet( $page ) {
    if( 'plugins_page_recommender-test' != $page )
    {
         return;
    }
    wp_enqueue_style( 'prefix-style', plugins_url('admin.css', __FILE__) );
}

function recommender_test() {
	
		require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
		require_once(ABSPATH . 'wp-admin/includes/class-wp-plugin-install-list-table.php');

		// We're extending the list table because it contains protected methods that are handy.
		class Recommender_Test_Table extends WP_Plugin_Install_List_Table {
			var $type = 'popular';
			
			function __construct( $type = 'popular' ) {
				$this->type = $type;
				
				parent::__construct();
			}
			
			public function get_installed_plugin_slugs() {
				return parent::get_installed_plugin_slugs();
			}
			
			// Override the parent with a greatly simplified version
			public function prepare_items() {
				$args = array(
					'page' => $this->get_pagenum(),
					'per_page' => 50,
					'fields' => array( 'last_updated' => true, 'downloaded' => true, 'icons' => true ),
					// Send the locale and installed plugin slugs to the API so it can provide context-sensitive results.
					'locale' => get_locale(),
					'browse' => 'popular',
				);
				
				if ( $this->type === 'recommended' )
					$args[ 'installed_plugins' ] = $this->get_installed_plugin_slugs();

				$api = plugins_api( 'query_plugins', $args );
#				var_dump( $args, $api );
		
				if ( is_wp_error( $api ) ) {
					$this->error = $api;
					return;
				}

				$this->items = $api->plugins;

				$this->set_pagination_args( array(
					'total_items' => $api->info['results'],
					'per_page' => $args['per_page'],
				) );

			}
		}
	
?>
<div class="wrap">
<h2><?php _e('Plugin Recommendations'); ?></h2>
<div class="widefat">
<?php

	$installed_plugins = array();
	if ( trim( $_POST['plugins'] ) )
		$installed_plugins = preg_split( "\w+", $_POST['plugins'] );
	else
		$installed_plugins = Recommender_Test_Table::get_installed_plugin_slugs();
		

	echo '<form method="post">';
	echo '<textarea name="plugins" style="width: 100%">';
	echo esc_html( join( " ", $installed_plugins ) );
	echo '</textarea>';
	echo '<input type="submit" name="go" value="Recommend" />';
	echo '</form>';

	echo '<div style="width: 45%; float: left; margin: 12px">';
	echo '<h3>Popular:</h3>';
	
	$table = new Recommender_Test_Table( 'popular' );
	$table->prepare_items();
	$table->display();
	
	echo '</div>';


	echo '<div style="width: 45%; float: left; margin: 12px">';
	echo '<h3>Recommended:</h3>';
	
	$table = new Recommender_Test_Table( 'recommended' );
	$table->prepare_items();
	$table->display();
	
	echo '</div>';


?>
</div>
</div>
<?php

}