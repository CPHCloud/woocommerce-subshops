<?php

return register_field_group(array (
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

?>