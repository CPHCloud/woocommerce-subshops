<?php

/**
* 
*/
class wss_admin extends wss_init {
	
	/**
	 * Inits the admin parts of the plugin
	 *
	 * @return void
	 **/
	public static function init(){

		/* Sets up the admin screens and options */
		add_action('init', array('wss_admin', 'setup_admin'), 1);

		/* Hook the wss_in_shops field for products */
		add_filter('acf/load_field/name=wss_in_shops', array('wss_admin', 'alter_field_in_shops'), 999, 3);

		/* Add shop field to order */
		add_filter('woocommerce_create_order', array('wss_admin', 'order_add_subshop_field'), 999, 1);

		/* Update the order meta with shop value */
		add_action('woocommerce_checkout_update_order_meta', array('wss_admin', 'add_subshop_to_order'));


	}	

	/**
	 * Add the woo_subshop meta value to the order
	 *
	 * @param $order_id (string) - the order ID
	 **/
	function add_subshop_to_order($order_id){
		if($shop = self::get_current_shop()){
			if(!update_post_meta($order_id, 'woo_subshop', $shop->ID)){
				self::log('Could not update the \'woo_subshop\' meta key for order with ID '.$order_id);
			}
		}
	}


	/**
	 * Sets up all admin functionality for this plugin
	 *
	 * @return void
	 **/
	public static function setup_admin(){

		/* Create the options panel */
		acf_add_options_sub_page(array(
	        'title' 		=> 'Woocommerce Subshops',
	        'slug'			=> 'wss-options-general',
	        'parent' 		=> 'options-general.php',
	        'capability' 	=> 'manage_options'
	    ));

		/* Register all the fields! */
		self::register_acf_fields();

		/* Get published subshops */
		$shops = get_posts(array(
			'post_type' 	=> 'woo_subshop',
			'post_status' 	=> 'publish',
			'posts_per_page'=> -1
		));

		/* Register menues for each subshop */
		foreach($shops as $shop)
			register_nav_menu('wss-'.$shop->post_name.'-main', 'Main menu for subshop '.$shop->post_title);

	}


	/**
	 * Registers the neccesary ACF field groups and fields
	 *
	 * @return void
	 **/
	public static function register_acf_fields(){

		if(WOO_SUBSHOPS_DEV){
			self::export_acf_fields();
		}
		else{
			self::include_acf_fields();
		}

	}


	/**
	 * Includes the fields needed by the admin area from the register-acf-fields.
	 *
	 * @return void
	 **/
	private static function include_acf_fields(){
		
		if(file_exists(self::dir().'/inc/register-acf-fields.php'))
			require(self::dir().'/inc/register-acf-fields.php');

	}


	/**
	 * This function is used in development and will output all the
	 * needed fields
	 *
	 * TODO  	This method currently only works for one developer
	 *			A method for collectiong the fields across multiple
	 *			developers and their environments need to be implemented.
	 *
	 * @return void
	 **/
	private static function export_acf_fields(){
		
		/* The path to the file that registers the fields */
		$file 	  = self::dir().'/inc/register-acf-fields.php';

		/* The arguments to get all ACF fields */
		$get_acfs 	= array('wss:options', 'wss:pages', 'wss:subshop', 'wss:product');
		$acfs 		= array();
		foreach($get_acfs as $get){
			if($p = get_page_by_title($get, OBJECT, 'acf')){
				$acfs[] = $p;
			}
		}

		/* Get fields */
		if($acfs){

			/* 
			Fields where found.
			Now we need to get an array of their IDs
			*/
			foreach($acfs as &$acf){
				$acf = $acf->ID;
			}

			/* Require the export class of the ACF plugin if it's not present */
			if(!class_exists('acf_export')){
				require_once(self::dir().'/plugins/advanced-custom-fields/core/controllers/export.php');
			}

			/*
			This will fool the ACF exporter into believing that
			a POST request with the fields to export has been made.
			*/
			$_POST['acf_posts'] = $acfs;
			
			/* New export object */
			$export = new acf_export();

			/*
			The html_php method outputs the needed html for the wp-admin
			area. We capture that with ob_start and split it by html tags
			in order to find the value of the textarea that holds the PHP
			code we need. Dirty dirty dirty.
			*/
			ini_set('display_errors', 'Off');
			$buffer = ob_start();
			$export->html_php();
			
			unset($_POST['acf_posts']);

			$contents = ob_get_contents();
			ob_end_clean();

			$contents = preg_split('~readonly="true">~', $contents);
			$contents = preg_split('~</textarea>~', $contents[1]);
			$contents = '<?php '.$contents[0].' ?>';

			/* Write the contents to the file */
			$file = fopen($file, 'w+');
			fwrite($file, $contents);
			fclose($file);
		}
	}



	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function alter_field_in_shops($field){

		if($shops = self::get(array('posts_per_page' => -1))){
			foreach($shops as $shop)
				$field['choices'][$shop->ID] = $shop->post_title;
		}
		return $field;
	}

}

?>