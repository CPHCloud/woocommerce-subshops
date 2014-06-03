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


	public static $wc_pages;

	/**
	 * Takes care of adding all actions and filters to the
	 * appropriate hooks as well as initiating any current subshop
	 *
	 * @return void
	 **/
	public static function init(){
		
		/* Hooks on init */		
		add_action('init', array('wss_init', 'init_hooks'), 1);
		add_action('plugins_loaded', array('wss_init', 'init_hooks'), 1);

		/* Register the needed post types - woo_subshop */
		add_action('init', array('wss_init', 'load_textdomain'), 1);

		/* Loads */
		add_action('admin_init', array('wss_init', 'admin_init'));
		
		/* Register the needed post types - woo_subshop */
		add_action('init', array('wss_init', 'register_post_types'), 1);

		/* Makes sure rewrites are set tup properly */
		add_action('init', array('wss_init', 'add_rewrites'), 999);

		/* Sets up the page permastructure to be appended to the subshop base */
		add_action('after_setup_theme', array('wss_init', 'add_page_permastruct'), 1);

		/* Filter the request to make sure we point to the right templates */
		add_filter('parse_request', array('wss_init', 'alter_request'), 2);

		/* Filter the request to make sure we point to the right templates */
		add_action('woocommerce_product_query', array('wss_init', 'alter_query'), 9999, 2);

		/* We need to change what templates are used for the subshops */
		add_filter('template_include', array('wss_init', 'template_redirects'), 999);

		/* Use our own session handler as opposed to the WC built-in */
		add_filter('woocommerce_session_handler', array('wss_init', 'alter_session_handler'), 999);

		/* Alter permalinks */		
		add_filter('option_woocommerce_permalinks', array('wss_init', 'alter_permalinks_options'));

		/* Add shop query var to admin-url.php */
		add_filter('admin_url', array('wss_init', 'alter_admin_url'));

		/* Make the wc_pages work */
		self::$wc_pages = array('cart', 'checkout', 'myaccount');
		// foreach(self::$wc_pages as $wc_page)

		add_filter('page_link', array('wss_init', 'alter_shop_pages_permalinks'), 999, 3);

		add_filter('woocommerce_locate_template', array('wss_init', 'alter_locate_template'), 999, 3);

		add_filter('wc_get_template_part', array('wss_init', 'alter_template_part'), 999, 3);

		add_filter('wp_get_nav_menu_items', array('wss_init', 'alter_menu_item_urls'), 999, 3);

		/*
		Since Woocommerce does not know if we are in a subshop or not
		all the links and urls it produces are pointing to the main shop.
		These next hooks all take care of filtering the urls and appending
		the 'shop/{subshop_name}' slug to them.
		*/
		$alter_urls_hooks = array(
			'woocommerce_get_cart_url',
			'woocommerce_get_checkout_url',
			'woocommerce_get_remove_url',
			'woocommerce_add_to_cart_url',
			'woocommerce_product_add_to_cart_url',
			'woocommerce_breadcrumb_home_url',
			'woocommerce_get_checkout_payment_url',
			'woocommerce_get_checkout_order_received_url',
			'woocommerce_get_cancel_order_url',
			'woocommerce_get_view_order_url',
			'add_to_cart_redirect');

		foreach($alter_urls_hooks as $hook)
			add_filter($hook, array('wss_init', 'alter_urls'), 999);


		/* Add the 'alter_params' filter to all the needed hooks */
		$alter_params_hooks = array(
			'woocommerce_params',
			'wc_single_product_params',
			'wc_checkout_params',
			'wc_address_i18n_params',
			'wc_cart_params',
			'wc_cart_fragments_params',
			'wc_add_to_cart_params',
			'wc_add_to_cart_variation_params',
			'wc_country_select_params');

		foreach($alter_params_hooks as $hook)
			add_filter($hook, array('wss_init', 'alter_params'), 999);

		/* This...well...inits the subshop. Woohoo! */
		self::init_subshop();

		//define('WOO_SUBSHOPS_DEBUG', true);

		// add_filter('wp_redirect', function(){
		// 	$bt = debug_backtrace();
		// 	echo '<pre>';
		// 	print_r($bt[3]);
		// 	echo '</pre>';
		// });

	}


	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function init_hooks(){
		if(!$shop = self::get_current_shop())
			return;
		$hook = current_filter();

		if($hook == 'init'){
			do_action('wss/shop/init');
			do_action('wss/shop-'.$shop->name.'/init', $shop);
		}
		elseif($hook == 'plugins_loaded'){
			do_action('wss/ready');
		}
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function load_textdomain(){
		load_plugin_textdomain('wss', false, 'woocommerce-subshops/languages/');
	}

	function alter_template_part($template, $slug, $name){
		if($shop = self::get_current_shop())
			if($name and $tmpl = self::locate_template(array($slug.'-'.$name.'.php')))
				return $tmpl;
		return $template;
	}

	function alter_locate_template($template, $template_name, $template_path){
		if($shop = self::get_current_shop())
			if($tmpl = self::locate_template($template_name))
				return $tmpl;
		return $template;
	}


	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function alter_menu_item_urls($items, $menu, $args){

		if(!$shop = wss::get_current_shop())
			return $items;

		
		foreach($items as $k => &$item){
			if($item->object == 'page' and $shop->has_page($item->object_id)){
				$page = get_page($item->object_id);
				$item->url = self::url_inject($item->url, '/'.$page->post_name, '/'.$shop->slug);
			}
		}
		
		return $items;

	}

	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function alter_shop_pages_permalinks($url, $page_id){
		if(!$shop = self::get_current_shop())
			return $url;

		if($key = array_search($page_id, $shop->pages)){
			if($page = get_post($page_id) and $defpage = get_page(wc_get_page_id($key))) {
				if(stripos($url, '/'.$shop->slug) !== false)
					$url = self::url_inject($url, '/'.$defpage->post_name, '/'.$shop->slug);
			}
		}
		elseif(wc_get_page_id('shop') == $page_id){
			$url = self::url_inject($url, '/', '/'.$shop->slug);
		}


		return $url;

	}

	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function alter_shop_pages_ids($id){

		if(!$shop = self::get_current_shop())
			return $id;

		$hook = current_filter();
		foreach(self::$wc_pages as $page){

			if(stripos($hook, $page) === false)
				continue;

			/* This shop has it's own page replacement for this WC page */
			if($page_id = $shop->{$page.'_page'}){
				$id = $page_id;
			}

		}

		return $id;
	}


	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function admin_init(){

		require(self::dir().'/inc/admin/wss_orders.class.php');
		wss_orders::init();

	}


	/**
	 * Add the woo_subshop argument to all calls to admin-ajax.php
	 *
	 * @param $url (string) - the url to parse
	 * @return string - the parsed url
	 **/
	function alter_admin_url($url){
		if(strpos($url, 'admin-ajax.php') !== false and $shop = self::get_current_shop()){
		    $url = add_query_arg('woo_subshop', $shop->ID, $url);
		}
		return $url;
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
	function alter_params($p){
		if($shop = self::get_current_shop()){
			if(isset($p['checkout_url'])){
				$p['checkout_url'] = add_query_arg('woo_subshop', $shop->ID, $p['checkout_url']);
			}
			if(isset($p['cart_url'])){
				$p['cart_url'] = self::url_inject($p['cart_url'], 'cart/', self::get_shop_base().'/'.$shop->name.'/');
			}

		}
		return $p;
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
				· $inject- Inject at the location of this string in url
				· $append - If $split is set this will be appended
				· $prepend - If $split is set this will be prepended
				· $add_vars - An array containing any extra query vars to add to the url
				*/

				case 'woocommerce_get_cart_url':
					$inject 	= '/cart';
					$prepend 	= '/'.self::get_shop_base().'/'.$shop->post_name;
					break;
				
				case 'woocommerce_get_checkout_url':
					$inject 	= '/checkout';
					$prepend 	= '/'.self::get_shop_base().'/'.$shop->post_name;
					break;
				
				case 'woocommerce_get_remove_url':
					$inject 	= '/cart/?remove_item';
					$prepend 	= '/'.self::get_shop_base().'/'.$shop->post_name;
					break;

				case 'woocommerce_add_to_cart_url':
					$inject 		= '/';
					$prepend 	= '/'.self::get_shop_base().'/'.$shop->post_name;
					break;

				case 'woocommerce_product_add_to_cart_url':
					$add_vars 	= array('woo_subshop' => $shop->ID);
					break;

				case 'woocommerce_breadcrumb_home_url':
					$append 	= '/'.self::get_shop_base().'/'.$shop->post_name;
					break;

				case 'add_to_cart_redirect':
					$inject 	= '/cart';
					$prepend 	= '/'.self::get_shop_base().'/'.$shop->post_name;
					break;
				
				default:
					$inject = false;
					break;
			}

			/* Do we need to split the string */
			if($inject){
				/*
				We do. Explode the URL by the $inject string.
				Assemble it again with prepending and appending
				any relevant strings.
				*/
				$url  	= self::url_inject($url, $inject, $prepend, $append);
			}
			elseif($append and $prepend){
				$url    = $prepend.$url.$append;
			}
			elseif($append){
				$url    = $url.$append;
			}
			elseif($prepend){
				$url    = $prepend.$url;
			}

			/* Are there any additional query vars defined? */
			if($add_vars){
				/* Sure is. Add them with add_query_arg() */
				$url = add_query_arg($add_vars, $url);
			}
			
			/*
			Sometimes the url is parsed twice through this method
			which means that the $append or $prepend string could
			be added twice. So here we preg_replace multiple occurences
			with only one. There is obviously a better way to do this
			but this will have to suffice for now.
			*/
			if($append){
				$url = preg_replace('~('.preg_quote($append).'){2,}~i', $append, $url);
			}
			if($prepend){
				$url = preg_replace('~('.preg_quote($prepend).'){2,}~i', $prepend, $url);
			}

		}

		return $url;
	}


	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function url_inject($url, $inject, $prepend = false, $append = false){
		$parts 	= explode($inject, $url);
		if(strpos($url, $inject) === false){
			/* $inject string does not exist */
			return $url;
		}
		$url 	= $parts[0].$prepend.$inject.$append.$parts[1];
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

		/* We're in the admin area. No need to go any further. */
		if(is_admin() and (!defined( 'DOING_AJAX' ) or !DOING_AJAX))
			return null;

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

		if(!$shop)
			return null;

		/* Cast the $shop var as either integer or string */
		if(is_numeric($shop)){
			$shop = (int)$shop;
		}
		else{
			$shop = (string)$shop;
		}

		/* We have a shop name or ID, so now we need check if its valid */
		if($shop and $shop = self::get($shop)){
			/* The shop is valid. Init it. */
			define('WOO_SUBSHOP', $shop->ID);
			define('WOO_SUBSHOP_NAME', $shop->post_name);
			global $curr_shop;
			$curr_shop = new wss_subshop($shop);

			apply_filters('wss/init_shop', $curr_shop);

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
			/* Yes we are. Go through the $permbases */
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
			'ep_mask'		=> EP_PAGES
		));

	}

	/**
	 * Appends all shop-related endpoints to the subshops base
	 *
	 * @return void
	 **/
	public static function add_rewrites(){

		global $wp_rewrite, $woocommerce;

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
	 * undocumented function
	 *
	 * @return void
	 **/
	function alter_query($query, $_this){
		if(is_post_type_archive('product')){
			
			$mq = $_this->meta_query;

			if($shop = self::get_current_shop()){
				$mq[] = array(
						'key' 		=> 'wss_in_shops',
						'value' 	=> $shop->ID,
						'compare' 	=> 'LIKE'
					);
			}
			else{			
				$mq[] = array(
							'key' 		=> 'wss_in_shops',
							'value' 	=> '"main"',
							'compare'	=> 'LIKE'
						);

				$mq[] = array(
							'key' 		=> 'wss_in_shops',
							'compare' 	=> 'NOT EXISTS'
						);

				$mq['relation'] = 'OR';
						
			}
			

			// Set meta_query
            $query->set('meta_query', $mq);

            // Update the internal state of the calling object
            $_this->meta_query = $mq;

			do_action('wss/shop/product_query', $query, $_this);
			if($shop)
				do_action('wss/shop-'.$shop->name.'/product_query', $query, $_this);

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
		if(!$shop = self::get_current_shop())
			return $wpq;

		global $woocommerce;

		/* Set request for 'product_cat' and 'product_tag' */
		if($wpq->query_vars['product_cat'] or $wpq->query_vars['product_tag']){
			$wpq->query_vars = self::extract($wpq->query_vars, array('product_tag', 'product_cat', 'page', 'orderby'));
			$wpq->query_vars['post_type'] = 'product';
		}
		elseif($wpq->query_vars['s']){
			$wpq->query_vars = self::extract($wpq->query_vars, array('s'));
			$wpq->query_vars['post_type'] = 'product';
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
			/* This is a page - we need */
			self::disable_cannonical();
			$wpq->query_vars 				= self::extract($wpq->query_vars, array_merge(array('pagename'), $woocommerce->query->query_vars));
			$wpq->is_singular 				= true;
			$wpq->is_single 				= false;
			$wpq->is_page 					= true;

			/* Handle request to WC pages - cart, checkout, myaccount, etc. */
			if($pages = get_posts('post_type=page&pagename='.$wpq->query_vars['pagename'])){
				$page = $pages[0];

				/*
				If this is one of the WC pages (cart, checkout, myaccount, etc.) find out
				if the subshop has registered it's own pages we need to setup instead of the
				default one.
				*/
				$defpages = self::get_wc_default_pages();
				if($key = array_search($page->ID, $defpages)){
					if($shop->pages[$key] !== $defpages[$key]){
						$shopspage = get_page($shop->pages[$key]);
						$wpq->query_vars['pagename'] = $shopspage->post_name;
					}
				}
				elseif(in_array($page->ID, $shop->pages)){
					/* Do not access this page. Send information down to template_redirects() */
					define('WOO_SUBSHOPS_404', true);
				}
			}

		}
		/* Set reqeust for shop front pages so it's a product archive instead of a single shop */
		elseif(
			$wpq->query_vars['woo_subshop']
			and
			$wpq->query_vars['woo_subshop'] == $wpq->query_vars['name']
			)
		{
			$wpq->query_vars 				= self::extract($wpq->query_vars, array('page', 'orderby'));
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
	 * undocumented function
	 *
	 * @return void
	 **/
	function disable_cannonical(){
		remove_filter('template_redirect', 'redirect_canonical');
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

		if(defined('WOO_SUBSHOPS_404'))
			return self::get_404();

		if($shop = self::get_current_shop()){

			/* We are in a shop */

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

			/* Now we check if this is a product page */
			if(is_single() and get_post_type() == 'product'){
				global $post;
				if(!$shop->has_product($post->ID)){
					return self::get_404();
				}
			}

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
	 * Set to 404 and send status header
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
	 * Registers all the neccesary post_types
	 *
	 * @return void
	**/
	public static function register_post_types(){

		/* Register Subshop post type */
		$labels = array(
			'name'                => __( 'Shops', 'wss'),
			'singular_name'       => __( 'Shop', 'wss'),
			'menu_name'           => __( 'Shops', 'wss'),
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