import {useState, useEffect, useCallback} from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import {Button, TextControl, SelectControl} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import axios from 'axios';
import {debounce} from "lodash";

export const Block = ({checkoutExtensionData}) => {

    const { setExtensionData } = checkoutExtensionData;

    // Debounce for reducing the number of updates to the extension data
    const debouncedSetExtensionData = useCallback(debounce((namespace, key, value) => {
        setExtensionData(namespace, key, value);
    }, 500), [setExtensionData]);

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

    // Determine address type
    const [addressType, setAddressType] = useState('');
    const [postNumber, setPostNumber]   = useState('');

    // Derive booleans for each filter.
    const isPackstation = addressType === 'dhl_packstation';
    const isBranch      = addressType === 'dhl_branch';

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

    const showMapButton = hasCalculatedShipping &&
        shippingRates.length > 0 &&
        isPageLoaded && (shippingAddress.country === 'DE') &&
        prDhlGlobals.dhlSettings.display_google_maps;

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
        formData.append('packstation_filter', isPackstation ? 'true' : 'false');
        formData.append('branch_filter',      isBranch      ? 'true' : 'false');
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
    }, [shippingAddress, isPackstation, isBranch]);

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
                    shop_name = __('Postfiliale', 'dhl-for-woocommerce');
                    address_type = 'dhl_branch';
                    break;
                case 'postoffice':
                case 'postbank':
                    shop_name = __('Postfiliale', 'dhl-for-woocommerce');
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
            };

            setShippingAddress(newShippingAddress);
            setAddressType(address_type);
        }
    }, [dropOffPoint]);
    useEffect(() => {
        const handleShopSelected = (event) => {

            const { address_1, address_2, postcode, city ,parcel_ID} = event.detail;
            const newShippingAddress = {
                ...shippingAddress,
                address_1,
                address_2,
                postcode,
                city,
            };
            setShippingAddress(newShippingAddress);
            setDropOffPoint(parcel_ID);
            setAddressType(address_type);

        };

        window.addEventListener('dhl-shop-selected', handleShopSelected);

        return () => {
            window.removeEventListener('dhl-shop-selected', handleShopSelected);
        };
    }, [setShippingAddress, shippingAddress]);

    // Determine the correct registration link based on locale.
    const registrationLink = prDhlGlobals.locale === 'en_US'
        ? prDhlGlobals.DHL_ENGLISH_REGISTRATION_LINK
        : prDhlGlobals.DHL_GERMAN_REGISTRATION_LINK;

    const validationErrorId = 'dhl-post-number-validation';

    //clearing validation errors.
    const { setValidationErrors, clearValidationError } = useDispatch( 'wc/store/validation' );

    // Retrieve the current validation error.
    const validationError = useSelect( ( select ) => {
        return select( 'wc/store/validation' ).getValidationError( validationErrorId );
    }, [ validationErrorId ] );

    useEffect(() => {
        setExtensionData('pr-dhl', 'addressType', addressType);
        debouncedSetExtensionData('pr-dhl', 'addressType', addressType);
    }, [addressType]);

    useEffect(() => {
        setExtensionData('pr-dhl', 'postNumber', postNumber);
        debouncedSetExtensionData('pr-dhl', 'postNumber', postNumber);
    }, [postNumber]);

    /**
     * PostNumber Validation.
     */

    useEffect(() => {
        clearValidationError(validationErrorId);

        const address1 = shippingAddress.address_1 ? shippingAddress.address_1 : '';
        const pos_ps = address1.includes('packstation');
        const pos_rs = address1.includes('Postfiliale');
        const pos_po = address1.includes('Postfiliale');

        if ( addressType === 'dhl_packstation' ) {

            if ( ! postNumber.trim() ) {
                setValidationErrors({
                    [validationErrorId]: {
                        message: __( 'Post Number is mandatory for a Packstation location.', 'dhl-for-woocommerce' ),
                        hidden: false,
                    },
                });
                return;
            }

            if ( ! pos_ps ) {
                setValidationErrors({
                    [validationErrorId]: {
                        message: __( 'The text "Packstation" must be included in the address.', 'dhl-for-woocommerce' ),
                        hidden: false,
                    },
                });
                return;
            }

        } else if ( addressType === 'dhl_branch' ) {
            if ( ! pos_rs ) {
                setValidationErrors({
                    [validationErrorId]: {
                        message: __( 'The text "Postfiliale" must be included in the address.', 'dhl-for-woocommerce' ),
                        hidden: false,
                    },
                });
                return;
            }
            if ( ! pos_po ) {
                setValidationErrors({
                    [validationErrorId]: {
                        message: __( 'The text "Postfiliale" must be included in the address.', 'dhl-for-woocommerce' ),
                        hidden: false,
                    },
                });
                return;
            }
        }

        if ( postNumber ) {
            if ( isNaN( parseInt( postNumber, 10 ) ) ) {
                setValidationErrors({
                    [validationErrorId]: {
                        message: __( 'Post Number must be a number.', 'dhl-for-woocommerce' ),
                        hidden: false,
                    },
                });
                return;
            }

            if ( postNumber.length < 6 || postNumber.length > 12 ) {
                setValidationErrors({
                    [validationErrorId]: {
                        message: __( 'The post number you entered is not valid. Please correct the number.', 'dhl-for-woocommerce' ),
                        hidden: false,
                    },
                });
                return;
            }
        }

    }, [
        addressType,
        postNumber,
        shippingAddress,
        clearValidationError,
        setValidationErrors,
        validationErrorId,
    ]);

    const addressTypeOptions = [
        {label: __('Address Type', 'dhl-for-woocommerce'), value: ''},
        {label: __('Regular Address', 'dhl-for-woocommerce'), value: 'normal'},
    ];
    if (prDhlGlobals.dhlSettings.display_packstation) {
        addressTypeOptions.push({
            label: __('DHL Packstation', 'dhl-for-woocommerce'),
            value: 'dhl_packstation',
        });
    }

    if (prDhlGlobals.dhlSettings.display_post_office) {
        addressTypeOptions.push({
            label: __('DHL Branch', 'dhl-for-woocommerce'),
            value: 'dhl_branch',
        });
    }


    return (
        <>

            {showMapButton && (prDhlGlobals.dhlSettings.display_post_office || prDhlGlobals.dhlSettings.display_parcelshop || prDhlGlobals.dhlSettings.display_packstation ) && (
                <>

                    {/* Registration info displayed above the shipping fields */}
                    <div className="registration_info">
                        {__('For deliveries to DHL Parcel Lockers you have to', 'dhl-for-woocommerce')}{' '}
                        <a href={registrationLink} target="_blank" rel="noopener noreferrer">
                            {__('create a DHL account', 'dhl-for-woocommerce')}
                        </a>{' '}
                        {__('and get a Post Number.', 'dhl-for-woocommerce')}
                    </div>
                    <Button
                        isPrimary
                        id="dhl_parcel_finder"
                        data-fancybox
                        data-src="#dhl_parcel_finder_form"
                        data-options='{"touch":false,"clickSlide":false,"dragToClose":false}'
                        href="javascript:;"
                        onClick={() => {
                            // Reinitialize jQuery bindings, for example:
                            if (typeof wc_checkout_dhl_parcelfinder !== 'undefined') {
                                wc_checkout_dhl_parcelfinder.init();
                            }
                        }}
                    >
                        {__('Search Packstation / Branch', 'dhl-for-woocommerce')}
                        <img
                            src={`${prDhlGlobals.pluginUrl}/assets/img/dhl-official.png`}
                            alt="DHL logo"
                            className="dhl-co-logo"
                        />
                    </Button>

                    <div style={{display: 'none'}}>
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

                                {prDhlGlobals.dhlSettings.display_packstation && (
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
                                            style={{backgroundImage: `url(${prDhlGlobals.pluginUrl}/assets/img/packstation.png)`}}
                                        ></span>
                                    </p>
                                )}

                                {(prDhlGlobals.dhlSettings.display_parcelshop || prDhlGlobals.dhlSettings.display_post_office) && (
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
                                            {prDhlGlobals.dhlSettings.display_parcelshop && (
                                                <span
                                                    className="icon"
                                                    style={{backgroundImage: `url(${prDhlGlobals.pluginUrl}/assets/img/parcelshop.png)`}}
                                                ></span>
                                            )}
                                            {prDhlGlobals.dhlSettings.display_post_office && (
                                                <span
                                                    className="icon"
                                                    style={{backgroundImage: `url(${prDhlGlobals.pluginUrl}/assets/img/post_office.png)`}}
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

                                <input type="hidden" name="dhl_parcelfinder_country" id="dhl_parcelfinder_country"/>
                                <input type="hidden" name="dhl_parcelfinder_nonce" value={prDhlGlobals.parcel_nonce}/>

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
            { validationError && ! validationError.hidden && (
                <div className="wc-block-components-validation-error">
                    { validationError.message }
                </div>
            ) }

            {/* Address Type */}
            {((prDhlGlobals.dhlSettings.display_post_office || prDhlGlobals.dhlSettings.display_parcelshop || prDhlGlobals.dhlSettings.display_packstation))  && shippingAddress.country === 'DE' && (
                <>
                    <SelectControl
                        className="wc-blocks-components-select__select"
                        value={addressType}
                        id="shipping_dhl_address_type"
                        onChange={(value) => setAddressType(value)}
                        options={addressTypeOptions}
                    />
                </>
            )}

            {/* Drop off point */}
            {showMapButton && addressType !== 'normal' && (prDhlGlobals.dhlSettings.display_post_office || prDhlGlobals.dhlSettings.display_packstation ) &&(
                <>
                    <div className="wc-blocks-components-select__select">
                        <SelectControl
                            value={dropOffPoint}
                            onChange={(value) => setDropOffPoint(value)}
                            options={[
                                {label: __('Select a drop off points', 'dhl-for-woocommerce'), value: ''},
                                ...parcelShops.map((shop) => ({
                                    label: shop.name,
                                    value: shop.location.ids[0].locationId,
                                })),
                            ]}
                        />

                    </div>
                </>
            )}
            {/* Post Number */}
            {addressType !== 'normal' && (prDhlGlobals.dhlSettings.display_post_office || prDhlGlobals.dhlSettings.display_packstation ) &&(
                <>
                    <div className="wc-block-components-text-input ">
                        <TextControl
                            placeholder={ __('Post Number', 'dhl-for-woocommerce') }
                            value={ postNumber }
                            className="wc-block-components-text-input"
                            onChange={ ( val ) => setPostNumber(val) }
                            required={ addressType === 'dhl_packstation' }
                        />
                    </div>
                </>
            )}
        </>
    );
};
