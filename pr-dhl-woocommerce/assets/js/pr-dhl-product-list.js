jQuery(function(){
	jQuery('#the-list').on('click', '.editinline', function(){
	
		/**
		 * Extract metadata and put it as the value for the custom field form
		 */
		inlineEditPost.revert();
	
		var post_id = jQuery(this).closest('tr').attr('id');
	
		post_id = post_id.replace("post-", "");
	
		var dhl_hs_code_inline_data 			= jQuery('#dhl_hs_code_inline_' + post_id),
			dhl_hs_manuf_country_inline_data 	= jQuery('#dhl_manufacture_country_inline_' + post_id),
			$wc_inline_data 					= jQuery('#woocommerce_inline_' + post_id );
	
		jQuery('input[name="change_dhl_hs_code"]', '.inline-edit-row').val(dhl_hs_code_inline_data.find("#dhl_hs_code").text());
		console.log( dhl_hs_manuf_country_inline_data.find('#dhl_manufacture_country').text() );
		jQuery('select.change_dhl_manufacture_country option', '.inline-edit-row').each(function(){
			
			jQuery( this ).removeAttr('selected');
			
			if( jQuery(this).prop('value') == dhl_hs_manuf_country_inline_data.find('#dhl_manufacture_country').text() ){
				jQuery( this ).attr('selected', 'selected' );
			}
		});
		/**
		 * Only show custom field for appropriate types of products (simple)
		 */
		//var product_type = $wc_inline_data.find('.product_type').text();
		
		jQuery('.dhl_hs_code_inline', '.inline-edit-row').show();
		jQuery('.dhl_manufacture_country_inline', '.inline-edit-row').show();
	
	});
});