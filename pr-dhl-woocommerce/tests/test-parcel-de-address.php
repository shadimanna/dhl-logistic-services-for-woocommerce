<?php
/**
 * Standalone tests for Parcel DE consignee address handling.
 *
 * Exercises Item_Info::set_address_2() and Item_Info::split_address_street() in
 * isolation, without a WordPress bootstrap: the class is instantiated without its
 * constructor and the protected methods are invoked via reflection.
 *
 * Run: php tests/test-parcel-de-address.php
 */

namespace {
	if ( ! function_exists( 'esc_html__' ) ) {
		function esc_html__( $text, $domain = null ) {
			return $text;
		}
	}
}

namespace {
	use PR\DHL\REST_API\Parcel_DE\Item_Info;

	require __DIR__ . '/../includes/REST_API/Parcel_DE/Item_Info.php';

	/**
	 * Run set_address_2() then split_address_street() over a shipping address.
	 *
	 * @param array $shipping The shipping_address args.
	 * @return array The resulting shipping_address after both methods run.
	 */
	function pr_dhl_run_address( array $shipping ) {
		$ref  = new \ReflectionClass( Item_Info::class );
		$item = $ref->newInstanceWithoutConstructor();

		// Both methods early-return for Packstation / Parcelshop / Post Office addresses.
		foreach ( array( 'pos_ps', 'pos_rs', 'pos_po' ) as $flag ) {
			$prop = $ref->getProperty( $flag );
			$prop->setAccessible( true );
			$prop->setValue( $item, false );
		}

		$item->args = array( 'shipping_address' => $shipping );

		foreach ( array( 'set_address_2', 'split_address_street' ) as $name ) {
			$method = $ref->getMethod( $name );
			$method->setAccessible( true );
			$method->invoke( $item );
		}

		return $item->args['shipping_address'];
	}

	$failures = 0;

	/**
	 * Assert equality and report the outcome.
	 *
	 * @param string $label Human readable test name.
	 * @param mixed  $got   Actual value.
	 * @param mixed  $want  Expected value.
	 */
	function pr_dhl_assert( $label, $got, $want ) {
		global $failures;

		if ( $got === $want ) {
			printf( "[PASS] %s\n", $label );
			return;
		}

		++$failures;
		printf( "[FAIL] %s\n       got=%s\n       want=%s\n", $label, var_export( $got, true ), var_export( $want, true ) );
	}

	$long = 'An der langen Muhlenstrasse neben dem alten Wasserturm 12';

	// Long address_1, empty address_2: the house number is extracted into addressHouse.
	$r = pr_dhl_run_address( array( 'address_1' => $long, 'address_2' => '', 'country' => 'DE' ) );
	pr_dhl_assert( 'Long street, empty address_2 -> addressHouse extracted', $r['address_2'], '12' );

	// Long address_1 AND long address_2: address_2 is too long to be a house number, so it
	// moves to the additional field and the real house number is still recovered from
	// address_1 (regression fixed - previously addressHouse came back empty -> DHL 400).
	$r = pr_dhl_run_address( array( 'address_1' => $long, 'address_2' => $long, 'country' => 'DE' ) );
	pr_dhl_assert( 'Long street and long address_2 -> addressHouse recovered', $r['address_2'], '12' );
	pr_dhl_assert( 'Long street stays within the 50 char addressStreet limit', strlen( $r['address_1'] ) <= 50, true );
	pr_dhl_assert( 'Additional field stays within the 60 char limit', strlen( $r['address_additional'] ) <= 60, true );

	// A short address_2 is a genuine house number and must be left untouched.
	$r = pr_dhl_run_address( array( 'address_1' => 'Charles-de-Gaulle-Str.', 'address_2' => '20', 'country' => 'DE' ) );
	pr_dhl_assert( 'Short address_2 kept as the house number', $r['address_2'], '20' );

	// House number at the front of address_1.
	$r = pr_dhl_run_address( array( 'address_1' => '20 Charles-de-Gaulle-Str', 'address_2' => '', 'country' => 'DE' ) );
	pr_dhl_assert( 'Leading house number extracted', $r['address_2'], '20' );

	echo $failures ? "\n{$failures} failure(s)\n" : "\nAll tests passed\n";
	exit( $failures ? 1 : 0 );
}
