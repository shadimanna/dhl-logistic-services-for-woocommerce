

jQuery( document ).ready( function(){
    let args = {
        "wz_nav_style": "dots", // dots, tabs, progress
        "wz_ori" : "horizontal",
        "buttons": true,
        "navigation": 'all', // buttons, nav, all
        "next": '<span class="dashicons dashicons-arrow-right-alt2"></span>',
        "prev": '<span class="dashicons dashicons-arrow-left-alt2"></span>',
    };
    
    const wizard = new Wizard(args);
    
    wizard.init();

    var next_buttons = jQuery(".button-next");
    next_buttons.on( 'click', pr_dhl_wizard_next_button );

    var finish_button = jQuery(".button-finish");
    finish_button.on( 'click', pr_dhl_wizard_finish_button );
});

function pr_dhl_wizard_next_button( evt ) {
    evt.preventDefault();

    var active_wizard = jQuery( '.wizard-content .wizard-step.active' );
    var active_fields = active_wizard.find( '.wizard-dhl-field' );

    var all_fields = dhl_wizard_obj.all_fields;
    var field_values = [];

    active_fields.each( function( idx ) {
        var field       = jQuery( this );
        var field_name  = field.prop( 'name' );
        
        if ( all_fields.indexOf( field_name ) > -1 ) {
            var field_value = field.val();

            field_values.push( {
                'name': field_name,
                'value': field_value
            } );
        }
    } );

    pr_dhl_wizard_update_fields( field_values );

    jQuery('.wizard-btn.btn.next').click();
}

function pr_dhl_wizard_finish_button( evt ) {
    evt.preventDefault();

    jQuery('.wizard-btn.btn.submit').click();
}

function pr_dhl_wizard_update_fields( fields ) {
    for( var idx = 0; idx < fields.length; idx++ ) {
        var field_name = fields[ idx ].name;
        var field_value = fields[ idx ].value;

        jQuery( '#woocommerce_pr_dhl_paket_' + field_name ).val( field_value );
    }
}