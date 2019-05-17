jQuery( function( $ ) {

    var wc_shipment_dhl_dp_label_items = {
        // init Class
        init: function() {
            $( '#woocommerce-dhl-dp-order' )
                .on( 'click', '#pr_dhl_add_to_order', this.add_item_to_order.bind(this) );

            $( '#woocommerce-dhl-dp-order' )
                .on( 'click', '.pr_dhl_order_remove_item', this.remove_item_from_order.bind(this) );

            $( '#woocommerce-dhl-dp-order' )
                .on( 'click', '#pr_dhl_finalize_order', this.finalize_order.bind(this) );
        },

        lock_order_controls: function () {
            $( '#pr_dhl_add_to_order, .pr_dhl_order_remove_item, #pr_dhl_finalize_order' ).attr( 'disabled', 'disabled' );
        },

        unlock_order_controls: function () {
            $( '#pr_dhl_add_to_order, .pr_dhl_order_remove_item, #pr_dhl_finalize_order' ).removeAttr( 'disabled' );
        },

        add_item_to_order: function () {
            var data = {
                action:                   'wc_shipment_dhl_add_order_item',
                order_id:                 woocommerce_admin_meta_boxes.post_id,
                pr_dhl_order_nonce:       $( '#pr_dhl_order_nonce' ).val()
            };

            this.lock_order_controls();
            $.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
                $( '#pr_dhl_order_items_table' ).replaceWith( response );
                this.unlock_order_controls();
            }.bind(this) );
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

            this.lock_order_controls();
            $.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
                $( '#pr_dhl_order_items_table' ).replaceWith( response );
                this.unlock_order_controls();
            }.bind(this) );
        },

        finalize_order: function () {
            var data = {
                action:                   'wc_shipment_dhl_finalize_order',
                order_id:                 woocommerce_admin_meta_boxes.post_id,
                pr_dhl_order_nonce:       $( '#pr_dhl_order_nonce' ).val()
            };

            this.lock_order_controls();
            $.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
                this.unlock_order_controls();
            }.bind(this) );
        },
    };

    wc_shipment_dhl_dp_label_items.init();

} );
