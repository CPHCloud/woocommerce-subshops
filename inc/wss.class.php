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

/* Require inlineWP for inline css and js */
require_once('inlineWP/inlineWP.php');
inlinewp('wss');
		
class wss {

	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	public static function init(){

		require_once('wss_init.class.php');
		require_once('wss_admin.class.php');
		require_once('wss_subshop.class.php');

		wss_admin::init();
		wss_init::init();

		add_action('plugins_loaded', array('wss', 'setup_dev_env'), 1);

	}

	public function setup_dev_env(){
		/*
		This constant is used to check if the plugin is under development.
		It is mainly used by the ACF plugin used to create the options panel.
		*/
		if(self::get_option('dev_mode')){
			define('WOO_SUBSHOPS_DEV', true);
		}
		else{
			define('WOO_SUBSHOPS_DEV', false);
		}

		/* Set this to true to output debug information. */
		define('WOO_SUBSHOPS_DEBUG', false);

	}

	public static function inlinewp(){
		return inlinewp('wss');
	}

	/**
	 * Returns the path to this plugins dir
	 *
	 * @return string
	 **/
	public static function dir(){
		return WOO_SUBSHOPS_DIR;
	}


	/**
	 * Checks if the current page is a subshop
	 *
	 * @return boolean
	 **/
	public static function is_subshop(){

		if(!is_admin()){
			if(self::get_current_shop()){
				return true;
			}
		}

		return false;
	}


	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function set_var($key, $value){
		$GLOBALS['__wss_'.$key] = $value;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function get_var($key){
		return $GLOBALS['__wss_'.$key];
	}


	/**
	 * Retrieves the shop base slug as set in the admin area
	 *
	 * @return string - the base slug
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
	 **/
	public static function load_plugins(){

		include_once( ABSPATH . 'wp-admin/includes/plugin.php');

		if(is_plugin_active('advanced-custom-fields-pro/acf.php'))
			return false;

		/* Check if the needed plugins are already loaded */
		$deps[] 	= 'advanced-custom-fields/acf.php';
		$deps[] 	= 'acf-options-page/acf-options-page.php';
		$deps[] 	= 'acf-flexible-content/acf-flexible-content.php';
		$deps[] 	= 'acf-repeater/acf-repeater.php';

		foreach($deps as $dep){
			if(!is_plugin_active($dep)){
				if($dep == 'advanced-custom-fields/acf.php')
					define('ACF_LITE', true);
				require(self::dir().'/plugins/'.$dep);
			}
		}

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
	 **/
	public static function get_option($option){
		return get_field('wss_'.$option, 'option');
	}
	

	/**
	 * Retreives a list of shops based on arguments or
	 * a specific shop from either ID or name.
	 *
	 * @return mixed - an array of posts or a single shop object
	 **/
	public static function get($args){

		if(is_string($args) and $args){
			if($shops = get_posts(array('post_type' => 'woo_subshop', 'name' => $args))){
				return $shops[0];
			}
		}
		elseif(is_int($args) and $args > 0){
			return get_post($args);
		}
		else{
			if(!is_array($args)){
				$args = array();
			}
			$args = array_merge($args, array('post_type' => 'woo_subshop', 'post_status' => 'publish'));
			return get_posts($args);
		}

		return false;

	}


	/**
	 * Retrieves the post object for the current subshop
	 *
	 * TODO 	Object caching
	 *			We need to cache the object returned to prevent
	 *			excessive database requests.
	 *
	 * @return mixed - object on succes, false on failure
	 **/
	public static function get_current_shop(){
		global $curr_shop;
		return $curr_shop;
	}


	/**
	 * Our implementation of locate_template. Used to locate templates
	 * for subshops specifically.
	 *
	 * @return string - the located template
	 **/
	public static function locate_template($templates, $look_in_self = false){

		if(is_string($templates))
			$templates = array($templates);

		$dirs = array();

		if($shop = self::get_current_shop()){
			$dirs[] = get_stylesheet_directory().'/subshops/'.$shop->post_name.'/';
			$dirs[] = get_stylesheet_directory().'/subshops/_default/';
			$dirs[] = get_template_directory().'/subshops/'.$shop->post_name.'/';
			$dirs[] = get_template_directory().'/subshops/_default/';
		}

		$dirs 	= apply_filters('wss/locate_template_dirs', $dirs, $shop);

		$dirs[] = get_stylesheet_directory().'/';
		$dirs[] = get_template_directory().'/';


		if($look_in_self){
			$dirs[] = self::dir().'/templates/';
		}

		foreach($dirs as $dir){
			foreach($templates as $template){
				if(file_exists($dir.$template))
					return $dir.$template;
			}
		}
	}


	/**
	 * Ouput debug information if the debug constant is set to true
	 *
	 * @return void
	 **/
	public static function debug($what){
		if(WOO_SUBSHOPS_DEBUG){
			echo '<pre>';
			print_r($what);
			echo '</pre>';
		}
	}


	/**
	 * A utility function that extracts the key-value sets
	 * defined by keys in $extract.
	 *
	 * @param $array (array) - the array to extract from
	 * @param $extract (array) - and array containing the keys to get
	 * @return void
	 **/
	public static function extract($array, $extract){
		
		foreach($array as $k => $v){
			if(!in_array($k, $extract)){
				unset($array[$k]);
			}
		}

		return $array;
	}


	/**
	 * Proxy for get_header() that looks for header.php in 'subshops' folder
	 * of the current theme and includes it if found.
	 * Falls back to get_header() on non shop.
	 *
	 * @return void
	 **/
	function get_header($name = false){
		if($shop = self::get_current_shop()){
			do_action('get_header');
			$tmpl = 'header';
			if($name){
				$tmpl .= '-'.$name;
			}
			$tmpl .= '.php';
			if($tmpl = self::locate_template($tmpl)){
				load_template($tmpl);
			}
		}
		else{
			get_header($name);
		}
	}


	/**
	 * Proxy for get_footer() that looks for footer.php in 'subshops' folder
	 * of the current theme and includes it if found.
	 * Falls back to get_footer() on non shop.
	 *
	 * @return void
	 **/
	function get_footer($name = false){
		if($shop = self::get_current_shop()){
			do_action('get_footer');
			$tmpl = 'footer';
			if($name){
				$tmpl .= '-'.$name;
			}
			$tmpl .= '.php';
			if($tmpl = self::locate_template($tmpl)){
				load_template($tmpl);
			}
		}
		else{
			get_footer($name);
		}
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function get_wc_default_pages(){

		$def = array(
			'cart' 		=> get_option('woocommerce_cart_page_id'),
			'checkout' 	=> get_option('woocommerce_checkout_page_id'),
			'myaccount' => get_option('woocommerce_myaccount_page_id')
		);

		return $def;
	}



	function log($msg){
		error_log('WSS plugin: '.$msg);
	}



}

?>
