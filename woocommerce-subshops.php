<?php

/*
Plugin Name: Woocommerce | Subshops
Description: Create subshops in the shop
Text Domain: woo_subshops
*/

$curr_shop = false;
$wss_main_session_data = array();

$__woo_path = pathinfo(__FILE__);
define('WOO_SUBSHOPS_DIR', $__woo_path['dirname']);
unset($__woo_path);

require('inc/wss.class.php');
require('inc/wss_init.class.php');
require('inc/wss_subshop.class.php');

wss_init::init();

?>