<?php

$shops = wss::get(array('posts_per_page' => -1));

?>

<?php if($shops): ?>
<select name="shop_subshop" id="dropdown_woo_subshop">
	<option value="0">All shops</option>
	<?php foreach($shops as $shop):
			$shop = new wss_subshop($shop);
			$selected = '';
			if($shop->ID == $_GET['shop_subshop']){
				$selected = 'selected';
			}
	?>	
	<option value="<?php echo $shop->ID ?>" <?php echo $selected ?>><?php echo $shop->post_title ?></option>
	<?php endforeach; ?>
</select>
<?php endif; ?>

<?php
wc_enqueue_js("
	jQuery('select#dropdown_woo_subshop').width(150).chosen();
	");
?>