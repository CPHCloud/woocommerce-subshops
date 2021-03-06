<?php
$__wss_is_exporting = false;
/**
* 
*/
class wss_admin extends wss_init {
	

	public static $privileges;

	/**
	 * Inits the admin parts of the plugin
	 *
	 * @return void
	 **/
	public static function init(){

		//self::add_privilege('Can everything', 'can_everything', array('wss_admin', 'priv_handler'));

		add_action('plugins_loaded', array('wss_admin', 'acf_support'), 2);

		/* Sets up the admin screens and options */
		add_action('plugins_loaded', array('wss_admin', 'setup_admin'), 2);

		/* Hook the wss_in_shops field for products */
		add_filter('acf/load_field/name=wss_in_shops', array('wss_admin', 'alter_field_in_shops'), 999, 3);

		/* Hook the wss_in_shops field for products */
		add_filter('acf/load_field/name=wss_users', array('wss_admin', 'alter_field_privileges'), 999, 3);
		add_filter('acf/load_field/name=wss_roles', array('wss_admin', 'alter_field_privileges'), 999, 3);
		add_filter('acf/load_field/name=wss_roles', array('wss_admin', 'alter_field_roles_name'), 999, 4);

		/* Update the order meta with shop value */
		add_action('woocommerce_checkout_update_order_meta', array('wss_admin', 'add_subshop_to_order'));

		add_action('admin_head', array('wss_admin', 'hide_publish_info'));
		add_filter('acf/fields/flexible_content/no_value_message', array('wss_admin', 'alter_flexcontent_text'));

		add_action('acf/include_fields', 'wss_admin::include_field_groups', 20, 1);

	}

	public static function priv_handler($handle, $user){
		return true;
	}

	function add_privilege($title, $handle, $callback){
		self::$privileges[$handle] = array(
				'title' 	=> $title,
				'callback' 	=> $callback
			);
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function acf_support(){
		if(class_exists('acf')){
			require(self::dir().'/inc/acf/wss_acf.php');
			wss_acf::init();
		}
	}


	function alter_field_roles_name($field){

		if(self::get_var('is_exporting') === true){
			return $field;
		}

		foreach($field['sub_fields'] as &$sfield){
			if($sfield['name'] == 'role'){
				global $wp_roles;
				foreach ($wp_roles->roles as $role => $props) {
					$sfield['choices'][$role] = $props['name'];
				}
				break;
			}
		}


		return $field;
	}

	function alter_field_privileges($field){

		if(self::get_var('is_exporting') === true){
			return $field;
		}

		foreach($field['sub_fields'] as &$sfield){
			if($sfield['name'] == 'privileges'){
				$privileges = apply_filters('wss/privileges', self::$privileges);
				foreach($privileges as $handle => $p)
					$privs[$handle] = $p['title'];
				$sfield['choices'] = $privs;
				break;
			}
		}

		return $field;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function alter_flexcontent_text($text){
		global $pagenow, $post;
		if($pagenow == 'post.php' and get_post_type() == 'woo_subshop'){
			$text = 'Click the "Add element" button to start adding layout fields';
		}
		return $text;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function hide_publish_info(){
		global $pagenow, $post;
		if($pagenow == 'post.php' and get_post_type($post) == 'woo_subshop'){
			echo '<style>#minor-publishing{ display: none;}</style>';
		}
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

		/* Get published subshops */
		$shops = get_posts(array(
			'post_type' 	=> 'woo_subshop',
			'post_status' 	=> 'publish',
			'posts_per_page'=> -1
		));

		/* Register menues and sidebars for each subshop */
		foreach($shops as $shop){
			$shop = new wss_subshop($shop);
			if($shop->menu_locations){
				foreach($shop->menu_locations as $loc){
					register_nav_menu('wss-'.$shop->post_name.'-'.sanitize_title($loc['name']), $loc['description']);			
				}
			}
			if($shop->sidebars){
				foreach($shop->sidebars as $bar){

					$args = array(
						'name'          => $shop->post_title.' - '.$bar['name'],
						'id'            => 'wss-'.$shop->name.'-'.sanitize_title($bar['name']),
						'description'   => $bar['description'],
						'class'         => 'wss-sidebar-widget',
						'before_widget' => '<li id="%1" class="widget %2">',
						'after_widget'  => '</li>',
						'before_title'  => '<h2>',
						'after_title'   => '</h2>'
					);
					register_sidebar( $args );
				}
			}
		}

	}


	/**
	 * Registers the neccesary ACF field groups and fields
	 *
	 * @return void
	 **/
	public static function register_acf_fields(){

		if(WOO_SUBSHOPS_DEV){
			add_action('init', array('wss_admin', 'export_acf_fields'), 999);
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
	public static function include_field_groups(){

		$path 	= dirname(__FILE__).'/acf/field_groups/';
		$files 	= glob($path.'/group_*.php');

		if(!$files)
			return;

		foreach($files as $file){
			$group = include $file;
			if(!is_array($group))
				continue;
			json_encode($group);
			acf_add_local_field_group($group);
		}

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
	public static function export_acf_fields(){

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
			
			self::set_var('is_exporting', true);

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

			self::set_var('is_exporting', false);

		}
	}



	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function alter_field_in_shops($field){

		if(self::get_var('is_exporting') === true)
			return $field;

		if($shops = self::get(array('posts_per_page' => -1))){
			$field['choices'] = array('main' => __('Main shop', 'wss'));
			foreach($shops as $shop){
				$field['choices'][$shop->ID] = $shop->post_title;
			}
		}
		return $field;
	}

}

?>