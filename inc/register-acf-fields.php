<?php if(function_exists("register_field_group"))
{
	register_field_group(array (
		'id' => 'acf_wssoptions',
		'title' => 'wss:options',
		'fields' => array (
			array (
				'key' => 'field_537f08601a8e0',
				'label' => 'General',
				'name' => '',
				'type' => 'tab',
			),
			array (
				'key' => 'field_5371130fd8d87',
				'label' => 'Shops rewrite base',
				'name' => 'wss_rewrite_base',
				'type' => 'text',
				'instructions' => 'You need to visit Settings->Permalinks after you\'ve changed the rewrite base. This is necessary to regenerate the rewrite rules.',
				'default_value' => '',
				'placeholder' => '',
				'prepend' => '',
				'append' => '',
				'formatting' => 'html',
				'maxlength' => '',
			),
			array (
				'key' => 'field_537f088f1a8e2',
				'label' => 'Developer',
				'name' => '',
				'type' => 'tab',
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
				'multiple' => 1,
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
				array (
					'param' => 'page_type',
					'operator' => '!=',
					'value' => 'front_page',
					'order_no' => 1,
					'group_no' => 0,
				),
				array (
					'param' => 'post_type',
					'operator' => '!=',
					'value' => 'posts_page',
					'order_no' => 2,
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
				'key' => 'field_537f05ff5e335',
				'label' => 'Access',
				'name' => '',
				'type' => 'tab',
			),
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
				'type' => 'repeater',
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
				'sub_fields' => array (
					array (
						'key' => 'field_538d917acf281',
						'label' => 'User',
						'name' => 'user',
						'type' => 'user',
						'column_width' => 50,
						'role' => array (
							0 => 'all',
						),
						'field_type' => 'select',
						'allow_null' => 0,
					),
					array (
						'key' => 'field_538d91becf282',
						'label' => 'Privileges',
						'name' => 'privileges',
						'type' => 'select',
						'column_width' => '',
						'choices' => array (
						),
						'default_value' => 'customer',
						'allow_null' => 1,
						'multiple' => 1,
					),
				),
				'row_min' => '',
				'row_limit' => '',
				'layout' => 'table',
				'button_label' => 'Add user',
			),
			array (
				'key' => 'field_538dac013c035',
				'label' => 'Grant access to users with these roles',
				'name' => 'wss_roles',
				'type' => 'repeater',
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
				'sub_fields' => array (
					array (
						'key' => 'field_538dac013c036',
						'label' => 'Role',
						'name' => 'role',
						'type' => 'select',
						'column_width' => 50,
						'choices' => array (
							'administrator' => 'Administrator',
							'editor' => 'Editor',
							'author' => 'Author',
							'contributor' => 'Contributor',
							'subscriber' => 'Subscriber',
							'customer' => 'Customer',
							'shop_manager' => 'Shop Manager',
						),
						'default_value' => '',
						'allow_null' => 0,
						'multiple' => 0,
					),
					array (
						'key' => 'field_538dac013c037',
						'label' => 'Privileges',
						'name' => 'privileges',
						'type' => 'select',
						'column_width' => '',
						'choices' => array (
						),
						'default_value' => '',
						'allow_null' => 1,
						'multiple' => 1,
					),
				),
				'row_min' => '',
				'row_limit' => '',
				'layout' => 'table',
				'button_label' => 'Add user',
			),
			array (
				'key' => 'field_537f05f75e334',
				'label' => 'Pages',
				'name' => '',
				'type' => 'tab',
			),
			array (
				'key' => 'field_537f0a5beb5f1',
				'label' => 'Checkout page',
				'name' => 'wss_checkout_page',
				'type' => 'post_object',
				'post_type' => array (
					0 => 'page',
				),
				'taxonomy' => array (
					0 => 'all',
				),
				'allow_null' => 1,
				'multiple' => 0,
			),
			array (
				'key' => 'field_537f0a87eb5f2',
				'label' => 'Cart page',
				'name' => 'wss_cart_page',
				'type' => 'post_object',
				'post_type' => array (
					0 => 'page',
				),
				'taxonomy' => array (
					0 => 'all',
				),
				'allow_null' => 1,
				'multiple' => 0,
			),
			array (
				'key' => 'field_537f0a9eeb5f3',
				'label' => 'My account page',
				'name' => 'wss_myaccount_page',
				'type' => 'post_object',
				'post_type' => array (
					0 => 'page',
				),
				'taxonomy' => array (
					0 => 'all',
				),
				'allow_null' => 1,
				'multiple' => 0,
			),
			array (
				'key' => 'field_5384a13cff9c1',
				'label' => 'Menues',
				'name' => '',
				'type' => 'tab',
			),
			array (
				'key' => 'field_5384a155ff9c2',
				'label' => '',
				'name' => 'wss_menu_locations',
				'type' => 'repeater',
				'instructions' => 'Add as many menu locations as you like.',
				'sub_fields' => array (
					array (
						'key' => 'field_5384a17bff9c3',
						'label' => 'Location name',
						'name' => 'name',
						'type' => 'text',
						'required' => 1,
						'column_width' => '',
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'formatting' => 'none',
						'maxlength' => '',
					),
					array (
						'key' => 'field_5384a28aa3e9e',
						'label' => 'Description',
						'name' => 'description',
						'type' => 'text',
						'column_width' => '',
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'formatting' => 'none',
						'maxlength' => '',
					),
				),
				'row_min' => '',
				'row_limit' => '',
				'layout' => 'table',
				'button_label' => 'Add location',
			),
			array (
				'key' => 'field_5385852f75286',
				'label' => 'Sidebars',
				'name' => '',
				'type' => 'tab',
			),
			array (
				'key' => 'field_5385853c75287',
				'label' => '',
				'name' => 'wss_sidebars',
				'type' => 'repeater',
				'sub_fields' => array (
					array (
						'key' => 'field_5385855375288',
						'label' => 'Name',
						'name' => 'name',
						'type' => 'text',
						'column_width' => '',
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'formatting' => 'none',
						'maxlength' => '',
					),
					array (
						'key' => 'field_538587299bb61',
						'label' => 'Description',
						'name' => 'description',
						'type' => 'text',
						'column_width' => '',
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'formatting' => 'none',
						'maxlength' => '',
					),
				),
				'row_min' => '',
				'row_limit' => '',
				'layout' => 'table',
				'button_label' => 'Add sidebar',
			),
			array (
				'key' => 'field_5385946ec35cd',
				'label' => 'Layout',
				'name' => '',
				'type' => 'tab',
			),
			array (
				'key' => 'field_5385947cc35ce',
				'label' => '',
				'name' => 'wss_layout_fields',
				'type' => 'flexible_content',
				'instructions' => 'Add elements that you want to use on the shop page',
				'layouts' => array (
					array (
						'label' => 'Image',
						'name' => 'image',
						'display' => 'table',
						'min' => '',
						'max' => '',
						'sub_fields' => array (
							array (
								'key' => 'field_538594abc35cf',
								'label' => 'Handle',
								'name' => 'handle',
								'type' => 'text',
								'column_width' => 30,
								'default_value' => '',
								'placeholder' => '',
								'prepend' => '',
								'append' => '',
								'formatting' => 'html',
								'maxlength' => '',
							),
							array (
								'key' => 'field_538594dec106c',
								'label' => 'Image',
								'name' => 'value',
								'type' => 'image',
								'column_width' => 70,
								'save_format' => 'object',
								'preview_size' => 'thumbnail',
								'library' => 'all',
							),
						),
					),
					array (
						'label' => 'Color',
						'name' => 'color',
						'display' => 'table',
						'min' => '',
						'max' => '',
						'sub_fields' => array (
							array (
								'key' => 'field_5385a111621d3',
								'label' => 'Handle',
								'name' => 'handle',
								'type' => 'text',
								'column_width' => 30,
								'default_value' => '',
								'placeholder' => '',
								'prepend' => '',
								'append' => '',
								'formatting' => 'none',
								'maxlength' => '',
							),
							array (
								'key' => 'field_5385a130621d4',
								'label' => 'Color',
								'name' => 'value',
								'type' => 'color_picker',
								'column_width' => 70,
								'default_value' => '',
							),
						),
					),
				),
				'button_label' => 'Add element',
				'min' => '',
				'max' => '',
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