<?php

/*
Plugin Name: Woocommerce | Subshops
Description: Create subshops in the shop
Text Domain: woo_subshops
*/

$__woo_path = pathinfo(__FILE__);
define('WOO_SUBSHOPS_DIR', $__woo_path['dirname']);
unset($__woo_path);

require('inc/wss.class.php');
require('inc/wss_init.class.php');
wss_init::init();

?>