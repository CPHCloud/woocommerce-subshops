<?php

/*
*********************************
wss_init class
*********************************

This class holds all methods hooked
up to the filters and actions as well
as other methods needed for the initial
setup of Woocommerce Subshops

*/


class wss_init extends wss {


	/**
	 * Takes care of adding all actions and filters to the
	 * appropriate hooks as well as initiating any current subshop
	 *
	 * @return void
	 **/
	public static function init(){

		/* Loads all needed plugins, embedded or already installed */
		add_action('init', array('wss_init', 'load_plugins'), 1);
		
		/* Register the needed post types - woo_subshop */
		add_action('init', array('wss_init', 'register_post_types'), 1);

		/* Sets up the admin screens and options */
		add_action('init', array('wss_init', 'setup_admin'), 1);

		/* Makes sure rewrites are set tup properly */
		add_action('init', array('wss_init', 'add_rewrites'), 999);

		/* Sets up the page permastructure to be appended to the subshop base */
		add_action('after_setup_theme', array('wss_init', 'add_page_permastruct'), 1);

		/* Filter the request to make sure we point to the right templates */
		add_filter('parse_request', array('wss_init', 'alter_request'), 2);

		/* We need to change what templates are used for the subshops */
		add_filter('template_include', array('wss_init', 'template_redirects'), 999);

		/*
		Since Woocommerce does not know if we are in a subshop or not
		all the links and urls it produces are pointing to the main shop.
		These next hooks all take care of filtering the urls and appending
		the 'shop/{subshop_name}' slug to them.
		*/
		add_filter('option_woocommerce_permalinks', array('wss_init', 'alter_permalinks_options'));
		add_filter('woocommerce_get_cart_url', array('wss_init', 'alter_urls'));
		add_filter('woocommerce_get_checkout_url', array('wss_init', 'alter_urls'));
		add_filter('woocommerce_get_remove_url', array('wss_init', 'alter_urls'));
		add_filter('woocommerce_add_to_cart_url', array('wss_init', 'alter_urls'));
		add_filter('woocommerce_product_add_to_cart_url', array('wss_init', 'alter_urls'));
		add_filter('add_to_cart_redirect', array('wss_init', 'alter_urls'));

		/* Use our own session handler as opposed to the WC built-in */
		add_filter('woocommerce_session_handler', array('wss_init', 'alter_session_handler'), 999);

		/* Hook the admin ajax url to add the 'woo_subshop' query var */
		add_filter('admin_url', array('wss_init', 'alter_admin_ajax_url'));

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

		/* This...well...inits the subshop. Wohoo! */
		self::init_subshop();

	}


	/**
	 * This hooked function takes care of altering the url
	 * to the admin-ajax.php so it has the 'woo_subshop' var
	 * appended. This is neccesary to parse the current shop
	 * to the various WC ajax functions
	 *
	 * @param $url (string) - the url to parse
	 * @return string - the parsed url
	 **/
	function alter_admin_ajax_url($url){
		if($shop = self::get_current_shop())
			$url = add_query_arg('woo_subshop', $shop->ID, $url);
		return $url;
	}

	/**
	 * Alters the url passed to append subshop base and/or
	 * any query args
	 *
	 * @param $url (string) - the url to parse
	 * @return string - the parsed url
	 **/
	function alter_urls($url){

		/* Check if this is a subshop at all */
		if($shop = self::get_current_shop()){

			/*
			This is a subshop indeed. No we need to find out which url we're trying to pass.
			For that we can use the WP function current_filter().
			*/
			switch (current_filter()) {

				/*
				In each case of this switch a few variables can be defined.
				The definition will affect how the URL looks when it returns
				· $split - Split the url by this string
				· $append - If $split is set this will be appended
				· $prepend - If $split is set this will be prepended
				· $add_vars - An array containing any extra query vars to add to the url
				*/

				case 'woocommerce_get_cart_url':
					$split 		= '/cart';
					$prepend 	= '/'.self::get_shop_base().'/'.$shop->post_name;
					break;
				
				case 'woocommerce_get_checkout_url':
					$split = '/checkout';
					$prepend 	= '/'.self::get_shop_base().'/'.$shop->post_name;
					break;
				
				case 'woocommerce_get_remove_url':
					$split = '/cart/?remove_item';
					$prepend 	= '/'.self::get_shop_base().'/'.$shop->post_name;
					break;

				case 'woocommerce_add_to_cart_url':
					$split = '/';
					$prepend 	= '/'.self::get_shop_base().'/'.$shop->post_name;
					break;

				case 'woocommerce_product_add_to_cart_url':
					$add_vars = array('woo_subshop' => $shop->ID);
					break;

				case 'add_to_cart_redirect':
					$split = '/cart';
					$prepend 	= '/'.self::get_shop_base().'/'.$shop->post_name;
					break;
				
				default:
					$split = false;
					break;
			}

			/* Do we need to split the string */
			if($split){
				/*
				We do. Explode the URL by the $split string.
				Assemble it again with prepending and appending
				any relevant strings.
				*/
				$parts 	= explode($split, $url);
				$url  	= $parts[0].$prepend.$split.$append.$parts[1];
			}

			/* Are there any additional query vars defined? */
			if($add_vars){
				/* Sure is. Add them with add_query_arg() */
				$url = add_query_arg($add_vars, $url);
			}
		}

		return $url;
	}


	/**
	 * If a subshop is present we initiate it here.
	 *
	 * @return void
	 **/
	function init_subshop(){

		/* 
		NOTE ON THIS METHOD OF RETREIVING THE SUBSHOP
		You might ask why we aren't using the 'woo_subshop' query
		var given to us by WP. The problem is that it is initiated
		much too late in the script to be of any use to use at this
		point. It's neccesary to know what subshop we are in very early
		to be able to init it before Woocommerce starts its engines.
		*/

		/* First we checkfor the GET vars 'woo_subshop'
		and use that if it's present. This is neede to support
		ajax request where the only way of tellign which shop is
		needed is by using the GET var.
		*/
		if(!$shop = $_GET['woo_subshop']){
			/* No GET var. We need to find the shopname by the url pattern. */
			$shoppatt = '~^/'.self::get_shop_base().'/([^/]+)~';
			/* Match the REQUEST_URI to the $shoppattern */
			if(preg_match($shoppatt, $_SERVER['REQUEST_URI'], $matches)){
				/* Bingo. We have a shop name. */
				$shop = $matches[1];
			}
		}

		/* Cast the $shop var as either integer or string */
		if(is_numeric($shop)){
			$shop = (int)$shop;
		}
		else{
			$shop = (string)$shop;
		}

		/* We have a shop name or ID, so now we need check if its valid */
		if($shop = self::get($shop)){
			/* The shop is valid. Init it. */
			define('WOO_SUBSHOP', $shop->ID);
			define('WOO_SUBSHOP_NAME', $shop->post_name);
			global $curr_shop;
			$curr_shop = new wss_subshop($shop);
		}
	}


	/**
	 * This hooked function allows us to change the session handler class.
	 * This is crucial to support session handling of the cart and other
	 * session info for each individual shop.
	 *
	 * @param $session_handler (string) - the current session handler
	 * @return string - the new session handler
	 **/
	static function alter_session_handler($session_handler){
		require_once('wss_session_handler.class.php');
		return 'wss_session_handler';
	}


	/**
	 * Alters the woocommerce_permalinks option on the front-end
	 * to include the subshop slug if needed.
	 *
	 * @hooked option_woocommerce_permalinks
	 * @param $permbases (array) - Woocommerce permatructure bases
	 * @return array - the array contatining the permalink bases for woocommerce
	 **/
	public static function alter_permalinks_options($permbases){
		/* Are we in subshop */
		if($shop = self::get_current_shop()){
			/* Yes we are. Go thorugh the $permbases */
			foreach($permbases as $name => &$permbase){

				/* If the current base is empty we need to fall back to the defaults. */
				if(empty($permbase)){
					/* Base is empty. Apply defaults. */
					$name 		= str_ireplace('_base', '', $name);
					if($name == 'category'){
						$permbase 	= _x('product-category', 'slug', 'woocommerce' );
					}
					if($name == 'tag'){
						$permbase 	= _x('product-tag', 'slug', 'woocommerce' );
					}
					if($name == 'product'){
						$permbase 	= _x('product', 'slug', 'woocommerce' );
					}
				}

				/* Append the subshop name to the base */
				$permbase 	= preg_replace('~[\/]{2,}~', '/', self::get_shop_base().'/'.$shop->post_name.'/'.$permbase);
			}
		}

		return $permbases;
	}


	/**
	 * Makes subshops capable of displaying pages.
	 * Check for ownership happens in self::template_redirects
	 *
	 * @return void
	 **/
	public static function add_page_permastruct(){

		/* Add page permastruct */
		add_permastruct('woo_subshop_page', self::get_shop_base().'/%woo_subshop%/%pagename%', array(
			'with_front'	=> false,
			'ep_mask'		=> true
		));

	}

	/**
	 * Appends all shop-related endpoints to the subshops base
	 *
	 * @return void
	 **/
	public static function add_rewrites(){

		global $wp_rewrite;

		/* Get structures to add from wp_rewrite woocommerce */
		$structs = $wp_rewrite->extra_permastructs;

		/* The permastructure patterns to look for */
		$permastructs = apply_filters('woo_subshops/append_permastructs', array('product', 'product_[\d\w_-]+', 'pa_[\d\w_-]+'));

		/* Make sure $permastructs is an array */
		if(!is_array($permastructs)){
			$permastructs = array();
		}

		/* Loop through each WP registered permastruct */
		foreach($structs as $k => $struct){
			/* Match the structure against the one we are looking for in $permastructs */
			if(preg_match('~^('.implode('|', $permastructs).')$~', $k)){
				/* We have a match. Add the permastructure */
				$args 				= $struct;
				$structure  		= $args['struct'];
				$args['ep_mask'] 	= true;
				$args['with_front'] = false;
				$newstruct			= preg_replace('~\/{2,}~', '/', self::get_shop_base().'/%woo_subshop%/'.$structure);
				add_permastruct('woo_subshop_'.$k, $newstruct, $args);
			}

		}

	}


	/**
	 * Alters the request before it is parsed by the query.
	 * This enables us to clean up and morph requests into 
	 * the request we really want
	 *
	 * @param $wpq (object) - the WP_Request object
	 * @return object - the parsed request object
	 **/
	function alter_request($wpq){

		/* Debugging */
		self::debug('Before:');
		self::debug($wpq->query_vars);

		/* Check if this is a subshop. Return if not. */
		if(!self::is_subshop())
			return $wpq;

		/* Set request for 'product_cat' and 'product_tag' */
		if($wpq->query_vars['product_cat'] or $wpq->query_vars['product_tag']){
			$wpq->query_vars = self::extract($wpq->query_vars, array('product_tag', 'product_cat', 'page'));
		}
		/* Set request for products */
		elseif($wpq->query_vars['product']){
			$wpq->query_vars 				= self::extract($wpq->query_vars, array('name', 'product', 'post_type'));
			$wpq->is_singular 				= true;
			$wpq->is_single 				= false;
			$wpq->is_page 					= true;
		}
		/* Set request for pages */
		elseif($wpq->query_vars['pagename']){
			$wpq->query_vars 				= self::extract($wpq->query_vars, array('pagename'));
			$wpq->is_singular 				= true;
			$wpq->is_single 				= false;
			$wpq->is_page 					= true;
		}
		/* Set requst for shop front pages so it's a product archive instead of a single shop */
		elseif(
			$wpq->query_vars['woo_subshop']
			and
			$wpq->query_vars['woo_subshop'] == $wpq->query_vars['name']
			and
			count($wpq->query_vars) <= 4
			)
		{
			$wpq->query_vars 				= self::extract($wpq->query_vars, array('page'));
			$wpq->query_vars['post_type'] 	= 'product';
			$wpq->is_post_type_archive 		= true;
			$wpq->is_single 				= false;
		}

		/* Debugging */
		self::debug('After:');
		self::debug($wpq->query_vars);

		return $wpq;

	}


	/**
	 * If any requests in subshop scope need special templates
	 * other than what WP comes up with, this is where the magic happens.
	 *
	 * This also handles ANY request to a page to see if it's assigned
	 * to any subshops
	 *
	 * @param $template (string) - the template to parse
	 * @return string - the parsed template
	 **/
	public static function template_redirects($template){
		if($shop = self::get_current_shop()){

			/* Lets see if the user has access */
			if($shop->private){
				if(is_user_logged_in()){
					if(!$shop->has_user(get_current_user_id()))
						return self::locate_template('no-shop-access.php', true);
				}
				else {
					return self::locate_template('no-shop-access.php', true);
				}
			}

			/* 
			We are in a shop.
			Here we will run a check to see if the pages are assigned
			to the current shop. But first we need to weed out the
			pages that are set as 'checkout', 'cart' and 'my-account'.
			Get the pages from Woocommerce */
			$allowed_pages = array(
				get_option('woocommerce_checkout_page_id'),
				get_option('woocommerce_cart_page_id'),
				get_option('woocommerce_myaccount_page_id')
			);

			/*
			First a check to see if this is a page and if
			we have the proper priveliges to view it.
			*/
			if(
				/* Is page? */
				is_page()
				and
				/* Is not one of the allowed pages? */
				!in_array(get_queried_object_id(), $allowed_pages)
				and
				/* Current shop has this page? */
				!$shop->has_page(get_queried_object_id())
				)
			{
				/* We didnt match all rules. Return the 404 template. */
				return self::get_404();
			}


			/*
			Now we need to figure out which template to load.
			We'll check for woocommerce templates and any page
			templates present in a 'subshop' folder in the current
			theme.
			*/
			if(is_page()){
				/* Get any page template */
				$templates = array('page-'.get_query_var('pagename').'.php', 'page.php');
				if($found = self::locate_template($templates))
					return $found;

			}
			elseif(is_404()){
				if($found = self::locate_template('404.php'))
					return $found;				
			}
			elseif(strpos($template, 'woocommerce/') !== false) {
				/* Get any woocommerce templates */
				$parts = explode('/', $template);
				$templ = $parts[count($parts) - 1];
				if($found = self::locate_template($templ))
					return $found;
			}
		}
		/* Is this a page and NOT a subshop */
		elseif(is_page()){
			/* Check if page is assigned to a subshop. Return 404 if true. */
			if($shops = self::get(array('posts_per_page' => '-1'))){
				foreach($shops as $shop){
					$shop = new wss_subshop($shop);
					if($shop->has_page(get_queried_object_id())){
						return self::get_404();
					}
				}
			}
		}

		return $template;
	}


	/**
	 * Set to 40 and send status header
	 *
	 * @return void
	 **/
	function set_404(){
		global $wp_query;
		$wp_query->set_404();
		status_header(404);
	}

	/**
	 * Sets to 404 and retrieves the 404 template
	 *
	 * @return string - the 404 template
	 **/
	function get_404(){
		self::set_404();
		return self::locate_template('404.php');
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
		$get_acfs 	= array('wss:options', 'wss:pages', 'wss:subshop');
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
	 * Registers all the neccesary post_types
	 *
	 * @return void
	**/
	public static function register_post_types(){

		/* Register Subshop post type */
		$labels = array(
			'name'                => __( 'Shops', 'woo_subshops' ),
			'singular_name'       => __( 'Shop', 'woo_subshops' )
		);

		$args = array(
			'labels'              => $labels,
			'hierarchical'        => false,
			'description'         => 'description',
			'taxonomies'          => array(),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 57,
			'menu_icon'           => 'dashicons-networking',
			'show_in_nav_menus'   => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'has_archive'         => false,
			'query_var'           => true,
			'can_export'          => true,
			'rewrite'             => array(
				'slug' 			=> self::get_shop_base(),
				'with_front' 	=> false,
				'ep_mask'		=> true
				),
			'capability_type'     => 'post',
			'supports'            => array(
				'title'
				)
		);

		register_post_type('woo_subshop', $args);
			
	}

}

?>