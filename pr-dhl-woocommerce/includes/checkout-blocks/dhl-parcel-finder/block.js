import { useEffect } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export const Block = () => {


    return (
        <>
            {/* Button to trigger Fancybox */}
            <Button
                isPrimary
                id="dhl_parcel_finder"
                data-fancybox
                data-src="#dhl_parcel_finder_form"
                href="javascript:;"
            >
                {__('Search Packstation / Branch', 'dhl-for-woocommerce')}
                <img src={`${prDhlGlobals.pluginUrl}/assets/img/dhl-official.png`} alt="DHL logo" className="dhl-co-logo" />
            </Button>

            {/* Hidden Parcel Finder Form */}
            <div style={{ display: 'none' }}>
                <div id="dhl_parcel_finder_form">
                    <form id="checkout_dhl_parcel_finder" method="post">

                        <p className="form-row form-field small">
                            <input type="text" name="dhl_parcelfinder_postcode" className="input-text" placeholder={__('Post Code', 'dhl-for-woocommerce')} id="dhl_parcelfinder_postcode" />
                        </p>

                        <p className="form-row form-field small">
                            <input type="text" name="dhl_parcelfinder_city" className="input-text" placeholder={__('City', 'dhl-for-woocommerce')} id="dhl_parcelfinder_city" />
                        </p>

                        <p className="form-row form-field large">
                            <input type="text" name="dhl_parcelfinder_address" className="input-text" placeholder={__('Address', 'dhl-for-woocommerce')} id="dhl_parcelfinder_address" />
                        </p>

                        {prDhlGlobals.packstation_enabled && (
                            <p className="form-row form-field packstation">
                                <input type="checkbox" name="dhl_packstation_filter" className="input-checkbox" id="dhl_packstation_filter" value="1" defaultChecked />
                                <label htmlFor="dhl_packstation_filter">{__('Packstation', 'dhl-for-woocommerce')}</label>
                                <span className="icon" style={{ backgroundImage: `url(${prDhlGlobals.pluginUrl}/assets/img/packstation.png)` }}></span>
                            </p>
                        )}

                        {(prDhlGlobals.parcelshop_enabled || prDhlGlobals.post_office_enabled) && (
                            <p className="form-row form-field parcelshop">
                                <input type="checkbox" name="dhl_branch_filter" className="input-checkbox" id="dhl_branch_filter" value="1" defaultChecked />
                                <label htmlFor="dhl_branch_filter">{__('Branch', 'dhl-for-woocommerce')}</label>
                                <span className="parcel-wrap">
                                    {prDhlGlobals.parcelshop_enabled && (
                                        <span className="icon" style={{ backgroundImage: `url(${prDhlGlobals.pluginUrl}/assets/img/parcelshop.png)` }}></span>
                                    )}
                                    {prDhlGlobals.post_office_enabled && (
                                        <span className="icon" style={{ backgroundImage: `url(${prDhlGlobals.pluginUrl}/assets/img/post_office.png)` }}></span>
                                    )}
                                </span>
                            </p>
                        )}

                        <p id="dhl_seach_button" className="form-row form-field small">
                            <input type="submit" className="button" name="apply_parcel_finder" value={__('Search', 'dhl-for-woocommerce')} />
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
    );
};
