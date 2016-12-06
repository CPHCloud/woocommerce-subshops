<?php

$active_plugins = get_option('active_plugins');
if(
    !(
    	in_array('woocommerce/woocommerce.php', $active_plugins)
    
    and
    	
    	in_array('advanced-custom-fields-pro/acf.php', $active_plugins)
    )
)
	return;


/*
Plugin Name: Woocommerce | Subshops
Description: Create subshops in the shop
Text Domain: wss
Domain Path: languages
*/

$curr_shop = false;
$wss_main_session_data = array();

$__woo_path = pathinfo(__FILE__);
define('WOO_SUBSHOPS_DIR', $__woo_path['dirname']);
unset($__woo_path);

require('inc/wss.class.php');
wss::load_plugins();
wss::init();

function curr_shop(){
	global $curr_shop;
	return $curr_shop;
}


?>