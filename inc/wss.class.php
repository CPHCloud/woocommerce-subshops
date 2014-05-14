<?php

/*
*********************************
wss class
*********************************

This is the main plugin class and
it handles everything there is to
handle for the subshops to work.

Everything is heavily commented so
dig right in, if you need to figure
out what's going on.

*/

class wss {


	/**
	 * Returns the path to this plugins dir
	 *
	 * @return string
	 * @author Troels Abrahamsen
	 **/
	public static function dir(){
		return dirname(__FILE__);
	}


	/**
	 * Checks if the current page is a subshop
	 *
	 * @return boolean
	 * @author Troels Abrahamsen
	 **/
	public static function is_subshop(){

		if(defined('WOO_SUBSHOP'))
			return true;

		return false;
	}

	/**
	 * Retrieves the shop base slug as set in the admin area
	 *
	 * @return string - the base slug
	 * @author Troels Abrahamsen
	 **/
	public static function get_shop_base(){
		if(!$base = self::get_option('rewrite_base')){
			$base = 'shops';
		}
		return $base;
	}

	/**
	 * This function makes sure that all the needed plugins are
	 * present and active. If a plugin is not active it will be included
	 * from the 'plugins' folder in this plugins dir.
	 *
	 * @return void
	 * @author Troels Abrahamsen
	 **/
	public static function load_plugins(){

		/* Check if the needed plugins are already loaded */
		$deps[] 	= 'advanced-custom-fields/acf.php';
		$deps[] 	= 'acf-options-page/acf-options-page.php';
		$deps[] 	= 'validated-field-for-acf/validated_field.php';

		include_once( ABSPATH . 'wp-admin/includes/plugin.php');
		foreach($deps as $dep){
			if(!is_plugin_active($dep)){
				if($dep == 'advanced-custom-fields/acf.php')
					define('ACF_LITE', true);
				require(woo_subshops::dir().'/plugins/'.$dep);
			}
		}

	}


	/**
	 * Sets up all admin functionality for this plugin
	 *
	 * @return void
	 * @author Troels Abrahamsen
	 **/
	public static function setup_admin(){

		acf_add_options_sub_page(array(
	        'title' 		=> 'Woocommerce Subshops',
	        'parent' 		=> 'options-general.php',
	        'capability' 	=> 'manage_options'
	    ));

		//require(woo_subshops::dir().'/acf/register_post_types.php');

	}


	/**
	 * Retreives an option for this plugin.
	 * 		
	 * TODO:	Option caching
	 * 			Since we are using the ACF for saving options
	 *			we need to implement some caching to
	 *			to prevent excessive requests to the database.
	 *
	 * @param  $option - the option key
	 * @return mixed - the option value
	 * @author Troels Abrahamsen
	 **/
	public static function get_option($option){

		return get_field('wss_'.$option, 'option');
	}

	/**
	 * Retreives a list of shops based on arguments or
	 * a specific shop from either ID or name.
	 *
	 * @return mixed - an array of posts or a single shop object
	 * @author Troels Abrahamsen
	 **/
	public static function get($args){

		if(is_string($args) and $args){
			if($shops = get_posts(array('post_type' => 'woo_subshop', 'page_name' => $args))){
				return $shops[0];
			}
		}

		if(is_int($args) and $args > 0){
			return get_post($args);
		}

		if(!$args){
			$args = array();
		}

		$args = array_merge($args, array('post_type' => 'woo_subshop'));
		return get_posts($args);
	}


	/**
	 * Retrieves the post object for the current subshop
	 *
	 * TODO 	Object caching
	 *			We need to cache the object returned to prevent
	 *			excessive database requests.
	 *
	 * @return mixed - object on succes, false on failure
	 * @author Troels Abrahamsen
	 **/
	public static function get_current_shop(){

		if(WOO_SUBSHOP)
			if($shop = get_post(WOO_SUBSHOP))
				return $shop;

		return false;
	}

}

?>