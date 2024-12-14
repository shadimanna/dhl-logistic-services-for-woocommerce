import { useState, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { Button, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import axios from 'axios';

export const Block = () => {
    const [isPageLoaded, setIsPageLoaded] = useState(false);
    const [parcelShops, setParcelShops] = useState([]);
    const [dropOffPoint, setDropOffPoint] = useState('');

    // Retrieve shipping calculation & rates state
    const { hasCalculatedShipping, shippingRates } = useSelect((select) => {
        const cartStore = select('wc/store/cart');
        return {
            hasCalculatedShipping: cartStore.getHasCalculatedShipping(),
            shippingRates: cartStore.getShippingRates(),
        };
    }, []);

    // Retrieve & update shipping address
    const { setShippingAddress } = useDispatch('wc/store/cart');
    const shippingAddress = useSelect((select) =>
            select('wc/store/cart').getCustomerData()?.shippingAddress || {},
        []);

    // Handle page load (for when map button should display)
    useEffect(() => {
        const handlePageLoad = () => setIsPageLoaded(true);
        if (document.readyState === 'complete') {
            setIsPageLoaded(true);
        } else {
            window.addEventListener('load', handlePageLoad);
            return () => window.removeEventListener('load', handlePageLoad);
        }
    }, []);

    const showMapButton = hasCalculatedShipping && shippingRates.length > 0 && isPageLoaded;

    // Fetch parcel shops when shippingAddress changes and is available
    useEffect(() => {
        if (!shippingAddress || !shippingAddress.country || !shippingAddress.postcode) {
            return;
        }

        const formData = new URLSearchParams();
        formData.append('action', 'wc_shipment_dhl_parcelfinder_search');
        formData.append('parcelfinder_country', shippingAddress.country);
        formData.append('parcelfinder_postcode', shippingAddress.postcode);
        formData.append('parcelfinder_city', shippingAddress.city || '');
        formData.append('parcelfinder_address', shippingAddress.address_1 || '');
        // For demo, enabling both packstation & branch filters
        formData.append('packstation_filter', 'true');
        formData.append('branch_filter', 'true');
        formData.append('security', prDhlGlobals.parcel_nonce);

        axios.post(prDhlGlobals.ajax_url, formData.toString(), {
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        })
            .then((response) => {
                if (!response.data.error && response.data.parcel_res) {
                    setParcelShops(response.data.parcel_res);
                } else {
                    setParcelShops([]);
                }
            })
            .catch(() => {
                setParcelShops([]);
            });
    }, [shippingAddress]);

    // Update shipping address when user selects a drop-off point
    useEffect(() => {
        if (!dropOffPoint || parcelShops.length === 0) return;
        const selectedShop = parcelShops.find(shop => shop.location.ids[0].locationId === dropOffPoint);
        if (selectedShop) {
            let shop_name = '';
            let address_type = '';

            switch (selectedShop.location.type) {
                case 'locker':
                    shop_name = __('Packstation', 'dhl-for-woocommerce');
                    address_type = 'dhl_packstation';
                    break;
                case 'servicepoint':
                    shop_name = __('ParcelShop', 'dhl-for-woocommerce');
                    address_type = 'dhl_branch';
                    break;
                case 'postoffice':
                case 'postbank':
                    shop_name = __('Post Office', 'dhl-for-woocommerce');
                    address_type = 'dhl_branch';
                    break;
                default:
                    shop_name = __('Packstation', 'dhl-for-woocommerce');
                    address_type = 'dhl_packstation';
            }

            const newShippingAddress = {
                ...shippingAddress,
                address_1: `${shop_name} ${selectedShop.location.keywordId}`,
                address_2: '',
                postcode: selectedShop.place.address.postalCode,
                city: selectedShop.place.address.addressLocality,
                'pr-dhl/address_type': address_type,
            };

            setShippingAddress(newShippingAddress);
        }
    }, [dropOffPoint]);

    return (
        <>
            {showMapButton && (
                <>

                    {/* Existing button and fancybox form */}
                    <Button
                        isPrimary
                        id="dhl_parcel_finder"
                        data-fancybox
                        data-src="#dhl_parcel_finder_form"
                        href="javascript:;"
                    >
                        {__('Search Packstation / Branch', 'dhl-for-woocommerce')}
                        <img
                            src={`${prDhlGlobals.pluginUrl}/assets/img/dhl-official.png`}
                            alt="DHL logo"
                            className="dhl-co-logo"
                        />
                    </Button>

                    {/* New SelectControl for drop off points */}
                    <div className="wc-blocks-components-select__select">
                        <SelectControl
                            value={dropOffPoint}
                            onChange={(value) => setDropOffPoint(value)}
                            options={[
                                { label: __('Select a drop off points', 'dhl-for-woocommerce'), value: '' },
                                ...parcelShops.map((shop) => ({
                                    label: shop.name,
                                    value: shop.location.ids[0].locationId,
                                })),
                            ]}
                        />

                    </div>

                    <div style={{ display: 'none' }}>
                        <div id="dhl_parcel_finder_form">
                            <form id="checkout_dhl_parcel_finder" method="post">
                                <p className="form-row form-field small">
                                    <input
                                        type="text"
                                        name="dhl_parcelfinder_postcode"
                                        className="input-text"
                                        placeholder={__('Post Code', 'dhl-for-woocommerce')}
                                        id="dhl_parcelfinder_postcode"
                                    />
                                </p>

                                <p className="form-row form-field small">
                                    <input
                                        type="text"
                                        name="dhl_parcelfinder_city"
                                        className="input-text"
                                        placeholder={__('City', 'dhl-for-woocommerce')}
                                        id="dhl_parcelfinder_city"
                                    />
                                </p>

                                <p className="form-row form-field large">
                                    <input
                                        type="text"
                                        name="dhl_parcelfinder_address"
                                        className="input-text"
                                        placeholder={__('Address', 'dhl-for-woocommerce')}
                                        id="dhl_parcelfinder_address"
                                    />
                                </p>

                                {prDhlGlobals.packstation_enabled && (
                                    <p className="form-row form-field packstation">
                                        <input
                                            type="checkbox"
                                            name="dhl_packstation_filter"
                                            className="input-checkbox"
                                            id="dhl_packstation_filter"
                                            value="1"
                                            defaultChecked
                                        />
                                        <label htmlFor="dhl_packstation_filter">
                                            {__('Packstation', 'dhl-for-woocommerce')}
                                        </label>
                                        <span
                                            className="icon"
                                            style={{ backgroundImage: `url(${prDhlGlobals.pluginUrl}/assets/img/packstation.png)` }}
                                        ></span>
                                    </p>
                                )}

                                {(prDhlGlobals.parcelshop_enabled || prDhlGlobals.post_office_enabled) && (
                                    <p className="form-row form-field parcelshop">
                                        <input
                                            type="checkbox"
                                            name="dhl_branch_filter"
                                            className="input-checkbox"
                                            id="dhl_branch_filter"
                                            value="1"
                                            defaultChecked
                                        />
                                        <label htmlFor="dhl_branch_filter">
                                            {__('Branch', 'dhl-for-woocommerce')}
                                        </label>
                                        <span className="parcel-wrap">
                                            {prDhlGlobals.parcelshop_enabled && (
                                                <span
                                                    className="icon"
                                                    style={{ backgroundImage: `url(${prDhlGlobals.pluginUrl}/assets/img/parcelshop.png)` }}
                                                ></span>
                                            )}
                                            {prDhlGlobals.post_office_enabled && (
                                                <span
                                                    className="icon"
                                                    style={{ backgroundImage: `url(${prDhlGlobals.pluginUrl}/assets/img/post_office.png)` }}
                                                ></span>
                                            )}
                                        </span>
                                    </p>
                                )}

                                <p id="dhl_seach_button" className="form-row form-field small">
                                    <input
                                        type="submit"
                                        className="button"
                                        name="apply_parcel_finder"
                                        value={__('Search', 'dhl-for-woocommerce')}
                                    />
                                </p>

                                <input type="hidden" name="dhl_parcelfinder_country" id="dhl_parcelfinder_country" />
                                <input type="hidden" name="dhl_parcelfinder_nonce" value={prDhlGlobals.parcel_nonce} />

                                <div className="clear"></div>

                                {/* Close Button */}
                                <button data-fancybox-close className="fancybox-close-small" title="close">
                                    <svg viewBox="0 0 32 32">
                                        <path d="M10,10 L22,22 M22,10 L10,22"></path>
                                    </svg>
                                </button>
                            </form>

                            <div id="dhl_google_map"></div>
                        </div>
                    </div>
                </>
            )}
        </>
    );
};
