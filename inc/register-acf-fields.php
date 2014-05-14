<?php if(function_exists("register_field_group"))
{
	register_field_group(array (
		'id' => 'acf_woocommerce-subshop-options',
		'title' => 'Woocommerce subshop options',
		'fields' => array (
			array (
				'key' => 'field_5371130fd8d87',
				'label' => 'Shops rewrite base',
				'name' => 'wss_rewrite_base',
				'type' => 'validated_field',
				'instructions' => 'You need to visit Settings->Permalinks after you\'ve changed the rewrite base. This is necessary to regenerate the rewrite rules.',
				'read_only' => 'false',
				'drafts' => 'true',
				'sub_field' => array (
					'type' => 'text',
					'key' => 'field_5371130fd8d87',
					'name' => 'wss_rewrite_base',
					'_name' => 'wss_rewrite_base',
					'id' => 'acf-field-wss_rewrite_base',
					'value' => '',
					'field_group' => 192,
					'default_value' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
					'formatting' => 'none',
					'maxlength' => '',
				),
				'mask' => 'a9',
				'function' => 'regex',
				'pattern' => '[\\d\\w-_]+',
				'message' => 'No spaces or special characters please',
				'unique' => 'non-unique',
				'unique_statuses' => array (
					0 => 'publish',
					1 => 'future',
				),
			),
		),
		'location' => array (
			array (
				array (
					'param' => 'options_page',
					'operator' => '==',
					'value' => 'wss-options-general',
					'order_no' => 0,
					'group_no' => 0,
				),
			),
		),
		'options' => array (
			'position' => 'normal',
			'layout' => 'no_box',
			'hide_on_screen' => array (
			),
		),
		'menu_order' => 0,
	));
}
 ?>