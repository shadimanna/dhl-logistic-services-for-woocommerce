<?php

namespace PR\DHL\Tests\REST_API\Parcel_DE;

use PHPUnit\Framework\TestCase;
use PR\DHL\REST_API\Parcel_DE\Client;
use PR\DHL\REST_API\Parcel_DE\Item_Info;
use ReflectionClass;
use ReflectionMethod;

/**
 * Regression tests for the customs "shippingConditions" mapping in
 * Client::get_customs().
 *
 * Per the DHL Parcel DE v2 OpenAPI spec the Incoterm enum is
 * DDU (deprecated), DAP, DDP, DDX, DXV and the field is "exclusively used for
 * the product Europaket (V54EPAK)". These tests lock in:
 *
 *   - DDU (deprecated) maps to DAP
 *   - DAP / DDP / DDX / DXV pass through unchanged
 *   - empty or unset duties default to DAP (covers the bulk / auto-label paths
 *     where order_details['duties'] is never submitted via the metabox)
 *   - the field is emitted only for Europaket (V54EPAK), never for other
 *     cross-border products such as Paket International (V53WPAK)
 */
class Shipping_Conditions_Test extends TestCase {

	/**
	 * Invoke the protected Client::get_customs() with a minimal Item_Info.
	 *
	 * The Client and Item_Info are built without their constructors because
	 * get_customs() only reads plain public properties and the prepare_items()
	 * helper; none of the API_Client wiring (driver/auth/base_url) is needed.
	 *
	 * @param string      $product The DHL product code (e.g. V54EPAK).
	 * @param string|null $duties  The duties value, or null to omit the key.
	 * @return array The customs payload.
	 */
	private function build_customs( $product, $duties ) {
		$order_details = array(
			'invoice_num'    => 'INV-1',
			'currency'       => 'EUR',
			'additional_fee' => 0,
			'shipping_fee'   => 0,
		);

		if ( null !== $duties ) {
			$order_details['duties'] = $duties;
		}

		$item_info           = ( new ReflectionClass( Item_Info::class ) )->newInstanceWithoutConstructor();
		$item_info->args     = array( 'order_details' => $order_details );
		$item_info->shipment = array( 'product' => $product );
		$item_info->items    = array(
			array(
				'itemDescription'  => 'Widget',
				'countryOfOrigin'  => 'DE',
				'hsCode'           => '12345678',
				'packagedQuantity' => 1,
				'itemValue'        => array(
					'currency' => 'EUR',
					'amount'   => 10,
				),
				'itemWeight'       => array(
					'uom'   => 'kg',
					'value' => 1,
				),
			),
		);

		$client = ( new ReflectionClass( Client::class ) )->newInstanceWithoutConstructor();
		$method = new ReflectionMethod( Client::class, 'get_customs' );
		$method->setAccessible( true );

		return $method->invoke( $client, $item_info );
	}

	/**
	 * Europaket emits shippingConditions with the mapped Incoterm.
	 *
	 * @dataProvider europaket_provider
	 *
	 * @param string|null $duties   The duties input.
	 * @param string      $expected The expected shippingConditions value.
	 */
	public function test_europaket_shipping_conditions( $duties, $expected ) {
		$customs = $this->build_customs( 'V54EPAK', $duties );

		$this->assertArrayHasKey( 'shippingConditions', $customs );
		$this->assertSame( $expected, $customs['shippingConditions'] );
	}

	/**
	 * @return array<string, array{0: string|null, 1: string}>
	 */
	public function europaket_provider() {
		return array(
			'DDU (deprecated) maps to DAP' => array( 'DDU', 'DAP' ),
			'DAP passthrough'              => array( 'DAP', 'DAP' ),
			'DDP passthrough'              => array( 'DDP', 'DDP' ),
			'DDX passthrough'              => array( 'DDX', 'DDX' ),
			'DXV passthrough'              => array( 'DXV', 'DXV' ),
			'empty defaults to DAP'        => array( '', 'DAP' ),
			'unset defaults to DAP'        => array( null, 'DAP' ),
		);
	}

	/**
	 * Non-Europaket cross-border products never get shippingConditions.
	 *
	 * @dataProvider non_europaket_provider
	 *
	 * @param string|null $duties The duties input.
	 */
	public function test_non_europaket_omits_shipping_conditions( $duties ) {
		$customs = $this->build_customs( 'V53WPAK', $duties );

		$this->assertArrayNotHasKey( 'shippingConditions', $customs );
	}

	/**
	 * @return array<string, array{0: string|null}>
	 */
	public function non_europaket_provider() {
		return array(
			'with DDP' => array( 'DDP' ),
			'empty'    => array( '' ),
			'unset'    => array( null ),
		);
	}
}
