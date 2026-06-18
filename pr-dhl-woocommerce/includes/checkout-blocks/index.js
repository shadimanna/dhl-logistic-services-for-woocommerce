/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

// Import block definitions
import './dhl-preferred-services';
import './dhl-parcel-finder';
import './dhl-closest-drop-point';

const render = () => {};

registerPlugin('pr-dhl', {
	render,
	scope: 'woocommerce-checkout',
});
