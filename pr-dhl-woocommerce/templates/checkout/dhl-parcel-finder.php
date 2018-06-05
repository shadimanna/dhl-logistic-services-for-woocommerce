<?php
defined( 'ABSPATH' ) or exit;
/*

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
    <form id="checkout_dhl_parcel_finder" method="post">

		<p class="form-row small-field">
			<input type="text" name="dhl_parcelfinder_postcode" class="input-text" placeholder="<?php esc_attr_e( 'Post Code', 'pr-shipping-dhl' ); ?>" id="dhl_parcelfinder_postcode" />
		</p>

		<p class="form-row small-field">
			<input type="text" name="dhl_parcelfinder_city" class="input-text" placeholder="<?php esc_attr_e( 'City', 'pr-shipping-dhl' ); ?>" id="dhl_parcelfinder_city" />
		</p>


		<p class="form-row large-field">
			<input type="text" name="dhl_parcelfinder_address" class="input-text" placeholder="<?php esc_attr_e( 'Address', 'pr-shipping-dhl' ); ?>" id="dhl_parcelfinder_address" />
		</p>
		
		<div class="clear"></div>

		<p class="form-row small-field">
			<input type="checkbox" name="dhl_branch_filter" class="input-checkbox" id="dhl_branch_filter" value="1" checked />
			<label for="dhl_branch_filter"><?php esc_attr_e( 'Branch', 'pr-shipping-dhl' ); ?></label>
			<img src="<?php echo $packstation_img; ?>" alt="" class="packstation_img">
			<img src="<?php echo $parcelshop_img; ?>" alt="" class="parcelshop_img">
		</p>

		<p class="form-row small-field">
			<input type="checkbox" name="dhl_postoffice_filter" class="input-checkbox" placeholder="" id="dhl_postoffice_filter" value="1" checked />
			<label for="dhl_postoffice_filter"><?php esc_attr_e( 'Post Office', 'pr-shipping-dhl' ); ?></label>
			<img src="<?php echo $post_office_img; ?>" alt="" class="post_office_img">
		</p>
		
		<p class="form-row small-field">
			<input type="submit" class="button" name="apply_parcel_finder" value="<?php esc_attr_e( 'Search', 'pr-shipping-dhl' ); ?>" />
		</p>
		
		<input type="hidden" name="dhl_parcelfinder_country" id="dhl_parcelfinder_country" />

		<input type="hidden" name="dhl_parcelfinder_nonce" value="<?php echo wp_create_nonce('dhl_parcelfinder') ?>" />

		<div class="clear"></div>
	</form>

	<div id="dhl_google_map"></div>
	
  </div>
</div>