<?php
/**
 * The main subshop class
 *
 * @class 		wss_subshop
 * @category	Class
 */
class wss_subshop {

	/**
	 * Runs on construction of the 
	 *
	 * @return voidcu_get_overview_img
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

	}


	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function __get($var){
		if(isset($this->object->$var))
			return $this->object->$var;
		return false;
	}

	/**
	 * Checks if a page is assigned to this shop
	 *
	 * @return void
	 **/
	function has_page($page_id){

		if($shop = get_field('wss_in_subshop', $page_id)){
			if($shop->ID == $this->ID)
				return true;
		}

		return false;

	}

}


?>