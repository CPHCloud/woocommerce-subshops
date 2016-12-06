<?php 

	return array(
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
	);


?>