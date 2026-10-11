<?php

namespace PR\DHL\REST_API\Parcel_DE;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates a commercial-invoice PDF for Paperless Customs, using the bundled TCPDF.
 *
 * @since [*next-version*]
 */
class Commercial_Invoice_PDF {

	/**
	 * Build the commercial-invoice PDF.
	 *
	 * @since [*next-version*]
	 *
	 * @param Item_Info $item_info The shipment info (shipper, consignee, items, currency).
	 * @param array     $settings  Plugin settings incl. 'paperless_signature' and 'paperless_logo' attachment IDs.
	 * @return string Raw PDF bytes.
	 * @throws \Exception If TCPDF is unavailable.
	 */
	public function generate( Item_Info $item_info, array $settings ) {
		$loader = \PR_DHL_Libraryloader::instance();
		$tcpdf  = $loader->get_tcpdf();

		if ( ! $tcpdf ) {
			throw new \Exception( esc_html__( 'TCPDF library unavailable for commercial invoice generation.', 'dhl-for-woocommerce' ) );
		}

		$pdf = new \TCPDF( 'P', 'mm', 'A4', true, 'UTF-8' );
		$pdf->SetCreator( 'DHL for WooCommerce' );
		$pdf->SetMargins( 15, 15, 15 );
		$pdf->setPrintHeader( false );
		$pdf->setPrintFooter( false );
		$pdf->AddPage();

		// Optional logo.
		$logo_path = $this->attachment_path( $settings['paperless_logo'] ?? '' );
		if ( $logo_path ) {
			$pdf->Image( $logo_path, 15, 12, 40 );
			$pdf->Ln( 20 );
		}

		$pdf->SetFont( 'helvetica', 'B', 14 );
		$pdf->Cell( 0, 8, 'Commercial Invoice', 0, 1, 'C' );
		$pdf->Ln( 2 );

		$pdf->SetFont( 'helvetica', '', 10 );
		$pdf->MultiCell( 90, 5, $this->format_party( $item_info->shipper ), 0, 'L', false, 0 );
		$pdf->MultiCell( 90, 5, $this->format_party( $item_info->contactAddress ), 0, 'L', true );
		$pdf->Ln( 2 );

		$invoice_no = $item_info->args['order_details']['invoice_num'] ?? '';
		$pdf->Cell( 0, 5, 'Invoice No: ' . $invoice_no . '   Date: ' . gmdate( 'Y-m-d' ), 0, 1 );
		$pdf->Ln( 2 );

		// Line items table header.
		$pdf->SetFont( 'helvetica', 'B', 9 );
		$pdf->Cell( 70, 6, 'Description', 1 );
		$pdf->Cell( 22, 6, 'HS code', 1 );
		$pdf->Cell( 20, 6, 'Origin', 1 );
		$pdf->Cell( 15, 6, 'Qty', 1, 0, 'R' );
		$pdf->Cell( 28, 6, 'Unit value', 1, 0, 'R' );
		$pdf->Cell( 25, 6, 'Total', 1, 1, 'R' );

		$pdf->SetFont( 'helvetica', '', 9 );
		$currency = $item_info->args['order_details']['currency'] ?? '';
		$grand    = 0.0;
		foreach ( $item_info->items as $item ) {
			$qty    = (float) ( $item['packagedQuantity'] ?? 0 );
			$unit   = (float) ( $item['itemValue']['amount'] ?? 0 );
			$total  = $qty * $unit;
			$grand += $total;
			$desc   = (string) ( $item['itemDescription'] ?? '' );
			$desc   = mb_strimwidth( $desc, 0, 60, '…', 'UTF-8' );
			$pdf->Cell( 70, 6, $desc, 1 );
			$pdf->Cell( 22, 6, (string) ( $item['hsCode'] ?? '' ), 1 );
			$pdf->Cell( 20, 6, (string) ( $item['countryOfOrigin'] ?? '' ), 1 );
			$pdf->Cell( 15, 6, (string) $qty, 1, 0, 'R' );
			$pdf->Cell( 28, 6, number_format( $unit, 2 ), 1, 0, 'R' );
			$pdf->Cell( 25, 6, number_format( $total, 2 ), 1, 1, 'R' );
		}

		$pdf->SetFont( 'helvetica', 'B', 9 );
		$pdf->Cell( 147, 6, 'Total (' . $currency . ')', 1, 0, 'R' );
		$pdf->Cell( 25, 6, number_format( $grand, 2 ), 1, 1, 'R' );
		$pdf->Ln( 8 );

		// Reason for export + declaration.
		$pdf->SetFont( 'helvetica', '', 9 );
		$pdf->MultiCell( 0, 5, 'Reason for export: ' . apply_filters( 'pr_shipping_dhl_paket_label_shipment_export_type', 'COMMERCIAL_GOODS' ), 0, 'L' );
		$pdf->Ln( 2 );
		$pdf->MultiCell( 0, 5, 'I declare the above information to be true and correct.', 0, 'L' );
		$pdf->Ln( 4 );

		// Signature.
		$sig_path = $this->attachment_path( $settings['paperless_signature'] ?? '' );
		if ( $sig_path ) {
			$pdf->Image( $sig_path, 15, $pdf->GetY(), 40 );
			$pdf->Ln( 18 );
		}
		$pdf->Cell( 0, 5, 'Place/Date: ' . ( $item_info->shipper['city'] ?? '' ) . ', ' . gmdate( 'Y-m-d' ), 0, 1 );
		$pdf->Cell( 0, 5, 'Signature: ' . ( $item_info->shipper['name1'] ?? '' ), 0, 1 );

		return $pdf->Output( 'invoice.pdf', 'S' );
	}

	/**
	 * Format a party address block for the invoice.
	 *
	 * @since [*next-version*]
	 *
	 * @param array $party Party data (name, street, house, postal code, city, country).
	 * @return string Newline-separated address block with empty lines removed.
	 */
	protected function format_party( array $party ) {
		$lines = array(
			$party['name1'] ?? ( $party['name'] ?? '' ),
			trim( ( $party['addressStreet'] ?? '' ) . ' ' . ( $party['addressHouse'] ?? '' ) ),
			trim( ( $party['postalCode'] ?? '' ) . ' ' . ( $party['city'] ?? '' ) ),
			$party['country'] ?? '',
		);

		return implode( "\n", array_filter( $lines ) );
	}

	/**
	 * Resolve an attachment ID to a readable local PNG/JPEG file path.
	 *
	 * @since [*next-version*]
	 *
	 * @param int|string $attachment_id Media library attachment ID.
	 * @return string Local file path, or empty string if not a usable PNG/JPEG image.
	 */
	protected function attachment_path( $attachment_id ) {
		if ( empty( $attachment_id ) ) {
			return '';
		}

		$attachment_id = (int) $attachment_id;
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return '';
		}

		if ( ! in_array( get_post_mime_type( $attachment_id ), array( 'image/png', 'image/jpeg' ), true ) ) {
			return '';
		}

		$path = get_attached_file( $attachment_id );

		return ( $path && file_exists( $path ) ) ? $path : '';
	}
}
