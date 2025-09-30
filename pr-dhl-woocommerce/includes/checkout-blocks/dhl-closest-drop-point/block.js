import { useEffect, useState, useCallback } from '@wordpress/element';
import { Notice } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { CART_STORE_KEY } from '@woocommerce/block-data';
import { debounce } from 'lodash';
import { __ } from '@wordpress/i18n';

export const Block = ({ checkoutExtensionData }) => {
    const { setExtensionData } = checkoutExtensionData;

    // Access the localized data from prDhlGlobals
    const imgUrl      = prDhlGlobals?.pluginUrl + "/assets/img/dhl-official.png";
    const dhlSettings = prDhlGlobals.dhlSettings;

    const [error, setError] = useState( null );
    const [closestDP, setClosestDP] = useState('no');
    const [displayClosest, setDisplayClosest] = useState( true );

    const closestAvailable = dhlSettings?.closest_drop_point;
    const validCountries   = prDhlGlobals?.valid_countries || [];


    // Retrieve customer data
    const customerData    = useSelect( ( select ) => select( CART_STORE_KEY ).getCustomerData(), [] );
    const shippingAddress = customerData ? customerData.shippingAddress : null;

    // Debounce for reducing the number of updates to the extension data
    const debouncedSetExtensionData = useCallback( debounce( ( namespace, key, value ) => {
        setExtensionData( namespace, key, value );
    }, 500 ), [setExtensionData] );

    useEffect( () => {
        if (
            ! shippingAddress ||
            ! closestAvailable ||
            ! validCountries.includes( shippingAddress.country )
        ) {
            setDisplayClosest( false );
            return;
        } else {
            setDisplayClosest( true );
            return;
        }

    }, [ shippingAddress, closestAvailable ] );

    // useEffect for closestDP
    useEffect( () => {
        setExtensionData( 'pr-dhl', 'closest_drop_point', closestDP );
        debouncedSetExtensionData( 'pr-dhl', 'closest_drop_point', closestDP );
    }, [ closestDP ] );

    if ( ! displayClosest ) {
        return null; 
    }

    if ( error ) {
        return (
            <Notice status="error" isDismissible={false}>
                {__( error, 'dhl-for-woocommerce' )}
            </Notice>
        );
    }

    // Render DHL logo dynamically from the localized data
    return (
        <table className="dhl-co-table">
            <tbody>
                {/* DHL logo */}
                <tr className="dhl-co-tr dhl-co-tr-first">
                    <td colSpan={2}>
                        <img src={imgUrl} alt="DHL logo" className="dhl-co-logo" />
                    </td>
                </tr>

                {/* Title and description */}
                <tr className="dhl-co-tr">
                    <th colSpan={2}>
                        { __( 'Closest drop-off point', 'dhl-for-woocommerce' ) }
                        <hr />
                    </th>
                </tr>

                <tr className="dhl-co-tr">
                    <td colSpan={2}>
                        { __( 'Preferred delivery to a parcel shop/parcel locker close to the specified home address', 'dhl-for-woocommerce' ) }
                    </td>
                </tr>

                <tr className="dhl-co-tr">
                    <th className="dhl-cdp">
                        { __( 'Delivery option', 'dhl-for-woocommerce' ) }
                    </th>
                    <td className="dhl-cdp">
                        <ul className="dhl-preferred-location">
                            <li>
                                <input
                                    defaultChecked={true}
                                    type="radio"
                                    name="pr_dhl_cdp_delivery"
                                    data-index={0} id="dhl_home_deliver_option"
                                    value="no"
                                    className=""
                                    onChange={(e) => setClosestDP(e.target.value)} />
                                <label htmlFor="dhl_home_deliver_option">{ __( 'Home delivery', 'dhl-for-woocommerce' ) }</label>
                            </li>
                            <li>
                                <input
                                    type="radio"
                                    name="pr_dhl_cdp_delivery"
                                    data-index={0} id="dhl_cdp_option"
                                    value="yes"
                                    className=""
                                    onChange={(e) => setClosestDP(e.target.value)} />
                                <label htmlFor="dhl_cdp_option">{ __( 'Closest Drop Point', 'dhl-for-woocommerce' ) }</label>
                            </li>
                        </ul>
                    </td>
                </tr>
                <tr className="dhl-co-tr dhl-co-tr-last">
                    <td colSpan={2}></td>
                </tr>
            </tbody>
        </table>
    );
};


