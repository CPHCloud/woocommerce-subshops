<?php

/*
*********************************
wss_init class
*********************************

This class holds all methods hooked
up to t´filters and actions as well
as other methods needed for the initial
setup of Woocommerce Subshops

*/

class wss_init extends wss {


	/**
	 * Takes care of adding all actions and filters to the
	 * appropriate hooks.
	 *
	 * @return void
	 * @author Troels Abrahamsen
	 **/
	public static function init(){

		/* These actions run */
		add_action('init', array('wss_init', 'load_plugins'), 1);
		add_action('init', array('wss_init', 'register_post_types'), 1);
		add_action('init', array('wss_init', 'setup_admin'), 1);
		add_action('init', array('wss_init', 'add_rewrites'), 999);

		/* Initializing is done, so now we set any needed query_vars */
		add_filter('query_vars', array('wss_init', 'add_query_vars'));

		/*
		OK. Everything is set up, so now we need to hook into wp
		and make sure we set all neede variables and constants for
		the system to work properly
		*/
		add_action('wp', array('wss_init', 'init_subshop'), 1);

		/* These filters handle specific tasks for subshop pages */
		add_filter('option_woocommerce_permalinks', array('wss_init', 'alter_permalinks_options'));
		add_filter('term_link', array('wss_init', 'alter_term_links'), 999, 3);
		add_filter('template_include', array('wss_init', 'template_redirects'), 999);
		add_filter('post_link', array('wss_init', 'alter_permalinks'), 999, 3);
		add_filter('the_permalink', array('wss_init', 'alter_permalinks'), 999, 3);

		/* Uncomment this to debug */
		define('WOO_SUBSHOPS_DEV', false);
		define('WOO_SUBSHOPS_DEBUG', false);

	}


	/**
	 * Outputs information debug information on all the relevant pages
	 *
	 * @return void
	 * @author Troels Abrahamsen
	 **/
	private static function debug(){
		if(WOO_SUBSHOPS_DEBUG){
			add_action('template_include', function($template){
				global $wp;
				$mywp = $wp;
				unset($mywp->public_query_vars, $mywp->private_query_vars, $mywp->extra_query_vars);
				echo '<pre>';
				print_r($wp);
				echo '</pre>';
				return $template;
			}, 999);

			add_action('generate_rewrite_rules', function($rules){
				echo '<pre>';
				print_r($rules);
				echo '</pre>';
				//$rules->flush_rewrite_rules();
				return $rules;
			}, 999);
		}
	}


	/**
	 * This function makes sure that all 'product_cat' and 'product_tag'
	 * taxonomy links are changed so they point to the current subshop
	 *
	 * @hooked term_link - 999
	 * @return string - the passed termlink
	 * @author Troels Abrahamsen
	 **/
	public static function alter_term_links($termlink, $term, $taxonomy){
		if(!is_admin() and self::is_subshop()){
			$change = array('product_cat', 'product_tag');
			if(in_array($taxonomy, $change)){
				$url 		= get_bloginfo('url');
				$replace 	= $url.'/'.self::get_shop_base().'/'.WOO_SUBSHOP_NAME;
				$termlink 	= str_ireplace($url, $replace, $termlink);
			}
		}
		return $termlink;
	}


	/**
	 * Changes all links to products so they have the subshop slug
	 * prepended.
	 *
	 * @hooked post_link - 999
	 * @hooked the_permalink - 999
	 * @return string - the passed url
	 * @author Troels Abrahamsen
	 **/
	public static function alter_permalinks($url, $post, $leavename){
		if(self::is_subshop() and $post->post_type == 'product'){
			$url = substr($url, strlen(get_bloginfo('url')));
			$url = get_bloginfo('url').'/'.self::get_shop_base().'/'.WOO_SUBSHOP_NAME.''.$url;
		}
		return $url;
	}


	/**
	 * Alters the woocommerce_permalinks option on the front-end
	 * to include the subshop slug if needed.
	 *
	 * @hooked option_woocommerce_permalinks
	 * @return array - the array contatining the permalink bases for woocommerce
	 * @author Troels Abrahamsen
	 **/
	public static function alter_permalinks_options($permbases){
		if(is_admin())
			return $permbases;

		if(self::is_subshop()){
			foreach($permbases as $name => &$permbase){
				if(empty($permbase)){
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
				$permbase 	= preg_replace('~[\/]{2,}~', '/', self::get_shop_base().'/'.WOO_SUBSHOP_NAME.'/'.$permbase);
			}
		}

		return $permbases;
	}

	/**
	 * Appends all shop-related endpoints to the subshops base
	 *
	 * @author Troels Abrahamsen
	 **/
	public static function add_rewrites(){

		global $wp_rewrite;

		/* Shop rewrite base */
		if(!$shopbase = self::get_shop_base()){
			$shopbase = 'shops';
		}

		/* Get structures to add from wp_rewrite woocommerce */
		$structs = $wp_rewrite->extra_permastructs;

		$permastructs = apply_filters('woo_subshops/append_permastructs', array('product', 'product_[\d\w_-]+', 'pa_[\d\w_-]+'));

		if(!is_array($permastructs)){
			$permastructs = array();
		}

		foreach($structs as $k => $struct){

			if(preg_match('~^('.implode('|', $permastructs).')$~', $k)){
				$args 				= $struct;
				$structure  		= $args['struct'];
				$args['ep_mask'] 	= true;
				$args['with_front'] = false;
				$newstruct			= preg_replace('~\/{2,}~', '/', $shopbase.'/%woo_subshop%/'.$structure);
				add_permastruct('woo_subshop_'.$k, $newstruct, $args);
			}

		}

	}


	/**
	 * Adds any neccesary query_vars
	 *
	 * @return array - the passed vars
	 * @author Troels Abrahamsen
	 **/
	public static function add_query_vars($vars){

	    return $vars;
	}


	/**
	 * If any requests in subshop scope need special templates
	 * other than what WP comes up with, this is where the magic happens.
	 *
	 * @return string - the template
	 * @author Troels Abrahamsen
	 **/
	public static function template_redirects($template){

		if(WOO_SUBSHOP){
			if(!$shop = self::get(WOO_SUBSHOP))
				return $template;

			if(is_single())


			if(strpos($template, 'woocommerce/') !== false) {
				$dirs[] = get_stylesheet_directory().'/subshops/'.$shop->post_name;
				$dirs[] = get_stylesheet_directory().'/subshops';
				$parts = explode('/', $template);
				$templ = $parts[count($parts) - 1];
				foreach($dirs as $dir){
					if(file_exists($dir.'/'.$templ))
						return $dir.'/'.$templ;
				}
			}
		}
		
		return $template;
	}


	/**
	 * Sets the subshop constants used throughout the load
	 *
	 * @return void
	 * @author Troels Abrahamsen
	 **/
	public static function init_subshop(){
		$shop_id = 0;
		if($shop_name = get_query_var('woo_subshop')){
			$shop 	 = self::get($shop_name);
			$shop_id = $shop->ID;
		}
		elseif($shop_id = self::get_option('primary_shop')){
			$shop = self::get($shop_id);
			$shop_name = $shop->post_name;
		}

		define('WOO_SUBSHOP', $shop_id);
		define('WOO_SUBSHOP_NAME', $shop_name);

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
	        'slug'			=> 'wss-options-general',
	        'parent' 		=> 'options-general.php',
	        'capability' 	=> 'manage_options'
	    ));

		self::register_acf_fields();

	}

	/**
	 * 
	 *
	 * @return void
	 * @author Troels Abrahamsen
	 **/
	function include_acf_fields(){
		
		if(file_exists(self::dir().'/inc/register-acf-fields.php'))
			require(self::dir().'/inc/register-acf-fields.php');

	}


	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Troels Abrahamsen
	 **/
	function export_acf_fields(){
		
		/* The path to the file that registers the fields */
		$file 	  = self::dir().'/inc/register-acf-fields.php';

		/* The arguments to get all ACF fields */
		$get_acfs = array('Woocommerce subshop options');
		$acfs = array();
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
	 * @author Troels Abrahamsen
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
	 * @author Troels Abrahamsen
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
				'with_front' 	=> false
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