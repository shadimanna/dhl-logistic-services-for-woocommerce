import {useEffect, useState, useCallback} from '@wordpress/element';
import {TextControl, SelectControl, RadioControl} from '@wordpress/components';
import {__} from '@wordpress/i18n';
import {debounce} from 'lodash';

export const Block = ({checkoutExtensionData}) => {
    const {setExtensionData} = checkoutExtensionData;

    // Access the localized data from prDhlGlobals
    const imgUrl = prDhlGlobals.logoUrl;
    const dhlSettings = prDhlGlobals.dhlSettings;
    const preferredDays = prDhlGlobals.preferredDays;

    // Debounce for reducing the number of updates to the extension data
    const debouncedSetExtensionData = useCallback(debounce((namespace, key, value) => {
        setExtensionData(namespace, key, value);
    }, 1000), [setExtensionData]);

    // Determine availability of location and neighbor options
    const locationAvailable = dhlSettings?.dhl_preferred_location === 'yes';
    const neighborAvailable = dhlSettings?.dhl_preferred_neighbour === 'yes';
    const showRadioControl = locationAvailable && neighborAvailable;

    // Initialize preferredLocationNeighbor
    const initialPreferredLocationNeighbor = showRadioControl ? 'none' : locationAvailable ? 'location' : neighborAvailable ? 'neighbor' : 'none';

    // State hooks for the block fields
    const [preferredDay, setPreferredDay] = useState('');
    const [preferredLocationNeighbor, setPreferredLocationNeighbor] = useState(initialPreferredLocationNeighbor);
    const [preferredLocation, setPreferredLocation] = useState('');
    const [preferredNeighborName, setPreferredNeighborName] = useState('');
    const [preferredNeighborAddress, setPreferredNeighborAddress] = useState('');

    // useEffect for preferredDay
    useEffect(() => {
        setExtensionData('pr-dhl', 'preferredDay', preferredDay);
        debouncedSetExtensionData('pr-dhl', 'preferredDay', preferredDay);
    }, [setExtensionData, preferredDay, debouncedSetExtensionData]);

    // useEffect for preferredLocationNeighbor
    useEffect(() => {
        setExtensionData('pr-dhl', 'preferredLocationNeighbor', preferredLocationNeighbor);
        debouncedSetExtensionData('pr-dhl', 'preferredLocationNeighbor', preferredLocationNeighbor);
    }, [setExtensionData, preferredLocationNeighbor, debouncedSetExtensionData]);

    // useEffect for preferredLocation
    useEffect(() => {
        setExtensionData('pr-dhl', 'preferredLocation', preferredLocation);
        debouncedSetExtensionData('pr-dhl', 'preferredLocation', preferredLocation);
    }, [setExtensionData, preferredLocation, debouncedSetExtensionData]);

    // useEffect for preferredNeighborName
    useEffect(() => {
        setExtensionData('pr-dhl', 'preferredNeighborName', preferredNeighborName);
        debouncedSetExtensionData('pr-dhl', 'preferredNeighborName', preferredNeighborName);
    }, [setExtensionData, preferredNeighborName, debouncedSetExtensionData]);

    // useEffect for preferredNeighborAddress
    useEffect(() => {
        setExtensionData('pr-dhl', 'preferredNeighborAddress', preferredNeighborAddress);
        debouncedSetExtensionData('pr-dhl', 'preferredNeighborAddress', preferredNeighborAddress);
    }, [setExtensionData, preferredNeighborAddress, debouncedSetExtensionData]);

    // Handle visibility of drop-off location and neighbor fields based on settings and selection
    const showDropOffLocation = (showRadioControl && preferredLocationNeighbor === 'location') || (!showRadioControl && locationAvailable);
    const showNeighborFields = (showRadioControl && preferredLocationNeighbor === 'neighbor') || (!showRadioControl && neighborAvailable);

    // Convert preferredDays object to an array of options for SelectControl
    const preferredDayOptions = Object.keys(preferredDays).map((key) => {
        if (key === '0' || key === 'none') {
            return {
                label: preferredDays[key], value: key,
            };
        }

        return {
            label: `${new Date(key).getDate()} ${preferredDays[key]}`, value: key,
        };
    });

    // Render DHL logo dynamically from the localized data
    return (<table className="dhl-co-table">
        {/* DHL logo */}
        <tr className="dhl-co-tr dhl-co-tr-first">
            <td colSpan="2">
                <img src={imgUrl} alt="DHL logo" className="dhl-co-logo"/>
            </td>
        </tr>

        {/* Title and description */}
        <tr className="dhl-co-tr">
            <th colSpan="2">
                {__('DHL Preferred Delivery. Delivered just as you wish.', 'dhl-for-woocommerce')}
                <hr/>
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
                    {__('There is a surcharge for this service.', 'dhl-for-woocommerce')}
                </td>
            </tr>
            <tr className="dhl-co-tr">
                <td colSpan="2">
                    <SelectControl
                        value={preferredDay}
                        options={preferredDayOptions}
                        onChange={(value) => setPreferredDay(value)}
                    />
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
                        options={[{label: 'None', value: 'none'}, {
                            label: 'Location',
                            value: 'location'
                        }, {label: 'Neighbor', value: 'neighbor'},]}
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
