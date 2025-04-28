/**
 * External dependencies
 */
import {__} from '@wordpress/i18n';
import {
    useBlockProps,
    RichText,
    InspectorControls,
} from '@wordpress/block-editor';
import {PanelBody, SelectControl, RadioControl, TextControl, Disabled} from '@wordpress/components';
import {getSetting} from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import './style.scss';

const {defaultLabelText} = getSetting('pr-dhl_data', '');

export const Edit = ({attributes, setAttributes}) => {
    const {
        mainTitle,
        infoText,
        deliveryDayLabel,
        dropOffOrNeighborLabel,
        dropOffLocationLabel,
        neighborLabel,
    } = attributes;

    const blockProps = useBlockProps();

    return (
        <div {...blockProps} style={{display: 'block'}}>
            <InspectorControls>
                <PanelBody title={__('DHL Block Options', 'dhl-for-woocommerce')}>
                </PanelBody>
            </InspectorControls>

            <div>
                <RichText
                    value={
                        mainTitle ||
                        defaultLabelText ||
                        __('DHL Preferred Delivery. Delivered just as you wish.', 'dhl-for-woocommerce')
                    }
                    onChange={(value) => setAttributes({mainTitle: value})}
                />
                <RichText
                    value={
                        infoText ||
                        defaultLabelText ||
                        __('Thanks to the ﬂexible recipient services of DHL Preferred Delivery, you decide when and where you want to receive your parcels.', 'dhl-for-woocommerce')
                    }
                    onChange={(value) => setAttributes({infoText: value})}
                />
                <RichText
                    value={
                        deliveryDayLabel ||
                        defaultLabelText ||
                        __('Delivery day: Delivery at your preferred day ', 'dhl-for-woocommerce')
                    }
                    onChange={(value) => setAttributes({deliveryDayLabel: value})}
                />

                <Disabled>
                    <SelectControl
                        label={deliveryDayLabel}
                        options={[]}
                    />
                </Disabled>
                <RichText
                    value={
                        dropOffOrNeighborLabel ||
                        defaultLabelText ||
                        __('Drop-off location or neighbor', 'dhl-for-woocommerce')
                    }
                    onChange={(value) => setAttributes({dropOffOrNeighborLabel: value})}
                />
                <Disabled>
                    <RadioControl
                        label={dropOffOrNeighborLabel}
                        options={[
                            {label: 'None', value: 'none'},
                            {label: 'Location', value: 'location'},
                            {label: 'Neighbor', value: 'neighbor'},
                        ]}
                    />
                </Disabled>
                <RichText
                    value={
                        dropOffLocationLabel ||
                        defaultLabelText ||
                        __('Drop-off location: Delivery to your preferred drop-off location ', 'dhl-for-woocommerce')
                    }
                    onChange={(value) => setAttributes({dropOffLocationLabel: value})}
                />
                <Disabled>
                    <TextControl
                        placholder={__('e.g. Garage, Terrace', 'dhl-for-woocommerce')}
                    />
                </Disabled>
                <RichText
                    value={
                        neighborLabel ||
                        defaultLabelText ||
                        __('Neighbour: Delivery to a neighbour of your choice ', 'dhl-for-woocommerce')
                    }
                    onChange={(value) => setAttributes({neighborLabel: value})}
                />
                <Disabled>
                    <TextControl
                        placholder={__('First name, last name of neighbour', 'dhl-for-woocommerce')}
                    />
                </Disabled>
                <Disabled>
                    <TextControl
                        placholder={__('Street, number, postal code, city', 'dhl-for-woocommerce')}
                    />
                </Disabled>
            </div>
        </div>
    );
};

export const Save = ({attributes}) => {
    return (null);
};