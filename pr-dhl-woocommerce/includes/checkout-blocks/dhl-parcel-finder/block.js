import { useState, useCallback } from '@wordpress/element';
import { Modal, Button, TextControl, CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export const Block = ({ checkoutExtensionData }) => {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [postcode, setPostcode] = useState('');
    const [city, setCity] = useState('');
    const [address, setAddress] = useState('');
    const [isPackstationEnabled, setIsPackstationEnabled] = useState(prDhlGlobals.packstation_enabled);
    const [isBranchEnabled, setIsBranchEnabled] = useState(prDhlGlobals.parcelshop_enabled || prDhlGlobals.post_office_enabled);

    const toggleModal = useCallback(() => setIsModalOpen((prev) => !prev), []);

    const imgUrl = prDhlGlobals.pluginUrl + "/assets/img/dhl-official.png";
    const packstationImgUrl = prDhlGlobals.pluginUrl + "/assets/img/packstation.png";
    const parcelshopImgUrl = prDhlGlobals.pluginUrl + "/assets/img/parcelshop.png";
    const postOfficeImgUrl = prDhlGlobals.pluginUrl + "/assets/img/post_office.png";

    const handleSearch = (event) => {
        event.preventDefault();
        // Perform AJAX call to parcel finder API here
    };

    return (
        <>
            <Button isPrimary onClick={toggleModal} className="dhl-search-button">
                {__('Search Packstation / Branch', 'dhl-for-woocommerce')}
                <img src={imgUrl} alt="DHL logo" className="dhl-co-logo" />
            </Button>
            {isModalOpen && (
                <Modal
                    title={__('DHL Parcel Finder', 'dhl-for-woocommerce')}
                    onRequestClose={toggleModal}
                    className="dhl-parcel-finder-modal"
                    isFullScreen
                >
                    <form id="checkout_dhl_parcel_finder" onSubmit={handleSearch}>
                        <TextControl
                            label={__('Post Code', 'dhl-for-woocommerce')}
                            value={postcode}
                            onChange={(value) => setPostcode(value)}
                            id="dhl_parcelfinder_postcode"
                        />
                        <TextControl
                            label={__('City', 'dhl-for-woocommerce')}
                            value={city}
                            onChange={(value) => setCity(value)}
                            id="dhl_parcelfinder_city"
                        />
                        <TextControl
                            label={__('Address', 'dhl-for-woocommerce')}
                            value={address}
                            onChange={(value) => setAddress(value)}
                            id="dhl_parcelfinder_address"
                        />
                        {prDhlGlobals.packstation_enabled && (
                            <CheckboxControl
                                label={__('Packstation', 'dhl-for-woocommerce')}
                                checked={isPackstationEnabled}
                                onChange={(isChecked) => setIsPackstationEnabled(isChecked)}
                                id="dhl_packstation_filter"
                            />
                        )}
                        {(prDhlGlobals.parcelshop_enabled || prDhlGlobals.post_office_enabled) && (
                            <CheckboxControl
                                label={__('Branch', 'dhl-for-woocommerce')}
                                checked={isBranchEnabled}
                                onChange={(isChecked) => setIsBranchEnabled(isChecked)}
                                id="dhl_branch_filter"
                            />
                        )}
                        <div className="parcel-icons">
                            {prDhlGlobals.packstation_enabled && (
                                <span className="icon" style={{ backgroundImage: `url(${packstationImgUrl})` }}></span>
                            )}
                            {prDhlGlobals.parcelshop_enabled && (
                                <span className="icon" style={{ backgroundImage: `url(${parcelshopImgUrl})` }}></span>
                            )}
                            {prDhlGlobals.post_office_enabled && (
                                <span className="icon" style={{ backgroundImage: `url(${postOfficeImgUrl})` }}></span>
                            )}
                        </div>
                        <Button isPrimary type="submit">
                            {__('Search', 'dhl-for-woocommerce')}
                        </Button>
                    </form>
                    <div id="dhl_google_map"></div>
                </Modal>
            )}
        </>
    );
};
