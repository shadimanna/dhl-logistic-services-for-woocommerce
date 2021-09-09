<?php defined( 'ABSPATH' ) or exit; ?>
<!DOCTYPE HTML>
<html <?php language_attributes(); ?>>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>">
		<title><?php esc_html_e( 'DHL Handover', 'dhl-for-woocommerce' ) ?></title>
	</head>

	<body>
		<?php
			if ( isset( $action ) && 'print' === $action ) {
				echo '<a class="button" href="#" onclick="window.print()">' . esc_html_e( 'Print', 'dhl-for-woocommerce' ) . '</a>';

			}
