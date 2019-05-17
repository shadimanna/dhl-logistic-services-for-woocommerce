jQuery( function( $ ) {

    var wc_dhl_dp_order_items = {
        // init Class
        init: function() {
            $( '#woocommerce-dhl-dp-order' )
                .on( 'click', '#pr_dhl_add_to_order', this.add_item_to_order );

            $( '#woocommerce-dhl-dp-order' )
                .on( 'click', '.pr_dhl_order_remove_item', this.remove_item_from_order );

            $( '#woocommerce-dhl-dp-order' )
                .on( 'click', '#pr_dhl_finalize_order', this.finalize_order );

            this.update();
        },

        update: function () {
            var num_rows = $( '#pr_dhl_order_items_table tbody tr:not("#pr_dhl_no_items_msg")' ).length;

            // If there are no items, disable the finalize order button
            if ( num_rows > 0 ) {
                $( '#pr_dhl_finalize_order' ).removeClass( 'disabled' );
            } else {
                // The "disabled" class is used to not accidentally re-enable the button after an AJAX call
                // The WordPress "disabled" class handles the disabling while the AJAX locking uses the HTML5 attribute
                $( '#pr_dhl_finalize_order' ).addClass( 'disabled' );
            }
        },

        lock_order_controls: function () {
            $( '#pr_dhl_add_to_order, .pr_dhl_order_remove_item, #pr_dhl_finalize_order' ).attr( 'disabled', 'disabled' );
        },

        unlock_order_controls: function () {
            $( '#pr_dhl_add_to_order, .pr_dhl_order_remove_item, #pr_dhl_finalize_order:not(.disabled)' ).removeAttr( 'disabled' );
        },


        add_item_to_order: function () {
            var data = {
                action:                   'wc_shipment_dhl_add_order_item',
                order_id:                 woocommerce_admin_meta_boxes.post_id,
                pr_dhl_order_nonce:       $( '#pr_dhl_order_nonce' ).val()
            };

            wc_dhl_dp_order_items.lock_order_controls();
            $.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
                $( '#pr_dhl_order_items_table' ).replaceWith( response );
                wc_dhl_dp_order_items.update();
                wc_dhl_dp_order_items.unlock_order_controls();
            } );
        },

        remove_item_from_order: function ( event ) {
            var click_target = $( event.target );
            var item_row = click_target.closest('tr');
            var item_barcode = item_row.find('.pr_dhl_item_barcode');
            var data = {
                action:                   'wc_shipment_dhl_remove_order_item',
                item_barcode:             item_barcode.text(),
                pr_dhl_order_nonce:       $( '#pr_dhl_order_nonce' ).val()
            };

            wc_dhl_dp_order_items.lock_order_controls();
            $.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
                $( '#pr_dhl_order_items_table' ).replaceWith( response );
                wc_dhl_dp_order_items.update();
                wc_dhl_dp_order_items.unlock_order_controls();
            } );
        },

        finalize_order: function () {
            var data = {
                action:                   'wc_shipment_dhl_finalize_order',
                order_id:                 woocommerce_admin_meta_boxes.post_id,
                pr_dhl_order_nonce:       $( '#pr_dhl_order_nonce' ).val()
            };

            wc_dhl_dp_order_items.lock_order_controls();
            $.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
                wc_dhl_dp_order_items.update();
                wc_dhl_dp_order_items.unlock_order_controls();
            } );
        },
    };

    wc_dhl_dp_order_items.init();

} );
