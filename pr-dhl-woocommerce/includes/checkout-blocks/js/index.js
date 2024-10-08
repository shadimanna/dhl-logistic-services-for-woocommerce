/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

// Import block definitions
import './dhl-blocks';

const render = () => {};

registerPlugin('pr-dhl', {
	render,
	scope: 'woocommerce-checkout',
});
