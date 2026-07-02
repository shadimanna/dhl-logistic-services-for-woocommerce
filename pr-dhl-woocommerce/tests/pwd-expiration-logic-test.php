<?php
/**
 * Regression tests for PR_DHL_WC::get_pwd_expiration_state().
 *
 * The DHL password-expiration warning is timezone- and boundary-sensitive, so this
 * covers the exact decision boundaries that previously produced false warnings:
 *   - an unparseable date (strtotime === false) must NOT raise a warning;
 *   - UTC "now" boundaries at the 30-day and 7-day marks;
 *   - correct scheduling of the month/week cron events.
 *
 * Dependency-free: no PHPUnit or WordPress test suite required. The plugin's PR_DHL()
 * bootstrap is pre-defined so requiring the main file only DEFINES the class (it is
 * never instantiated), letting us call the pure static helper directly.
 *
 * Run:  php tests/pwd-expiration-logic-test.php
 */

define( 'ABSPATH', __DIR__ . '/' );

if ( ! function_exists( 'PR_DHL' ) ) {
	// Skip the plugin bootstrap block (which would instantiate the class on include).
	function PR_DHL() {
		return null;
	}
}

require dirname( __DIR__ ) . '/pr-dhl-woocommerce.php';

$now  = 1000000000; // Fixed reference clock (UTC).
$day  = 24 * 60 * 60;
$fail = 0;
$pass = 0;

/**
 * @param string $name     Scenario name.
 * @param array  $expected Expected state array.
 * @param array  $actual   Actual state array from the helper.
 */
function check( $name, $expected, $actual ) {
	global $fail, $pass;
	if ( $expected === $actual ) {
		$pass++;
		printf( "  PASS  %s\n", $name );
		return;
	}
	$fail++;
	printf( "  FAIL  %s\n        expected %s\n        actual   %s\n", $name, json_encode( $expected ), json_encode( $actual ) );
}

$cases = array(
	array(
		'name'     => 'Unparseable date (strtotime === false) -> no warning, no schedule',
		'valid'    => false,
		'expected' => array( 'flag' => '', 'schedule_month' => false, 'schedule_week' => false ),
	),
	array(
		'name'     => 'Valid +90 days -> no flag, both events scheduled',
		'valid'    => $now + 90 * $day,
		'expected' => array( 'flag' => '', 'schedule_month' => $now + 60 * $day, 'schedule_week' => $now + 83 * $day ),
	),
	array(
		'name'     => 'Valid +20 days (7-30) -> 30days flag, week event scheduled',
		'valid'    => $now + 20 * $day,
		'expected' => array( 'flag' => '30days', 'schedule_month' => false, 'schedule_week' => $now + 13 * $day ),
	),
	array(
		'name'     => 'Valid +3 days (<7) -> 7days flag, no schedule',
		'valid'    => $now + 3 * $day,
		'expected' => array( 'flag' => '7days', 'schedule_month' => false, 'schedule_week' => false ),
	),
	array(
		'name'     => 'Already expired -> 7days flag, no schedule',
		'valid'    => $now - 5 * $day,
		'expected' => array( 'flag' => '7days', 'schedule_month' => false, 'schedule_week' => false ),
	),
	array(
		'name'     => 'Boundary: 30-day cutoff 1s in the future -> schedule both',
		'valid'    => $now + 30 * $day + 1,
		'expected' => array( 'flag' => '', 'schedule_month' => $now + 1, 'schedule_week' => $now + 23 * $day + 1 ),
	),
	array(
		'name'     => 'Boundary: 30-day cutoff 1s in the past -> 30days flag',
		'valid'    => $now + 30 * $day - 1,
		'expected' => array( 'flag' => '30days', 'schedule_month' => false, 'schedule_week' => $now + 23 * $day - 1 ),
	),
	array(
		'name'     => 'Boundary: 7-day cutoff 1s in the past -> 7days flag',
		'valid'    => $now + 7 * $day - 1,
		'expected' => array( 'flag' => '7days', 'schedule_month' => false, 'schedule_week' => false ),
	),
);

echo "PR_DHL_WC::get_pwd_expiration_state()\n";
foreach ( $cases as $c ) {
	check( $c['name'], $c['expected'], PR_DHL_WC::get_pwd_expiration_state( $c['valid'], $now ) );
}

printf( "\n%d passed, %d failed\n", $pass, $fail );
exit( $fail > 0 ? 1 : 0 );
