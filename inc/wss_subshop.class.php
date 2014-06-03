<?php
/**
 * The main subshop class
 *
 * @class 		wss_subshop
 * @category	Class
 */
class wss_subshop {

	/**
	 * Runs on construction of wss_subshop object
	 *
	 * @return void
	**/
	function __construct($shop){

		/* Cast a numeric string as integer */
		if(is_numeric($shop)){
			$shop = (int)$shop;
		}

		/* Initiate based on $shop variable type */
		if(is_int($shop)){
			$this->ID 		= $shop;
			$this->object 	= wss::get($shop);
		}
		elseif(is_string($shop)){
			if($this->object 	= wss::get($shop)){
				$this->ID = $this->object->ID;
			}

		}
		elseif(is_object($shop) and $shop->post_type == 'woo_subshop'){
			$this->ID 		= $shop->ID;
			$this->object 	= $shop;
		}

		/* Setup the object variables */
		if($this->object){
			$this->name = $this->object->post_name;
		}
		
		$this->slug = wss::get_shop_base().'/'.$this->object->post_name;
		$this->url  = get_bloginfo('url').'/'.$this->slug;

		$this->pages = array(
			'cart' 		=> $this->get_page_id('cart'),
			'checkout' 	=> $this->get_page_id('checkout'),
			'myaccount' => $this->get_page_id('myaccount')
		);

		//aprint($this);

		$this->cached_fields = array();
	}


	/**
	 * getter
	 *
	 * @return void
	 **/
	function __get($var){
		if(isset($this->object->$var)){
			return $this->object->$var;
		}
		elseif($value = $this->cached_fields['wss_'.$var]){
			return $value;
		}
		elseif($value = get_field('wss_'.$var, $this->ID)){
			$this->cached_fields['wss_'.$var] = $value;
			return $value;
		}
		return false;
	}

	/**
	 * Checks if a page is assigned to this shop
	 *
	 * @return void
	 **/
	function has_page($page_id){
		
		/* First we check if the */
		if(in_array($page_id, $this->pages)){
			return true;
		}
		elseif($shops = get_field('wss_in_subshop', $page_id)){
			foreach($shops as $shop)
				if($shop->ID == $this->ID)
					return true;
		}

		return false;
	}


	/**
	 * Checks if a user has access to this shop.
	 *
	 * TODO 	This needs to be reworked in a future version as
	 * 			it does not scale very well.
	 *
	 * @return void
	 **/
	function has_user($user_id = false){

		if(!$user_id)
			$user_id = get_current_user_id();

		if($this->users){
			foreach($this->users as $row) {
				if($row['user']['ID'] == $user_id)
					return true;
			}
		}
		if($this->roles){
			$user = get_userdata( $user_id );
			/* $role is now our users role - duh */

			foreach($this->roles as $row) {
				if(in_array($row['role'], $user->roles))
					return true;
			}			
		}

		return false;
	}


	function has_privilege($privhandle, $user = false){
		$return = false;
		if(!$user)
			$user = get_current_user_id();
		$user = get_userdata($user);

		$privs = apply_filters('wss/privileges', wss_admin::$privileges);
		/* Do we settle this with a callback? */
		if(isset($privs[$privhandle])){
			if(is_callable($privs[$privhandle]['callback'])){
				$callback = $privs[$privhandle]['callback'];
				$args = array($privhandle, $user);
				if(is_array($callback) and count($callback) == 2){
					if(is_object($callback[0])){
						$return = call_user_method_array($callback[1], $callback[0], $args);
					}
					else{
						$function = implode('::', $callback);
						$return   = call_user_func_array($function, $args);
					}
				}
				elseif(is_string()){
					$return = call_user_func_array($callback, $args);
				}
			}
			else{
				/* We're just comparing privs - no callback */
				if($this->users){
					foreach($this->users as $row) {
						if($row['user']['ID'] == $user->ID and in_array($privhandle, $row['privileges']))
							return true;
					}
				}
				if($this->roles){
					/* $role is now our users role - duh */
					foreach($this->roles as $row) {
						if($row['role'] == $user->roles[0] and in_array($privhandle, $row['privileges']))
							return true;
					}			
				}

			}
		}
		return $return;
	}


	/**
	 * Checks if a product belongs to this shop.
	 *
	 * @return void
	 **/
	function has_product($product_id){

		if($in_shops = get_field('wss_in_shops', $product_id)){

			if(in_array($this->ID, $in_shops)){
				return true;
			}
			return false;
		}
		return false;
	}


	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function get_page_id($pagename){

		if($page = $this->{$pagename.'_page'}){
			return $page->ID;
		}
		return get_option('woocommerce_'.$pagename.'_page_id');

	}


	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function get_layout_field($handle){
		$els = $this->layout_fields;
		foreach ($els as $key => $field){
			if($field['handle'] == $handle){
				return $field['value'];
			}
		}
	}


}


?>