<?php 
	return array(
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
	);
 ?>