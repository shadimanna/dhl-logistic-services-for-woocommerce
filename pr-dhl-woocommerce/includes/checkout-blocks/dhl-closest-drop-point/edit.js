/**
 * External dependencies
 */
import {__} from '@wordpress/i18n';
import {
    useBlockProps,
    RichText,
    InspectorControls,
} from '@wordpress/block-editor';
import {PanelBody} from '@wordpress/components';
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
        homeDeliveryLabel,
        closestDropPointLabel,
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
                        homeDeliveryLabel ||
                        defaultLabelText ||
                        __('Home delivery', 'dhl-for-woocommerce')
                    }
                    onChange={(value) => setAttributes({homeDeliveryLabel: value})}
                />
                <RichText
                    value={
                        closestDropPointLabel ||
                        defaultLabelText ||
                        __('Closest Drop Point', 'dhl-for-woocommerce')
                    }
                    onChange={(value) => setAttributes({closestDropPointLabel: value})}
                />

            </div>
        </div>
    );
};

export const Save = ({attributes}) => {
    return (null);
};