jQuery(document).ready(function($) {
	
	var shop_id	 	= <?php echo $shop->ID ?>;
	var in_shops 	= $('#acf-field-wss_in_shops');
	var divisions 	= $('#acf-field-audi_divisions').parents('.postbox');
	var op 			= '<?php echo $rule['operator'] ?>';

	in_shops.change(function(){

		var match = false;
		
		in_shops.find('option:selected').each(function(){
			var opt = parseInt($(this).attr('value'));
			if(opt <?php echo $rule['operator'] ?> shop_id){
				console.log(opt+' '+op+' '+shop_id);
				match = true;
				return;
			}
		})

		if(match)
			divisions.show();
		else
			divisions.hide();
	})
	// IM HERE
});
