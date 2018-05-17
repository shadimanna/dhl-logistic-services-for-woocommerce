<?php
defined( 'ABSPATH' ) or exit;
/*
$packstation_img = PR_DHL_PLUGIN_DIR_URL . '/assets/img/packstation.png';
$parcelshop_img = PR_DHL_PLUGIN_DIR_URL . '/assets/img/parcelshop.png';
$post_office_img = PR_DHL_PLUGIN_DIR_URL . '/assets/img/post_office.png';

try {
  $shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();
  $dhl_obj = PR_DHL()->get_dhl_factory();
} catch (Exception $e) {
    return;
}
*/
?>

<div style="display:none">
  <div id="dhl_parcel_finder_form">
    <!-- Create form and call via AJAX parcel finder API -->
    <form class="checkout_dhl_parcel_finder" method="post">

		<p class="form-row form-row-first">
			<input type="text" name="dhl_parcelfinder_postcode" class="input-text" placeholder="<?php esc_attr_e( 'Post Code', 'pr-shipping-dhl' ); ?>" id="dhl_parcelfinder_postcode" />
		</p>

		<p class="form-row form-row-last">
			<input type="text" name="dhl_parcelfinder_city" class="input-text" placeholder="<?php esc_attr_e( 'City', 'pr-shipping-dhl' ); ?>" id="dhl_parcelfinder_city" />
		</p>

		<p class="form-row form-row-first">
			<input type="text" name="dhl_parcelfinder_address" class="input-text" placeholder="<?php esc_attr_e( 'Address', 'pr-shipping-dhl' ); ?>" id="dhl_parcelfinder_address" />
		</p>
		
		<input type="hidden" name="dhl_parcelfinder_country" id="dhl_parcelfinder_country" />

		<input type="hidden" name="dhl_parcelfinder_nonce" value="<?php echo wp_create_nonce('dhl_parcelfinder') ?>" />

		<p class="form-row form-row-last">
			<input type="submit" class="button" name="apply_parcel_finder" value="<?php esc_attr_e( 'Search', 'pr-shipping-dhl' ); ?>" />
		</p>
		

		<div class="clear"></div>
	</form>

	<div id="dhl_google_map"></div>
	
  </div>
</div>