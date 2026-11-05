<?php
/**
 * Unknown shipment exception for the Parcel DE shipping API.
 *
 * @package PR\DHL\REST_API\Parcel_DE
 */

namespace PR\DHL\REST_API\Parcel_DE;

use Exception;

/**
 * Thrown when DHL does not know the shipment to cancel, because it was already cancelled or never existed.
 *
 * @since [*next-version*]
 */
class Unknown_Shipment_Exception extends Exception {
}
