<?php if(function_exists("register_field_group"))
{
	register_field_group(array (
		'id' => 'acf_wssoptions',
		'title' => 'wss:options',
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
			array (
				'key' => 'field_537b3ad99cbb8',
				'label' => 'Dev mode',
				'name' => 'wss_dev_mode',
				'type' => 'true_false',
				'message' => '',
				'default_value' => 0,
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
	register_field_group(array (
		'id' => 'acf_wsspages',
		'title' => 'wss:pages',
		'fields' => array (
			array (
				'key' => 'field_5374664958f90',
				'label' => 'Assign to subshop',
				'name' => 'wss_in_subshop',
				'type' => 'post_object',
				'instructions' => 'Assigning a page to a subshop will also affect all child pages',
				'post_type' => array (
					0 => 'woo_subshop',
				),
				'taxonomy' => array (
					0 => 'all',
				),
				'allow_null' => 1,
				'multiple' => 0,
			),
		),
		'location' => array (
			array (
				array (
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'page',
					'order_no' => 0,
					'group_no' => 0,
				),
			),
		),
		'options' => array (
			'position' => 'side',
			'layout' => 'no_box',
			'hide_on_screen' => array (
			),
		),
		'menu_order' => 0,
	));
	register_field_group(array (
		'id' => 'acf_wssproduct',
		'title' => 'wss:product',
		'fields' => array (
			array (
				'key' => 'field_537b3d38c830f',
				'label' => 'Add to shops',
				'name' => 'wss_in_shops',
				'type' => 'select',
				'instructions' => 'Select the shops you want this product to appear in. If none is chosen, the shop will be assigned to the main shop by default.',
				'choices' => array (
					'main' => 'Main shop',
					195 => 'Irma',
					191 => 'Audi',
				),
				'default_value' => 'main',
				'allow_null' => 0,
				'multiple' => 1,
			),
		),
		'location' => array (
			array (
				array (
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'product',
					'order_no' => 0,
					'group_no' => 0,
				),
			),
		),
		'options' => array (
			'position' => 'acf_after_title',
			'layout' => 'no_box',
			'hide_on_screen' => array (
			),
		),
		'menu_order' => 0,
	));
	register_field_group(array (
		'id' => 'acf_wsssubshop',
		'title' => 'wss:subshop',
		'fields' => array (
			array (
				'key' => 'field_537b1fb1e0668',
				'label' => 'Private',
				'name' => 'wss_private',
				'type' => 'true_false',
				'message' => '',
				'default_value' => 0,
			),
			array (
				'key' => 'field_537b20c442d0a',
				'label' => 'Grant access to these users',
				'name' => 'wss_users',
				'type' => 'user',
				'conditional_logic' => array (
					'status' => 1,
					'rules' => array (
						array (
							'field' => 'field_537b1fb1e0668',
							'operator' => '==',
							'value' => '1',
						),
					),
					'allorany' => 'all',
				),
				'role' => array (
					0 => 'customer',
					1 => 'shop_manager',
				),
				'field_type' => 'multi_select',
				'allow_null' => 0,
			),
		),
		'location' => array (
			array (
				array (
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'woo_subshop',
					'order_no' => 0,
					'group_no' => 0,
				),
			),
		),
		'options' => array (
			'position' => 'acf_after_title',
			'layout' => 'no_box',
			'hide_on_screen' => array (
			),
		),
		'menu_order' => 0,
	));
}
 ?>