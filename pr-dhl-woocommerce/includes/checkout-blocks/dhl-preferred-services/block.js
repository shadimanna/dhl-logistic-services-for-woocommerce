import { useEffect, useState, useCallback } from '@wordpress/element';
import { TextControl, RadioControl, Spinner, Notice } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { CART_STORE_KEY } from '@woocommerce/block-data';
import { __ } from '@wordpress/i18n';
import { debounce } from 'lodash';
import axios from 'axios';

export const Block = ({ checkoutExtensionData }) => {
    const { setExtensionData } = checkoutExtensionData;

    // Access the localized data from prDhlGlobals
    const imgUrl = prDhlGlobals.pluginUrl + "/assets/img/dhl-official.png";
    const dhlSettings = prDhlGlobals.dhlSettings;
    const [displayPreferred, setDisplayPreferred] = useState(true);

    const { updateCustomerData } = useDispatch(CART_STORE_KEY);

    // Debounce for reducing the number of updates to the extension data
    const debouncedSetExtensionData = useCallback(debounce((namespace, key, value) => {
        setExtensionData(namespace, key, value);
    }, 500), [setExtensionData]);

    const debounceTimer = useState(null);

    // Determine availability of location and neighbor options
    const locationAvailable = dhlSettings?.dhl_preferred_location === 'yes';
    const neighborAvailable = dhlSettings?.dhl_preferred_neighbour === 'yes';
    const showRadioControl = locationAvailable && neighborAvailable;

    // Initialize preferredLocationNeighbor
    const initialPreferredLocationNeighbor = showRadioControl ? 'none' : locationAvailable ? 'location' : neighborAvailable ? 'neighbor' : 'none';

    // State hooks for the block fields
    const [preferredDay, setPreferredDay] = useState('');
    const [preferredDays, setPreferredDays] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [preferredLocationNeighbor, setPreferredLocationNeighbor] = useState(initialPreferredLocationNeighbor);
    const [preferredLocation, setPreferredLocation] = useState('');
    const [preferredNeighborName, setPreferredNeighborName] = useState('');
    const [preferredNeighborAddress, setPreferredNeighborAddress] = useState('');

    // Retrieve customer data
    const customerData = useSelect((select) => select(CART_STORE_KEY).getCustomerData(), []);
    const shippingAddress = customerData ? customerData.shippingAddress : null;

    // Retrieve selected shipping methods and payment method
    const cartData = useSelect((select) => select(CART_STORE_KEY).getCartData(), []);
    const selectedShippingMethods = cartData ? cartData.selectedShippingMethods : [];
    const selectedPaymentMethod = cartData ? cartData.selectedPaymentMethod : '';

    // State to keep track of whether the preferred day fee has been applied
    const [preferredDayFeeApplied, setPreferredDayFeeApplied] = useState(false);

    useEffect(() => {

        // Clear previous timer on re-render.
        if (debounceTimer.current) {
            clearTimeout(debounceTimer.current);
        }
        // Check if shippingAddress is valid
        if (
            !shippingAddress ||
            shippingAddress.country !== 'DE' ||
            !shippingAddress.city ||
            !shippingAddress.postcode
        ) {
            setDisplayPreferred(false);
            setLoading(false);
            return;
        }

        // Debounce the request by 1.5 seconds.
        debounceTimer.current = setTimeout(() => {
            if (shippingAddress) {
                const data = {
                    shipping_country: shippingAddress.country || '',
                    shipping_postcode: shippingAddress.postcode || '',
                    shipping_address_1: shippingAddress.address_1 || '',
                    shipping_address_2: shippingAddress.address_2 || '',
                    shipping_city: shippingAddress.city || '',
                    shipping_state: shippingAddress.state || '',
                    shipping_email: shippingAddress.email || '',
                    shipping_phone: shippingAddress.phone || '',
                    shipping_company: shippingAddress.company || '',
                    shipping_methods: selectedShippingMethods,
                    payment_method: selectedPaymentMethod,
                };

                const formData = new URLSearchParams();
                formData.append('action', 'pr_dhl_set_checkout_post_data');
                formData.append('nonce', prDhlGlobals.nonce);

                // Append each data field
                Object.keys(data).forEach((key) => {
                    if (Array.isArray(data[key])) {
                        data[key].forEach((item, index) => {
                            formData.append(`data[${key}][${index}]`, item);
                        });
                    } else {
                        formData.append(`data[${key}]`, data[key]);
                    }
                });

                // Send the data via AJAX
                axios.post(prDhlGlobals.ajax_url, formData, {
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                })
                    .then(response => {
                        if (response.data.success) {
                            // Fetch the preferredDays
                            fetchPreferredDays();
                        } else {
                            setDisplayPreferred(false);
                            setLoading(false);
                        }
                    })
                    .catch(error => {
                        setDisplayPreferred(false);
                        setLoading(false);
                    });
            }

        }, 750);
        // clear timer if user keeps typing.
        return () => clearTimeout(debounceTimer.current);

    }, [shippingAddress, selectedShippingMethods, selectedPaymentMethod]);

    const fetchPreferredDays = () => {
        setLoading(true);
        setError('');

        const formData = new URLSearchParams();
        formData.append('action', 'pr_dhl_get_preferred_days');
        formData.append('nonce', prDhlGlobals.nonce);

        axios.post(prDhlGlobals.ajax_url, formData, {
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
        })
            .then(response => {

                if (response.data.success) {

                    setPreferredDays(response.data.data.preferredDays);
                    setDisplayPreferred(true);
                    setLoading(false);
                } else {
                    setDisplayPreferred(false);
                    setLoading(false);
                }
            })
            .catch(error => {
                setDisplayPreferred(false);
                setError('Error fetching preferred days.');
                setLoading(false);
            });
    };

    // useEffect for preferredDay
    useEffect(() => {
        setExtensionData('pr-dhl', 'preferredDay', preferredDay);
        debouncedSetExtensionData('pr-dhl', 'preferredDay', preferredDay);

        // Update customer data to refresh the checkout
        updateCustomerData();

        // Handle adding/removing the preferred day fee
        const preferredDayCost = parseFloat(dhlSettings?.dhl_preferred_day_cost || 0);

        // Function to update the cart fee via Store API
        const updateCartFee = async (feeAmount, feeLabel) => {
            try {
                const { extensionCartUpdate } = window.wc.blocksCheckout || {};

                if (typeof extensionCartUpdate === 'function') {
                    await extensionCartUpdate({
                        namespace: 'pr-dhl',
                        data: {
                            action: 'update_preferred_day_fee',
                            price: feeAmount,
                            label: feeLabel,
                        },
                    });
                }
            } catch (error) {
                console.error('Error updating cart fee:', error);
            }
        };

        if (preferredDay && preferredDay !== '0' && preferredDayCost > 0) {
            if (!preferredDayFeeApplied) {
                // Add the fee
                updateCartFee(preferredDayCost, __('DHL Delivery Day', 'dhl-for-woocommerce'));
                setPreferredDayFeeApplied(true);
            }
        } else {
            if (preferredDayFeeApplied) {
                // Remove the fee
                updateCartFee(0, '');
                setPreferredDayFeeApplied(false);
            }
        }
    }, [preferredDay]);

    // useEffect for preferredLocationNeighbor
    useEffect(() => {
        setExtensionData('pr-dhl', 'preferredLocationNeighbor', preferredLocationNeighbor);
        debouncedSetExtensionData('pr-dhl', 'preferredLocationNeighbor', preferredLocationNeighbor);
    }, [preferredLocationNeighbor]);

    // useEffect for preferredLocation
    useEffect(() => {
        setExtensionData('pr-dhl', 'preferredLocation', preferredLocation);
        debouncedSetExtensionData('pr-dhl', 'preferredLocation', preferredLocation);
    }, [preferredLocation]);

    // useEffect for preferredNeighborName
    useEffect(() => {
        setExtensionData('pr-dhl', 'preferredNeighborName', preferredNeighborName);
        debouncedSetExtensionData('pr-dhl', 'preferredNeighborName', preferredNeighborName);
    }, [preferredNeighborName]);

    // useEffect for preferredNeighborAddress
    useEffect(() => {
        setExtensionData('pr-dhl', 'preferredNeighborAddress', preferredNeighborAddress);
        debouncedSetExtensionData('pr-dhl', 'preferredNeighborAddress', preferredNeighborAddress);
    }, [preferredNeighborAddress]);

    // Handle visibility of drop-off location and neighbor fields based on settings and selection
    const showDropOffLocation = (showRadioControl && preferredLocationNeighbor === 'location') || (!showRadioControl && locationAvailable);
    const showNeighborFields = (showRadioControl && preferredLocationNeighbor === 'neighbor') || (!showRadioControl && neighborAvailable);

    // Update the mapping of preferredDayOptions
    let preferredDayOptions = [];
    if (preferredDays && Object.keys(preferredDays).length > 0) {
        preferredDayOptions = Object.entries(preferredDays).map(([key, dayName]) => {
            let weekDayNum = '';
            if (key === '0' || key === 'none') {
                weekDayNum = '-';
            } else {
                const date = new Date(key);
                if (isNaN(date.getTime())) {
                    weekDayNum = '-';
                } else {
                    weekDayNum = date.getDate().toString();
                }
            }
            return {
                weekDayNum,
                dayName,
                key,
            };
        });
    }

    if (loading) {
        return <Spinner />;
    }

    if (!displayPreferred) {
        return null; // Or display a message indicating that preferred services are not available
    }

    if (error) {
        return (
            <Notice status="error" isDismissible={false}>
                {__(error, 'dhl-for-woocommerce')}
            </Notice>
        );
    }

    if ( shippingAddress && shippingAddress.country !== 'DE' ) {
        return null;
    }

    // Render DHL logo dynamically from the localized data
    return (<table className="dhl-co-table">
        {/* DHL logo */}
        <tr className="dhl-co-tr dhl-co-tr-first">
            <td colSpan="2">
                <img src={imgUrl} alt="DHL logo" className="dhl-co-logo" />
            </td>
        </tr>

        {/* Title and description */}
        <tr className="dhl-co-tr">
            <th colSpan="2">
                {__('DHL Preferred Delivery. Delivered just as you wish.', 'dhl-for-woocommerce')}
                <hr />
            </th>
        </tr>

        <tr className="dhl-co-tr">
            <td colSpan="2">
                {__('Thanks to the flexible recipient services of DHL Preferred Delivery, you decide when and where you want to receive your parcels. Please choose your preferred delivery option.', 'dhl-for-woocommerce')}
            </td>
        </tr>
        {/* Preferred Delivery Day */}
        {dhlSettings?.dhl_preferred_day === 'yes' && (<>
            <tr className="dhl-co-tr">
                <th colSpan="2" className="dhl-pt">
                    {__('Delivery day: Delivery at your preferred day', 'dhl-for-woocommerce')}
                    <span
                        className="dhl-tooltip"
                        title={__('Choose one of the displayed days as your preferred day for your parcel delivery. Other days are not possible due to delivery processes.', 'dhl-for-woocommerce')}
                    >
                        ?
                    </span>
                </th>
            </tr>
            <tr className="dhl-co-tr">
                <td colSpan="2">
                    {dhlSettings?.dhl_preferred_day_cost && parseFloat(dhlSettings.dhl_preferred_day_cost) > 0 ? (
                        <>
                            {sprintf(
                                __('There is a surcharge of %s incl. VAT for this service.*', 'dhl-for-woocommerce'),
                                wcPrice(parseFloat(dhlSettings.dhl_preferred_day_cost))
                            )}
                        </>
                    ) : (
                        __('There is a surcharge for this service.', 'dhl-for-woocommerce')
                    )}
                </td>
            </tr>
            <tr className="dhl-co-tr">
                <td colSpan="2">
                    {preferredDayOptions.length > 0 ? (
                        <ul className="dhl-co-times">
                            {preferredDayOptions.map((option, index) => (
                                <li key={index}>
                                    <input
                                        type="radio"
                                        name="pr_dhl_preferred_day"
                                        className="pr_dhl_preferred_day"
                                        data-index="0"
                                        id={`pr_dhl_preferred_day_${option.key}`}
                                        value={option.key}
                                        checked={preferredDay === option.key}
                                        onChange={(e) => setPreferredDay(e.target.value)}
                                    />
                                    <label htmlFor={`pr_dhl_preferred_day_${option.key}`}>
                                        {option.weekDayNum}<br />{option.dayName}
                                    </label>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <i>
                            {__('Unfortunately, for the selected delivery address the service Delivery Day is not available', 'dhl-for-woocommerce')}
                        </i>
                    )}
                </td>
            </tr>
        </>)}

        {/* Drop-off location or neighbor */}
        {showRadioControl && (<>
            <tr className="dhl-co-tr">
                <th className="dhl-pt">
                    {__('Drop-off location or neighbor', 'dhl-for-woocommerce')}
                </th>
                <td className="dhl-pt">
                    <RadioControl
                        selected={preferredLocationNeighbor}
                        options={[
                            { label: __('None', 'dhl-for-woocommerce'), value: 'none' },
                            { label: __('Location', 'dhl-for-woocommerce'), value: 'location' },
                            { label: __('Neighbor', 'dhl-for-woocommerce'), value: 'neighbor' },
                        ]}
                        onChange={(value) => setPreferredLocationNeighbor(value)}
                    />
                </td>
            </tr>
        </>)}

        {/* Preferred Drop-off Location */}
        {showDropOffLocation && (<>
            <tr className="dhl-co-tr">
                <th colSpan="2" className="dhl-pt">
                    {__('Drop-off location: Delivery to your preferred drop-off location', 'dhl-for-woocommerce')}
                </th>
            </tr>
            <tr className="dhl-co-tr">
                <td colSpan="2">
                    <div className="wc-block-components-text-input">
                        <TextControl
                            placeholder={__('e.g. Garage, Terrace', 'dhl-for-woocommerce')}
                            value={preferredLocation}
                            onChange={(value) => setPreferredLocation(value)}
                        />
                    </div>
                </td>
            </tr>
        </>)}

        {/* Preferred Neighbor */}
        {showNeighborFields && (<>
            <tr className="dhl-co-tr">
                <th colSpan="2" className="dhl-pt">
                    {__('Neighbour: Delivery to a neighbour of your choice', 'dhl-for-woocommerce')}
                </th>
            </tr>
            <tr className="dhl-co-tr">
                <td colSpan="2">
                    <div className="wc-block-components-text-input">
                        <TextControl
                            className={'pr-dhl-other-textarea'}
                            placeholder={__('First name, last name of neighbour', 'dhl-for-woocommerce')}
                            value={preferredNeighborName}
                            onChange={(value) => setPreferredNeighborName(value)}
                        />
                    </div>
                </td>
            </tr>
            <tr className="dhl-co-tr">
                <td colSpan="2" className="">
                    <div className="wc-block-components-text-input">
                        <TextControl
                            placeholder={__('Street, number, postal code, city', 'dhl-for-woocommerce')}
                            value={preferredNeighborAddress}
                            onChange={(value) => setPreferredNeighborAddress(value)}
                        />
                    </div>
                </td>
            </tr>
        </>)}
    </table>);
};

// Helper function to format price
function wcPrice(amount) {
    // Assuming you have access to WooCommerce currency settings
    const currencySymbol = prDhlGlobals.currencySymbol || '€';
    const currencyPosition = prDhlGlobals.currencyPosition || 'left';
    const decimals = prDhlGlobals.currencyDecimals || 2;
    const decimalSeparator = prDhlGlobals.currencyDecimalSeparator || '.';
    const thousandSeparator = prDhlGlobals.currencyThousandSeparator || ',';

    amount = parseFloat(amount).toFixed(decimals);

    const parts = amount.toString().split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);
    const formattedAmount = parts.join(decimalSeparator);

    if (currencyPosition === 'left') {
        return currencySymbol + formattedAmount;
    } else {
        return formattedAmount + currencySymbol;
    }
}
