=== DHL for WooCommerce ===
Contributors: DHL, shadim, utzfu
Donate link: 
Tags: DPDHL, original DHL, DHL, DHL eCommerce, DHL express, DHL Parcel NL, DHL Parcel Benelux, DHL Parcel Luxembourg, DHL Paket Germany, WooCommerce, Woocom, Woo Commerce, Shipping, shiping, label creation, label printing, shipping rates, DHL Paket
Requires at least: 4.1
Requires PHP: 5.6
Tested up to: 5.0
Stable tag: 1.3.1
WC requires at least: 2.6.0
WC tested up to: 3.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The official DHL for WooCommerce plugin allows you to automate your e-commerce order process. Covering shipping services from DHL eCommerce (globally), DHL Paket (Germany and Austria), DHL Parcel (Benelux) and soon DHL Express (globally).


== Description ==

DHL’s official extension for WooCommerce on Wordpress. Manage your national and international shipments easily.  The “DHL for WooCommerce” – plugin is compatible with (whether the plugin is available in your country see [here](https://www.logistics.dhl/content/dam/dhl/local/global/dhl-ecommerce/images/text-generic-1592x896/g0-integration-3pv-wooocommerce-00.web.796.448.jpg "Global DHL service coverage within WooCommerce").):
* DHL Paket (Germany and Austria)
* DHL Parcel Europe
* DHL eCommerce


== Features ==

1. Fast and easy **label creation** of your national and international orders.
1. Automatically receive a **tracking code** for each label.
1. **Create Handover Notes** conveniently for a smooth and reliable manifesting process in-line with your specific regional requirements for DHL eCommerce.
1. Use **additional delivery services** as e.g. the visual check of age available via the API of DHL Paket or Cash on delivery by DHL eCommerce in selected countries. 
1. Offer **Preferred Delivery Options** to your customers via “Wunschpaket”. The customer has the opportunity to select a specific time and date for his delivery.
1. **Customization** Enable/disable or edit the names of services and set up the handling cost for each DHL shipping service.
1. Experience **premium support**, timely compatibility updates and bug fixes.
1. The **“print only if codeable”** – option you can activate in the DHL settings will check whether the address is correct or not before generating the label.
1. **Bulk Label Creation** allows you to create multiple DHL Labels at once. 
1. **Return Parcel Handling** allows you to print a return label with a “return address” so your customer can return the shipment easily. 


== Availability by countries and prerequisites == 

Based on your sender country and shipping preference, different access credentials for **DHL Paket, DHL Parcel Europe** and **DHL eCommerce** are required for the configuration: 

**DHL Paket for Germany and Austria** log in with your business customer portal credentials. (not a customer yet? Click [here](https://www.dhl.de/en/geschaeftskunden/paket/kunde-werden/angebot-dhl-geschaeftskunden-online.html) for **DHL Paket**)

**DHL Parcel Europe** for BeNeLux, Iberia and Switzerland please self-generate your API credentials with your business customer portal account.  (not a customer yet? Click [here](https://www.dhlparcel.nl/en/get-quote) for **DHL Parcel Netherlands**, here for **DHL Parcel Switzerland** and [here](https://www.dhlparcel.be/nl/offerte-aanvragen) for **DHL Parcel Belgium**). 
**DHL eCommerce**: send us your company name and customer account number via the form [here](https://www.logistics.dhl/us-en/home/all-products-and-solutions/technology-platform-integration/request-api-access.html) and we will provide you the credentials for this plugin. (not a customer yet? Click [here](http://www.dhl.com/signup-wooCommerce)). 

== Installation & Configuration ==

1. Upload the downloaded plugin files to your `/wp-content/plugins/DHL-for-WooCommerce` directory, **OR** install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Go to WooCommerce-->Settings->Shipping and select the upper DHL unit (depending on your home country this is DHL Paket, DHL eCommerce) etc to configure the plugin

...for **DHL Paket (Germany, Austria)**: you need your EKP number (10 digits) and add the participation numbers to the respective products available (you will find the participation numbers in the DHL business customer portal).
...for **DHL Parcel Europe (BeNeLux, Iberia, Switzerland)**: you need your self-generated API credentials (UserID and Key). Push the “Test connection” and the fields below will be prefilled automatically. 
...for **DHL eCommerce**: you need your customer account number, the distribution center you are using, your client-ID and client-secret. 


== Support ==

More detailed instructions on how to set up your store and configure it are consolidated on on the page [http://www.dhl.com/WooCommerce](http://www.dhl.com/wooCommerce "All About DHL for WooCommerce")

Click [here](www.dhl.com/faqs) for our FAQs or check out our [integration page](www.dhl.com/Integration) with alternative integration options.

For individual support please use our support form [here](https://www.logistics.dhl/us-en/home/all-products-and-solutions/technology-platform-integration/request-technical-help.html)


== Additional Information ==  
* The plugin contains a tracking pixel due to reporting purposes of preferred services. Its output is the URL where the preferred services are integrated via plugin as well as the number of plugin calls. There is no personal data created or collected.
* In case you like to offer direct shipment to DHL parcelshops or post offices, please note the following phrase of the DHL Paket Service Specifications (annex to the business customer contract): “The sender guarantees that he is entitled to transmit his customers’ e-mail addresses to DHL for the purpose of the parcel notification.”
* A “Google Maps API Key” is required for a complete installation. 

== Screenshots ==

1. screenshot-4.(png|jpg|jpeg|gif)
1. screenshot-1.(png|jpg|jpeg|gif)
1. screenshot-2.(png|jpg|jpeg|gif)
1. screenshot-3.(png|jpg|jpeg|gif)


== Changelog ==

= 1.3.5 =
* DHL Paket: Validation fixes
* Readme text changes

= 1.3.4 =
* DHL Parcel: Added selectable delivery times based on location
* DHL Parcel: Added an automatic switch between Same Day / Home and Evening delivery for delivery times
* DHL Parcel: Added a filter to sort orders based on estimated shipping days in the admin
* DHL Parcel: Added cutoff times for delivery times
* DHL Parcel: Added days needed for shipping for delivery times
* DHL Parcel: Added colored indicators for estimated shipping days in the admin
* DHL Parcel: Added configurable shipping days for delivery times

= 1.3.3 =
* DHL Parcel: Enabled Switzerland

= 1.3.2 =
* DHL Parcel: Additional return labels can be created alongside regular labels
* DHL Parcel: Added settings to set a default address for return labels
* DHL Parcel: Added bulk label creation and bulk label printing
* DHL Parcel: Added a setting to set the default size preference for bulk label creation
* DHL Parcel: Added the service option to hide shipping address
* DHL Parcel: Added settings to set a default address when hiding sender address

= 1.3.1 =
* Bug Fix - DHL Paket: Fix JS errors on checkout page, by validating fields exist first

= 1.3.0 =
- DHL Parcel: Added Usabilla feedback button to the plugin settings page
- DHL Parcel: Added an option to calculate free shipping after applying discounts
- DHL Parcel: Updated free shipping settings to be either free, or for discounts
- DHL Parcel: Each delivery option can now be seperately set to be eligable for free or discounted shipping
- DHL Parcel: Each delivery option has now it's own free or discounted pricing
- DHL Parcel: Enabled most shipping options available in My DHL Platform.
- DHL Parcel: ServicePoint can now be selected and changed in the admin, whether a customer has selected a ServicePoint or not
- DHL Parcel: Updated label creation interface to be in-line with My DHL Platform
- DHL Parcel: Updated ServicePoint Locator to use the unified React Component version
- DHL eCommerce: Bulk generate labels for all formats
- DHL eCommerce: Force DHL product in bulk label generation
- DHL eCommerce: Add fixed weight to package in settings
- DHL eCommerce: Set label format settings
- DHL eCommerce: Set "Incoterms" in order
- DHL eCommerce: Add COD in order
- DHL eCommerce: Add Vietnam states to WooCommerce

= 1.2.4 =
* Bug fix: Always place shipper, receiver and return "company name" first in address
* Bug fix: Only validate locations if "ship to different address" checkbox is checked

= 1.2.3 =
* Bug fix: Use 'jQuery' instead of '$' on frontend script

= 1.2.2 =
* Bug fix: Default to 'customer' order note instead of 'private'

= 1.2.1 =
* Bug fix: Shipper street number can include characters

= 1.2.0 =
* DHL Paket: New feature - Added Parcel Shop Finder for "Packstation" and "Branch", with Google map.
* DHL Paket: New feature - Preferred day and time set dynamically based on postcode
* DHL Paket: New feature - Bulk create labels in order view
* DHL Paket: New feature - Create return label option
* DHL Paket: New feature - Added "Print Only If Codeable" service
* DHL Paket: New feature - Added "Ident-Check" service
* DHL Paket: New feature - Making tracking note private setting so it does not send email to customer
* DHL Paket: Save all labels in their own folder i.e. "/wp-content/uploads/woocommerce_dhl_label"

= 1.1.2 =
* Bug fix - DHL eCommerce: Deleted products cause exception error on edit order

= 1.1.1 =
* DHL Parcel: Version number increased to load updated CSS and JS files

= 1.1.0 =
* DHL Parcel: Shipping zones added
* DHL Parcel: Checkout will now only show available shipping methods based on shopper address
* DHL Parcel: Added missing customer fields that prevented customers from receiving certain automated notifications
* DHL Parcel: Signature can be enabled to be checked by default (if available)
* DHL Parcel: Track & trace link updated to include postcode, to show full data

= 1.0.16 =
* Validation fix - Validate product exists before adding weight
* Validation fix - Validate shipping address state exists before modifying it

= 1.0.15 =
* Bug fix - DHL Parcel: Removed empty ServicePoint API calls
* Bug fix - DHL Parcel: Now properly returns a visible error when street + number cannot be parsed
* Enhancement - DHL Parcel: Added optional track & trace information to WooCommerce order completion mail

= 1.0.14 =
* Enhancement: Send order currency and price instead of shop currency and product price to support multi-currency plugins

= 1.0.13 =
* Sending "email" field via DHL Paket API to support DHL AT
* Bug fix - Exception handling for payment gateway check

= 1.0.12 =
* Bug fix - DHL Paket: Tooltip conflict with bootstrap tooltip
* Bug fix - DHL Paket: Remove * text if preferred day and time not displayed in the frontend
* Bug fix - DHL eCommerce: Fix conflict between 'PayPal Express' and plugin for DHL eCommerce merchants only
* Bug fix - DHL eCommerce: Fix settings links
* Warning fix: 'payment_method' array key warning
* Enhancement: Add weight filter 'pr_shipping_dhl_order_weight'
* Enhancement - DHL Paket: Remove 'Test Connection' button, since not accurate

= 1.0.11 =
* Bug fix - DHL Parcel: Fixed Dutch translation loading bug
* Enhancement - DHL Parcel: Added track & trace component to account page, can be enabled in settings
* Enhancement - DHL Parcel: Added postcode sensitivity fix due to change in the API validation
* Enhancement - DHL Parcel: Added the ability to debug by mail, can be enabled in settings
* Enhancement - DHL Parcel: Added WordPress application tag to labels

= 1.0.10 =
* Enhancement - Modify log messages

= 1.0.9 =
* Enhancement - DHL eCommerce: increase POST timeout to 30 seconds instead of 5

= 1.0.8 =
* Bug fix - DHL Paket: Fix special field e.g. &amp in ship address

= 1.0.7 =
* Bug fix - DHL Paket: Subscription renewal action, parameters incorrect
* Bug fix - DHL eCommerce: Delete token transient on saved settings to avoid conflict if connection type changed

= 1.0.6 =
* Enhancement - DHL Paket: Remove "preferred_none" on thank you page
* Enhancement - DHL Paket: Do not display DHL table in checkout page, if ALL preferred services are disabled
* Enhancement - DHL eCommerce: Rename interational products

= 1.0.5 =
* DHL Paket - Do not require export information for shipping within the European Union

= 1.0.4 =
* Create label metabox not displaying bug fix

= 1.0.3 =
* Bug fix - DHL Parcel: Excess resource loading caused errors and incompatibilities. Moved the loading logic to an earlier state.

= 1.0.2 =
* Bug fix - DHL Paket: Fix "streetNumber" SOAP error, by assuming that the last part of "Address 1" is the street number and sending separately
* Bug fix - DHL Paket: Allow characters in "Street Address Number" in DHL Paket settings panel 

= 1.0.1 =
* Bug fix - DHL Paket: Fix duplicate payment details in thank you page and email
* Bug fix - DHL Paket : Max items limit of 6 should only be for international shipments
* Warning fix - DHL Paket: Order details does not exist for DHL Paket
* Warning fix: If weight not numeric will throw a warning

= 1.0 =
* First public release

== Upgrade Notice ==

= 1.2 =
* New features, please upgrade

= 1.0.14 =
* Enhancements, please upgrade

= 1.0.13 =
* Bug fixes and enhancements, please upgrade

= 1.0.12 =
* Bug fixes and enhancements, please upgrade

= 1.0.11 =
* Enhancement, please upgrade

= 1.0.10 =
* Enhancement, please upgrade

= 1.0.9 =
* Enhancement, please upgrade

= 1.0.8 =
* Bug fixes, please upgrade

= 1.0.7 =
* Bug fixes, please upgrade

= 1.0.6 =
* Enhancement, please upgrade

= 1.0.5 =
* Enhancement, please upgrade

= 1.0.4 =
* Create label metabox not displaying bug fix, please upgrade

= 1.0.3 =
* Payment incompatibilities fixed for DHL Parcel users, please upgrade

= 1.0.2 =
* Bug fixes, please upgrade

= 1.0.1 =
* Bug fixes, please upgrade

= 1.0 =
* First public release

