jQuery( document ).ready(function(){

	var sandbox_checkbox = jQuery('#woocommerce_pr_dhl_paket_dhl_sandbox');
	DHLSandboxEnabled( sandbox_checkbox );

	sandbox_checkbox.on('click', function(evt){
		DHLSandboxEnabled( jQuery( this ) );
	});

	var logo_checkbox = jQuery('#woocommerce_pr_dhl_paket_dhl_add_logo');
	DHLLogoEnabled( logo_checkbox );

	logo_checkbox.on('click', function(evt){
		DHLLogoEnabled( jQuery(this) );
	});
});

function DHLLogoEnabled( logo_checkbox ){
	var shipper_reference 		= jQuery('#woocommerce_pr_dhl_paket_dhl_shipper_reference');
	var tr_shipper_ref 			= shipper_reference.closest('tr');

	if( logo_checkbox.prop('checked') == true ){

		tr_shipper_ref.show();

	}else{

		tr_shipper_ref.hide();

	}
}

function DHLSandboxEnabled( sandbox_checkbox ){
	var api_settings_username 	= jQuery('#woocommerce_pr_dhl_paket_dhl_api_user');
	var api_settings_password 	= jQuery('#woocommerce_pr_dhl_paket_dhl_api_pwd');
	var account_number 			= jQuery('#woocommerce_pr_dhl_paket_dhl_account_num');

	var api_sandbox_username 	= jQuery('#woocommerce_pr_dhl_paket_dhl_api_sandbox_user');
	var api_sandbox_password 	= jQuery('#woocommerce_pr_dhl_paket_dhl_api_sandbox_pwd');
	var tr_sandbox_username 	= api_sandbox_username.closest('tr');
	var tr_sandbox_password 	= api_sandbox_password.closest('tr');

	if( sandbox_checkbox.prop('checked') == true ){

		// api_settings_username.val( dhl_paket_settings_obj.username );
		// api_settings_password.val( dhl_paket_settings_obj.pass );
		// account_number.val( dhl_paket_settings_obj.account_no );

		api_settings_username.prop('readonly', true );
		api_settings_password.prop('readonly', true );
		account_number.prop('readonly', true );

		tr_sandbox_username.show();
		tr_sandbox_password.show();
	}else{
		api_settings_username.prop('readonly', false );
		api_settings_password.prop('readonly', false );
		account_number.prop('readonly', false );

		tr_sandbox_username.hide();
		tr_sandbox_password.hide();
	}
}