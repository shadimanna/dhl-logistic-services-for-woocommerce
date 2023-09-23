jQuery( document ).ready(function(){

	var sandbox_checkbox = jQuery('#woocommerce_pr_dhl_paket_dhl_sandbox');
	DHLSandboxEnabled( sandbox_checkbox );

	sandbox_checkbox.on('click', function(evt){
		DHLSandboxEnabled( jQuery( this ) );
	});

	const api_mode = jQuery('#woocommerce_pr_dhl_paket_dhl_default_api');
	const developer_account = jQuery('#woocommerce_pr_dhl_paket_dhl_developer_account');
	DHLAPIModeChanged(api_mode);

	function setupEventListeners() {
		api_mode.on('change', () => {
			DHLAPIModeChanged(api_mode);
		});

		developer_account.on('change', () => {
			DHLAPIModeChanged(api_mode);
		});
	}
	setupEventListeners();

	var logo_checkbox = jQuery('#woocommerce_pr_dhl_paket_dhl_add_logo');
	DHLLogoEnabled( logo_checkbox );

	logo_checkbox.on('click', function(evt){
		DHLLogoEnabled( jQuery(this) );
	});

	DHLPaketMenuBuilder();
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
	var api_mode 				= jQuery('#woocommerce_pr_dhl_paket_dhl_default_api');
	var developer_account 		= jQuery('#woocommerce_pr_dhl_paket_dhl_developer_account');


	if( sandbox_checkbox.prop('checked') == true ){

		// api_settings_username.val( dhl_paket_settings_obj.username );
		// api_settings_password.val( dhl_paket_settings_obj.pass );
		// account_number.val( dhl_paket_settings_obj.account_no );

		api_settings_username.prop('readonly', true );
		api_settings_password.prop('readonly', true );
		account_number.prop('readonly', true );

		if ( 'rest-api' === api_mode.val() ) {
			developer_account.closest('tr').show();

			if ( true !== developer_account.prop('checked') ) {
				return;
			}
		}

		tr_sandbox_username.show();
		tr_sandbox_password.show();

	}else{
		if ( 'rest-api' === api_mode.val() ) {
			developer_account.closest('tr').hide();
		}

		api_settings_username.prop('readonly', false );
		api_settings_password.prop('readonly', false );
		account_number.prop('readonly', false );

		tr_sandbox_username.hide();
		tr_sandbox_password.hide();
	}
}

function DHLAPIModeChanged(api_mode) {
	const developer_account = jQuery('#woocommerce_pr_dhl_paket_dhl_developer_account');
	const sandbox_checkbox = jQuery('#woocommerce_pr_dhl_paket_dhl_sandbox');

	const tr_sandbox_username = jQuery('#woocommerce_pr_dhl_paket_dhl_api_sandbox_user').closest('tr');
	const tr_sandbox_password = jQuery('#woocommerce_pr_dhl_paket_dhl_api_sandbox_pwd').closest('tr');

	function toggleElement(element, condition) {
		element.closest('tr').toggle(condition);
	}

	const isRestApiMode = 'rest-api' === api_mode.val();
	const isSandboxChecked = sandbox_checkbox.prop('checked');
	const isDeveloperAccountChecked = developer_account.prop('checked');

	// Hide or show developer_account based on conditions
	toggleElement(developer_account, isRestApiMode && isSandboxChecked);

	if (isRestApiMode && isSandboxChecked) {
		// If developer_account checkbox is checked, show username and password fields
		toggleElement(tr_sandbox_username, isDeveloperAccountChecked);
		toggleElement(tr_sandbox_password, isDeveloperAccountChecked);
	} else if (!isRestApiMode && isSandboxChecked) {
		// Always show username and password fields if not in rest-api mode but sandbox is checked
		toggleElement(tr_sandbox_username, true);
		toggleElement(tr_sandbox_password, true);
	} else {
		// Hide username and password fields otherwise
		toggleElement(tr_sandbox_username, false);
		toggleElement(tr_sandbox_password, false);
	}
}

function DHLPaketMenuBuilder(){
	var dhlpaket_settings_menu_collection = [];
	var dhlpaket_tab_menu_container = jQuery( '.dhlpaket_tab_menu' );

	jQuery( document.body ).on( 'dhlpaket:init_settings_menu', function() {
		jQuery( '#dhlpaket_shipping_method_settings' ).find( 'h3' ).each( function(e) {
			var tab_item = jQuery( '<a href="#" id="dhlpaket_tab_menu_item_' + e + '" class="dhlpaket_tab_menu_item">' + jQuery( this ).text() + '</a>' );
			dhlpaket_tab_menu_container.append( tab_item );

			var dhlpaket_settings = {
				title: jQuery(this),
				tab_item: dhlpaket_tab_menu_container.find( '#dhlpaket_tab_menu_item_' + e ),
				description: jQuery(this).nextAll('p:first'),
				settings: jQuery(this).nextAll('table:first')
			};
	
			dhlpaket_settings.title.attr( 'data-index', e );
			dhlpaket_settings.tab_item.attr( 'data-index', e );
			dhlpaket_settings.description.attr( 'data-index', e );
			dhlpaket_settings.settings.attr( 'data-index', e );
	
			// Add to collection
			dhlpaket_settings_menu_collection[ e ] = dhlpaket_settings;
	
			// Add triggers
			dhlpaket_settings.tab_item.hover(function() {
				jQuery( document.body ).trigger( 'dhlpaket:highlight_menu', [ jQuery(this).data('index') ] );
			}, function() {
				jQuery( document.body ).trigger( 'dhlpaket:unhighlight_menu', [ jQuery(this).data('index') ] );
			});
	
			dhlpaket_settings.description.hover(function() {
				jQuery(document.body).trigger( 'dhlpaket:highlight_menu', [ jQuery(this).data('index') ] );
			}, function() {
				jQuery(document.body).trigger( 'dhlpaket:unhighlight_menu', [ jQuery(this).data('index') ] );
			});
	
			dhlpaket_settings.tab_item.on( 'click', function( e ) {
				e.preventDefault();

				jQuery(document.body).trigger( 'dhlpaket:deselect_settings' );
				jQuery(document.body).trigger( 'dhlpaket:select_settings', [ jQuery(this).data('index') ] );
			});
	
			dhlpaket_settings.description.on( 'click', function() {
				jQuery(document.body).trigger('dhlpaket:deselect_settings');
				jQuery(document.body).trigger('dhlpaket:select_settings', [ jQuery(this).data('index') ] );
			});
		} );

		// Select first setting
		jQuery(document.body).trigger('dhlpaket:deselect_settings');
		jQuery(document.body).trigger('dhlpaket:select_settings', [0]);
	} ).on( 'dhlpaket:highlight_menu', function() { 

	} ).on( 'dhlpaket:unhighlight_menu', function() {

	} ).on( 'dhlpaket:deselect_settings', function() {
		jQuery.each(dhlpaket_settings_menu_collection, function(e, dhlpaket_settings) {
			dhlpaket_settings.tab_item.removeClass('dhlpaket-active');
            dhlpaket_settings.title.removeClass('dhlpaket-active');
            dhlpaket_settings.description.removeClass('dhlpaket-active');
            dhlpaket_settings.settings.removeClass('dhlpaket-active');
        });
	} ).on( 'dhlpaket:select_settings', function( e, index ) {
		dhlpaket_settings_menu_collection[index].tab_item.addClass('dhlpaket-active');
		dhlpaket_settings_menu_collection[index].title.addClass( 'dhlpaket-active' );
        dhlpaket_settings_menu_collection[index].description.addClass( 'dhlpaket-active' );
        dhlpaket_settings_menu_collection[index].settings.addClass( 'dhlpaket-active' );
        // Sending out event for other scripts
        jQuery(document.body).trigger( 'dhlpaket:settings_clicked', [ dhlpaket_settings_menu_collection[index].title.attr( 'id' ) ] );
	} );

	jQuery(document.body).trigger('dhlpaket:init_settings_menu');
}