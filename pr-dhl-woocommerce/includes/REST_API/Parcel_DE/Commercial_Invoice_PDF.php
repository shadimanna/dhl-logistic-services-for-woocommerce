<?php

namespace PR\DHL\REST_API\Parcel_DE;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates a commercial-invoice PDF for Paperless Customs, using the bundled TCPDF.
 *
 * The invoice wording ("Commercial Invoice", declaration, column headings) is intentionally
 * English, as customs authorities expect English on this document.
 *
 * @since [*next-version*]
 */
class Commercial_Invoice_PDF {

	/**
	 * Unicode-capable font bundled with TCPDF, so non-Latin names do not render as "?".
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	const FONT = 'dejavusans';

	/**
	 * Build the commercial-invoice PDF.
	 *
	 * @since [*next-version*]
	 *
	 * @param Item_Info $item_info The shipment info (shipper, consignee, items, currency).
	 * @param array     $settings  Plugin settings incl. 'paperless_signature' and 'paperless_logo' attachment IDs.
	 * @return string Raw PDF bytes.
	 * @throws \Exception If TCPDF is unavailable, there are no items, or a configured signature cannot be loaded.
	 */
	public function generate( Item_Info $item_info, array $settings ) {
		$loader = \PR_DHL_Libraryloader::instance();
		$tcpdf  = $loader->get_tcpdf();

		if ( ! $tcpdf ) {
			throw new \Exception( esc_html__( 'TCPDF library unavailable for commercial invoice generation.', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $item_info->items ) ) {
			throw new \Exception( esc_html__( 'No customs items to include on the commercial invoice.', 'dhl-for-woocommerce' ) );
		}

		// Fail closed: never ship an unsigned invoice when a signature is configured but unusable.
		$sig_path = '';
		if ( ! empty( $settings['paperless_signature'] ) ) {
			$sig_path = $this->attachment_path( $settings['paperless_signature'] );
			if ( '' === $sig_path ) {
				throw new \Exception( esc_html__( 'Paperless Customs signature image could not be loaded.', 'dhl-for-woocommerce' ) );
			}
		}

		$pdf = new \TCPDF( 'P', 'mm', 'A4', true, 'UTF-8' );
		$pdf->SetCreator( 'DHL for WooCommerce' );
		$pdf->SetMargins( 15, 15, 15 );
		$pdf->setPrintHeader( false );
		$pdf->setPrintFooter( false );
		$pdf->AddPage();

		// Optional logo (skipped silently if unresolved).
		$logo_path = $this->attachment_path( $settings['paperless_logo'] ?? '' );
		if ( $logo_path ) {
			$pdf->Image( $logo_path, 15, 12, 40, 16, '', '', '', false, 300, '', false, false, 0, 'LT' );
			$pdf->SetY( 30 );
		}

		// English is intentional: customs expects an English commercial invoice.
		$pdf->SetFont( self::FONT, 'B', 14 );
		$pdf->Cell( 0, 8, 'Commercial Invoice', 0, 1, 'C' );
		$pdf->Ln( 2 );

		$pdf->SetFont( self::FONT, '', 10 );
		$party_y       = $pdf->GetY();
		$shipper_text  = $this->format_party( $item_info->shipper );
		$consignee_txt = $this->format_party( $item_info->contactAddress );

		// Advance past the taller of the two side-by-side address blocks so nothing overlaps.
		$party_lines = max( $pdf->getNumLines( $shipper_text, 90 ), $pdf->getNumLines( $consignee_txt, 90 ) );

		$pdf->MultiCell( 90, 5, $shipper_text, 0, 'L', false, 0 );
		$pdf->MultiCell( 90, 5, $consignee_txt, 0, 'L', false, 1 );
		$pdf->SetY( $party_y + ( 5 * $party_lines ) );
		$pdf->Ln( 2 );

		$invoice_no = $item_info->args['order_details']['invoice_num'] ?? '';
		$pdf->Cell( 0, 5, 'Invoice No: ' . $invoice_no . '   Date: ' . wp_date( 'Y-m-d' ), 0, 1 );
		$pdf->Ln( 2 );

		// Line items table header.
		$pdf->SetFont( self::FONT, 'B', 9 );
		$pdf->Cell( 70, 6, 'Description', 1 );
		$pdf->Cell( 22, 6, 'HS code', 1 );
		$pdf->Cell( 20, 6, 'Origin', 1 );
		$pdf->Cell( 15, 6, 'Qty', 1, 0, 'R' );
		$pdf->Cell( 28, 6, 'Unit value', 1, 0, 'R' );
		$pdf->Cell( 25, 6, 'Total', 1, 1, 'R' );

		$pdf->SetFont( self::FONT, '', 9 );
		$currency = $item_info->args['order_details']['currency'] ?? '';
		$grand    = 0.0;
		foreach ( $item_info->items as $item ) {
			$qty  = (float) ( $item['packagedQuantity'] ?? 0 );
			$unit = (float) ( $item['itemValue']['amount'] ?? 0 );

			// Prefer the order line's actual total (avoids rounding drift) when provided.
			if ( isset( $item['lineTotal']['amount'] ) && is_numeric( $item['lineTotal']['amount'] ) ) {
				$total = (float) $item['lineTotal']['amount'];
			} else {
				$total = $qty * $unit;
			}
			$grand += $total;

			$desc = wp_html_excerpt( (string) ( $item['itemDescription'] ?? '' ), 60, '…' );
			$pdf->Cell( 70, 6, $desc, 1 );
			$pdf->Cell( 22, 6, (string) ( $item['hsCode'] ?? '' ), 1 );
			$pdf->Cell( 20, 6, (string) ( $item['countryOfOrigin'] ?? '' ), 1 );
			$pdf->Cell( 15, 6, (string) $qty, 1, 0, 'R' );
			$pdf->Cell( 28, 6, number_format( $unit, 2 ), 1, 0, 'R' );
			$pdf->Cell( 25, 6, number_format( $total, 2 ), 1, 1, 'R' );
		}

		// Grand total of goods only; shipping is passed to DHL separately (postalCharges).
		$pdf->SetFont( self::FONT, 'B', 9 );
		$pdf->Cell( 155, 6, 'Total (' . $currency . ')', 1, 0, 'R' );
		$pdf->Cell( 25, 6, number_format( $grand, 2 ), 1, 1, 'R' );
		$pdf->Ln( 8 );

		// Reason for export + declaration (English intentionally, for customs).
		$pdf->SetFont( self::FONT, '', 9 );
		$pdf->MultiCell( 0, 5, 'Reason for export: ' . apply_filters( 'pr_shipping_dhl_paket_label_shipment_export_type', 'COMMERCIAL_GOODS' ), 0, 'L' );
		$pdf->Ln( 2 );
		$pdf->MultiCell( 0, 5, 'I declare the above information to be true and correct.', 0, 'L' );
		$pdf->Ln( 4 );

		// Signature (already validated above).
		if ( $sig_path ) {
			$pdf->Image( $sig_path, 15, $pdf->GetY(), 40, 15, '', '', '', false, 300, '', false, false, 0, 'LT' );
			$pdf->Ln( 18 );
		}
		$pdf->Cell( 0, 5, 'Place/Date: ' . ( $item_info->shipper['city'] ?? '' ) . ', ' . wp_date( 'Y-m-d' ), 0, 1 );
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
	 * Resolve an attachment ID to a readable local PNG/JPEG file path, preferring the medium size.
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

		if ( 'attachment' !== get_post_type( $attachment_id ) ) {
			return '';
		}

		if ( ! in_array( get_post_mime_type( $attachment_id ), array( 'image/png', 'image/jpeg' ), true ) ) {
			return '';
		}

		$path = get_attached_file( $attachment_id );
		if ( ! $path || ! file_exists( $path ) ) {
			return '';
		}

		// Use the generated intermediate size: the image prints at ~40mm, so the original is wasteful.
		$medium = wp_get_attachment_image_src( $attachment_id, 'medium' );
		if ( is_array( $medium ) && ! empty( $medium[0] ) && ! empty( $medium[3] ) ) {
			$medium_path = trailingslashit( dirname( $path ) ) . wp_basename( $medium[0] );
			if ( file_exists( $medium_path ) ) {
				return $medium_path;
			}
		}

		return $path;
	}
}
