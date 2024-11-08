<?php
defined( 'ABSPATH' ) or exit;

$logo_url = PR_DHL_PLUGIN_DIR_URL . '/assets/img/dhl-official.png';
?>
<tr class="dhl-co-tr dhl-co-tr-fist">
	<td colspan="2"><img src="<?php echo esc_url( $logo_url ); ?>" alt="DHL logo" class="dhl-co-logo"></td>
</tr>

<tr class="dhl-co-tr">
	<th colspan="2"><?php esc_html_e( 'Closest drop-off point', 'dhl-for-woocommerce' ); ?><hr></th>
</tr>

<tr class="dhl-co-tr">
	<td colspan="2"><?php esc_html_e( 'Preferred delivery to a parcel shop/parcel locker close to the specified home address', 'dhl-for-woocommerce' ); ?></td>
</tr>

<tr class="dhl-co-tr">
	<th class="dhl-cdp"><?php esc_html_e( 'Delivery option', 'dhl-for-woocommerce' ); ?></th>
	<td class="dhl-cdp">
		<ul class="dhl-preferred-location">
			<li>
				<input
						checked="checked"
						type="radio"
						name="pr_dhl_cdp_delivery"
						data-index="0" id="dhl_home_deliver_option"
						value="no"
						class="" >
				<label for="dhl_home_deliver_option"><?php esc_html_e( 'Home delivery', 'dhl-for-woocommerce' ); ?></label>
			</li>
			<li>
				<input
						type="radio"
						name="pr_dhl_cdp_delivery"
						data-index="0" id="dhl_cdp_option"
						value="yes"
						class="" >
				<label for="dhl_cdp_option"><?php esc_html_e( 'Closest Drop Point', 'dhl-for-woocommerce' ); ?></label>
			</li>
		</ul>
	</td>
</tr>
<tr class="dhl-co-tr dhl-co-tr-last">
	<td colspan="2"></td>
</tr>