jQuery( document ).ready(function(){

	var sandbox_checkbox = jQuery('#woocommerce_pr_dhl_paket_dhl_sandbox');
	DHLSandboxEnabled( sandbox_checkbox );
	
	sandbox_checkbox.on('click', function(evt){
		DHLSandboxEnabled( jQuery( this ) );
	});
	
});

function DHLSandboxEnabled( sandbox_checkbox ){
	var api_settings_username 	= jQuery('#woocommerce_pr_dhl_paket_dhl_api_user');
	var api_settings_password 	= jQuery('#woocommerce_pr_dhl_paket_dhl_api_pwd');
	var account_number 			= jQuery('#woocommerce_pr_dhl_paket_dhl_account_num');

	if( sandbox_checkbox.prop('checked') == true ){
		api_settings_username.val( '2222222222_01' );
		api_settings_password.val( 'pass' );
		account_number.val( '2222222222' );

		api_settings_username.prop('readonly', true );
		api_settings_password.prop('readonly', true );
		account_number.prop('readonly', true );
	}else{
		api_settings_username.prop('readonly', false );
		api_settings_password.prop('readonly', false );
		account_number.prop('readonly', false );
	}
}