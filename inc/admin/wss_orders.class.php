<?php

/**
* 
*/
class wss_orders {
	
	function init(){
		add_action('restrict_manage_posts', array('wss_orders', 'add_subshop_filter_html'));
		add_action('request', array('wss_orders', 'filter_by_subshop'));
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function add_subshop_filter_html(){
		global $typenow;
		if($typenow == 'shop_order'){
			include('views/html_subshop_filter.php');
		}
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function filter_by_subshop($vars){
		global $typenow;
		if($typenow == 'shop_order' && isset( $_GET['shop_subshop'] ) && $_GET['shop_subshop'] > 0){
			$vars['meta_key'] = 'woo_subshop';
			$vars['meta_value'] = (int) $_GET['shop_subshop'];
		}
		return $vars;

	}

}
?>