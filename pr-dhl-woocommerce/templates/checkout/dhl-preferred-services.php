<?php
defined( 'ABSPATH' ) or exit;

$logo_url = PR_DHL_PLUGIN_DIR_URL . '/assets/img/dhl-official.png';

try {
	$shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();
	$dhl_obj               = PR_DHL()->get_dhl_factory();
} catch ( Exception $e ) {
	return;
}
?>

<tr class="dhl-co-tr dhl-co-tr-fist">
	<td colspan="2"><img src="<?php echo esc_url( $logo_url ); ?>" alt="DHL logo" class="dhl-co-logo"></td>
</tr>

<tr class="dhl-co-tr">
	<th colspan="2"><?php echo esc_html_e( 'DHL Preferred Delivery. Delivered just as you wish.', 'dhl-for-woocommerce' ); ?><hr></th>
</tr>

<tr class="dhl-co-tr">
	<td colspan="2">
	<?php
	echo esc_html_e(
		'Thanks to the ﬂexible recipient services of DHL Preferred Delivery, you decide
when and where you want to receive your parcels.<br>
Please choose your preferred delivery option.',
		'dhl-for-woocommerce'
	);
	?>
	</td>
</tr>

<?php
if ( isset( $shipping_dhl_settings['dhl_preferred_day'] ) && $shipping_dhl_settings['dhl_preferred_day'] == 'yes' ) {
	?>

	<tr class="dhl-co-tr">
		<th colspan="2" class="dhl-pt"><?php esc_html_e( 'Delivery day: Delivery at your preferred day', 'dhl-for-woocommerce' ); ?>
		<span class="dhl-tooltip" title="<?php esc_html_e( 'Choose one of the displayed days as your preferred day for your parcel delivery. Other days are not possible due to delivery processes.', 'dhl-for-woocommerce' ); ?>">?</span>
		</th>
	</tr>
	<tr class="dhl-co-tr">
		<td colspan="2">
		<?php
		wp_kses_post(
			printf(
			// Translators: %s is the surcharge amount including VAT.
				__( 'There is a surcharge of %s incl. VAT for this service.*', 'dhl-for-woocommerce' ),
				wc_price( wc_format_decimal( $shipping_dhl_settings['dhl_preferred_day_cost'] ) )
			)
		);
		?>
		</td>
	</tr>
	<tr class="dhl-co-tr">
		<td colspan="2">
			<?php

			if ( ! empty( $preferred_day_time['preferred_day'] ) ) {

				echo '<ul class="dhl-co-times">';

				$preferred_days = $preferred_day_time['preferred_day'];

				if ( empty( $pr_dhl_preferred_day_selected ) && ! empty( $preferred_days ) ) {
					$pr_dhl_preferred_day_selected = current( $preferred_days );
				}

				foreach ( $preferred_days as $key => $value ) {
					$week_day_num = empty( $key ) ? '-' : gmdate( 'j', strtotime( $key ) );
					$is_selected  = $pr_dhl_preferred_day_selected == $key ? 'checked="checked"' : '';
					?>

					<li>
					<input type="radio" name="pr_dhl_preferred_day" class="pr_dhl_preferred_day" data-index="0" id="pr_dhl_preferred_day_<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php echo esc_attr( $is_selected ); ?> >
					<label for="pr_dhl_preferred_day_<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $week_day_num ) . '<br/>' . esc_attr( $value ); ?></label>
					</li>

					<?php
				}

				echo '</ul>';

			} else {
				?>

				<i>
					<?php esc_html_e( 'Unfortunately, for the selected delivery address the service Delivery Day is not available', 'dhl-for-woocommerce' ); ?>
				</i>

				<?php } ?>
		</ul>
		</td>
	</tr>

	<?php
}

if ( isset( $shipping_dhl_settings['dhl_preferred_location'] ) &&
		isset( $shipping_dhl_settings['dhl_preferred_neighbour'] ) &&
		$shipping_dhl_settings['dhl_preferred_location'] == 'yes' &&
		$shipping_dhl_settings['dhl_preferred_neighbour'] == 'yes' ) {

	if ( empty( $pr_dhl_preferred_location_neighbor_selected ) ) {
		$pr_dhl_preferred_location_neighbor_selected = '0';
	}
	?>

	<tr class="dhl-co-tr">
		<th class="dhl-pt"><?php esc_html_e( 'Drop-off location or neighbor', 'dhl-for-woocommerce' ); ?></th>
		<td class="dhl-pt">
		<ul class="dhl-preferred-location">
			<li>
			<input
				type="radio"
				name="pr_dhl_preferred_location_neighbor"
				data-index="0" id="preferred_location_neighbor_0"
				value="0"
				class=""
				<?php
				if ( $pr_dhl_preferred_location_neighbor_selected == '0' ) {
					echo 'checked="checked"'; }
				?>
				>
			<label for="preferred_location_neighbor_0"><?php esc_html_e( 'None', 'dhl-for-woocommerce' ); ?></label>
			</li>
			<li>
			<input
				type="radio"
				name="pr_dhl_preferred_location_neighbor"
				data-index="0" id="preferred_location_neighbor_1"
				value="preferred_location"
				class=""
				<?php
				if ( ! empty( $pr_dhl_preferred_location_neighbor_selected ) && $pr_dhl_preferred_location_neighbor_selected == 'preferred_location' ) {
					echo 'checked="checked"'; }
				?>
				>
			<label for="preferred_location_neighbor_1"><?php esc_html_e( 'Location', 'dhl-for-woocommerce' ); ?></label>
			</li>
			<li>
			<input
				type="radio"
				name="pr_dhl_preferred_location_neighbor"
				data-index="0" id="preferred_location_neighbor_2"
				value="preferred_neighbor"
				class=""
				<?php
				if ( ! empty( $pr_dhl_preferred_location_neighbor_selected ) && $pr_dhl_preferred_location_neighbor_selected == 'preferred_neighbor' ) {
					echo 'checked="checked"'; }
				?>
				>
			<label for="preferred_location_neighbor_2"><?php esc_html_e( 'Neighbor', 'dhl-for-woocommerce' ); ?></label>
			</li>
		</ul>
		</td>
	</tr>
	<?php
}

if ( isset( $shipping_dhl_settings['dhl_preferred_location'] ) && $shipping_dhl_settings['dhl_preferred_location'] == 'yes' ) {
	?>

	<tr class="dhl-co-tr dhl-radio-toggle dhl-preferred_location">
		<th colspan="2" class="dhl-pt"><?php esc_html_e( 'Drop-off location: Delivery to your preferred drop-off location', 'dhl-for-woocommerce' ); ?>
		<span class="dhl-tooltip" title="<?php esc_html_e( 'Choose a weather-protected and non-visible place on your property, where we can deposit the parcel in your absence.', 'dhl-for-woocommerce' ); ?>">?</span>
		</th>
	</tr>
	<tr class="dhl-co-tr dhl-radio-toggle dhl-preferred_location">
		<td colspan="2">
		<input type="text" name="pr_dhl_preferred_location" data-index="0" id="pr_dhl_preferred_location" class=""
		<?php
		if ( ! empty( $pr_dhl_preferred_location_selected ) ) {
			echo esc_html( 'value="' . $pr_dhl_preferred_location_selected . '"' ); }
		?>
		maxlength="80" placeholder="<?php esc_html_e( 'e.g. Garage, Terrace', 'dhl-for-woocommerce' ); ?>" >
		</td>
	</tr>

	<?php
}

if ( isset( $shipping_dhl_settings['dhl_preferred_neighbour'] ) && $shipping_dhl_settings['dhl_preferred_neighbour'] == 'yes' ) {
	?>

	<tr class="dhl-co-tr dhl-radio-toggle dhl-preferred_neighbor">
		<th colspan="2" class="dhl-pt"><?php esc_html_e( 'Neighbour: Delivery to a neighbour of your choice', 'dhl-for-woocommerce' ); ?> <span class="dhl-tooltip" title="<?php esc_html_e( 'Determine a person in your immediate neighborhood whom we can hand out your parcel in your absence. This person should live in the same building, directly opposite or next door.', 'dhl-for-woocommerce' ); ?>">?</span></th>
	</tr>
	<tr class="dhl-co-tr dhl-radio-toggle dhl-preferred_neighbor">
		<td colspan="2"><input type="text" name="pr_dhl_preferred_neighbour_name" data-index="0" id="pr_dhl_preferred_neighbour_name" class=""
		<?php
		if ( ! empty( $pr_dhl_preferred_neighbour_name_selected ) ) {
			echo esc_html( 'value="' . $pr_dhl_preferred_neighbour_name_selected . '"' ); }
		?>
		maxlength="25" placeholder="<?php esc_html_e( 'First name, last name of neighbour', 'dhl-for-woocommerce' ); ?>"></td>
	</tr>
	<tr class="dhl-co-tr dhl-radio-toggle dhl-preferred_neighbor">
		<td colspan="2"><input type="text" name="pr_dhl_preferred_neighbour_address" data-index="0" id="pr_dhl_preferred_neighbour_address" class=""
		<?php
		if ( ! empty( $pr_dhl_preferred_neighbour_address_selected ) ) {
			echo esc_html( 'value="' . $pr_dhl_preferred_neighbour_address_selected . '"' ); }
		?>
		maxlength="55" placeholder="<?php esc_html_e( 'Street, number, postal code, city', 'dhl-for-woocommerce' ); ?>"></td>
	</tr>

	<?php
}
?>
<tr class="dhl-co-tr dhl-co-tr-last">
	<td colspan="2"></td>
</tr>