<?php

/**
 * WP Action
 * 
 *
 * @author Troels Abrahamsen
 **/


class wss_acf extends wss_init {

   public static function init(){

        add_filter('acf/location/rule_types', array('wss_acf', 'location_rules'));
        add_filter('acf/location/rule_values/assigned_shop_id', array('wss_acf', 'location_rule_assigned_shop_id'));
        add_filter('acf/location/rule_values/assigned_shop_name', array('wss_acf', 'location_rule_assigned_shop_name'));
        add_filter('acf/location/rule_values/shop_id_has_user', array('wss_acf', 'location_rule_shop_id_has_user'));
        add_filter('acf/location/rule_values/shop_name_has_user', array('wss_acf', 'location_rule_shop_name_has_user'));

        add_filter('acf/location/rule_match/assigned_shop_name', array('wss_acf', 'match_rule_assigned_shop_name'), 10, 3);
        add_filter('acf/location/rule_match/shop_id_has_user', array('wss_acf', 'match_rule_shop_id_has_user'), 10, 3);
        add_filter('acf/location/rule_match/shop_name_has_user', array('wss_acf', 'match_rule_shop_name_has_user'), 10, 3);

    }
    
    function location_rules($choices)
    {
        $choices['WSS']['assigned_shop_id']      = 'Assigned shop (by ID)';
        $choices['WSS']['assigned_shop_name']    = 'Assigned shop (by name)';
        $choices['WSS']['shop_id_has_user']      = 'User is in shop (by ID)';
        $choices['WSS']['shop_name_has_user']    = 'User is in shop (by name)';
     
        return $choices;
    }

    function location_rule_assigned_shop_id($choices)
    {
        $shops = wss::get(array('posts_per_page' => -1));
        if($shops){
            foreach($shops as $shop){
                $choices[ $shop->ID ] = $shop->post_title.' ('.$shop->ID.')';
            }
        }
        
        return $choices;
    }

    function location_rule_assigned_shop_name($choices)
    {
        $shops = wss::get(array('posts_per_page' => -1));
        if($shops){
            foreach($shops as $shop){
                $choices[ $shop->post_name ] = $shop->post_title.' ('.$shop->post_name.')';
            }
        }
        return $choices;
    }



    function location_rule_shop_id_has_user($choices)
    {
        $shops = wss::get(array('posts_per_page' => -1));
        if($shops){
            foreach($shops as $shop){
                $choices[ $shop->ID ] = $shop->post_title.' ('.$shop->ID.')';
            }
        }
     
        return $choices;
    }

    function location_rule_shop_name_has_user($choices)
    {
        $shops = wss::get(array('posts_per_page' => -1));
        if($shops){
            foreach($shops as $shop){
                $choices[ $shop->post_name ] = $shop->post_title.' ('.$shop->post_name.')';
            }
        }
     
        return $choices;
    }


    function match_rule_assigned_shop_name($match, $rule, $options){
        if($options['post_type'] === 'product'){

            $shop = $rule['value'];
            if($shop = new wss_subshop($shop)){
                
                ob_start();
                require 'location_rules/acf_location_rule_js.php';
                $js = ob_get_clean();
                self::inlinewp('wss')->enqueue_js($js);

                if($rule['operator'] == "=="){
                    $match = $shop->has_product($options['post_id']);
                }
                elseif($rule['operator'] == "!="){
                    var_dump($shop->has_product($options['post_id']));
                    if(!$shop->has_product($options['post_id']))
                        $match = true;
                }

            }

        }

        return $match;
    }


    function match_rule_shop_id_has_user($match, $rule, $options){

        global $pagenow;
        $showon = array('user-edit.php', 'profile.php');
        if(!in_array($pagenow, $showon)){
            return false;
        }

        $shop = $rule['value'];

        if($shop = new wss_subshop($shop)){
            $userid = get_current_user_id();
            if($rule['operator'] == "=="){
                $match = $shop->has_user($userid);
            }
            elseif($rule['operator'] == "!="){
                if(!$match = $shop->has_user($userid))
                    $match = true;
            }

        }

        return $match;
    }

    function match_rule_shop_name_has_user($match, $rule, $options){

        global $pagenow;
        $showon = array('user-edit.php', 'profile.php');
        if(!in_array($pagenow, $showon)){
            return false;
        }

        $shop = $rule['value'];

        if($shop = new wss_subshop($shop)){
            $userid = get_current_user_id();
            if($rule['operator'] == "=="){
                $match = $shop->has_user($userid);
            }
            elseif($rule['operator'] == "!="){
                if(!$match = $shop->has_user($userid))
                    $match = true;
            }

        }

        return $match;
    }

}


?>