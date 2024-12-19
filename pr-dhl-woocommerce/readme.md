=== DHL Shipping Germany for WooCommerce ===
Contributors: DHL, shadim, utzfu
Donate link:
Tags: DPDHL, DHL, DHL eCommerce, DHL Paket Germany, Shipping
Requires at least: 4.6
Requires PHP: 5.6
Tested up to: 6.7
Stable tag: 3.7.7
Requires Plugins: woocommerce
WC requires at least: 3.0
WC tested up to: 9.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automate e-commerce orders with Official DHL for WooCommerce. Covers DHL Paket and Deutsche Post International.

== Description ==

DHL’s official extension for WooCommerce on WordPress. Manage your national and international shipments easily. The “DHL for WooCommerce” – plugin is compatible with the following DHL service offerings depending on your origin country:

* DHL Paket (Germany)
* Deutsche Post International (all European countries)


The following DHL services are no longer supported by this plugin:

* DHL eCommerce Asia (TH, MY): Please use instead the following plugin [here]( https://wordpress.org/plugins/dhl-ecommerce-apac/ ).
* DHL Parcel for WooCommerce (for online stores that ship orders from the Benelux region): Please use instead the following plugin [here](https://wordpress.org/plugins/dhlpwc/).

== Features ==

1. NEW: Ship your orders with **DHL Warenpost International** in Germany.
2. Fast and easy **label creation** of your national and international orders with DHL products such as DHL Paket, Warenpost, DHL Paket International and Warenpost International
3. Automatically receive a **tracking code** for each label.
4. Use **additional delivery services** as e.g. the visual check of age available via the API of DHL Paket or Cash on delivery
5. Offer **Preferred Delivery Options** to your customers via “Wunschzustellung”. The customer has the opportunity to select a specific date for his delivery or an alternative delivery location e.g. a drop-off location or his preferred neighbour.
6. **Customization** Enable/disable or edit the names of services and set up the handling cost for each DHL shipping service.
7. Experience **premium support**, timely compatibility updates and bug fixes.
8. The “**print only if codeable**” – option you can activate in the DHL settings will check whether the address is correct or not before generating the label.
9. **Bulk Label Creation** allows you to create multiple DHL Labels at once.
10. **Return Parcel Handling** allows you to print a return label with a “return address” so your customer can return the shipment easily.


== Availability by countries and prerequisites ==

Based on your sender country and shipping preference, different access credentials for **DHL Paket, DHL Parcel NL and Deutsche Post International** are required for the configuration:

**DHL Paket for Germany**: Log in with your business customer portal credentials. (not a customer yet? Click [here](https://www.dhl.de/dhl-kundewerden?source=woocommerce&cid=c_dhloka_de_woocommerce) for **DHL Paket**)

**Deutsche Post International for Europe**: ask your sales contact for credentials for this plugin. (not a customer yet? Click [here](https://www.deutschepost.com/en/business-customers/contact/email.html)).

== Installation & Configuration ==

1. Upload the downloaded plugin files to your `/wp-content/plugins/DHL-for-WooCommerce` directory, **OR** install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to WooCommerce–>Settings->Shipping and select the upper DHL unit (depending on your home country this is DHL Paket, DHL (Parcel) for WooCommerce or Deutsche Post) to configure the plugin.

...for **DHL Paket (Germany)**: you need your EKP number (10 digits) and add the participation numbers (2 digits) to the respective products available (you will find the participation numbers in the DHL business customer portal).
...for **Deutsche Post International**: you need your customer account number (EKP) and API credentials.


== Support ==

More detailed instructions on how to set up your store and configure it are consolidated on on the page [here](https://github.com/shadimanna/dhl-logistic-services-for-woocommerce/wiki/Documentation)

== Additional Information ==
* A “Google Maps API Key” is required if you wish to display DHL locations on a map for your customers.

== Screenshots ==

1. screenshot-4.(png|jpg|jpeg|gif)
1. screenshot-1.(png|jpg|jpeg|gif)
1. screenshot-2.(png|jpg|jpeg|gif)
1. screenshot-3.(png|jpg|jpeg|gif)


== Changelog ==

= 3.7.7 =
* DHL Paket: Fix Customs Postal Charges value.

= 3.7.6 =
* DHL Paket: Fix "File cannot be saved" using SOAP API.

= 3.7.5 =
* DHL Paket: Fix pickup request for bulky goods.

= 3.7.4 =
* WordPress 6.7 compatibility.
* Update TCPDF library.
* Security updates.A

= 3.7.3 =
* DHL Paket: Fix Pickup locations

= 3.7.2 =
* DHL Paket: Fix Identity check DOB default value.
* DHL Paket: Update REST-API for Pickup request.

= 3.7.1 =
* DHL Paket: Fix PHP warnings.
* DHL Paket: Fix unhandled error if account settings is invalid.

= 3.7.0 =
* Deutsche Post: Fix an error while bulk create labels.

= 3.6.9 =
* Deutsche Post: Fix validation error when using comma as a decimal separator.

= 3.6.8 =
* DHL Paket: Fix Named Person Service / Rest-API.
* Fix fatal error when the store base is not supported.

= 3.6.7 =
* DHL Paket: Fix order weight decimal point / Rest-API.

= 3.6.6 =
* DHL Paket: Fix CN23 document includes refunded order items.

= 3.6.5 =
* WordPress 6.5 compatibility.
* DHL Paket: Fix Get Account Settings - Error 403

= 3.6.4 =
* DHL Paket: Fix REST API customs doc merged with label
* DHL Paket: Fix "Endorsement" warning

= 3.6.3 =
* DHL Paket: Fix HPOS compatibility with bulk create labels

= 3.6.2 =
* DHL Paket: Settings is empty on WooCommerce version 8.4.0

= 3.6.1 =
* DHL Paket: Fix MyAccount files crash due to namespacing

= 3.6.0 =
* DHL Paket: Implement MyAccount API to fetch EKP, participation settings and password expiration.
* DHL Paket: Add PDDP for Switzerland

= 3.5.9 =
* DHL Paket: Fix bulk label creation "total_package" error in REST API
* DHL Paket: Fix warning for "woocommerce_subscriptions_renewal_order_meta_query"

= 3.5.8 =
* DHL Paket: Add Company Name is destination address label
* DHL Paket: Fix bulk merge issue when label previously exists

= 3.5.7 =
* DHL Paket: Fix shipment weight for Rest-API.

= 3.5.6 =
* DHL Paket: Use DHL sandbox credentials for Rest-API.

= 3.5.5 =
* DHL Paket: Fix Parcel-DE Rest-API production link.

= 3.5.4 =
* Fix Multisite compatibility.

= 3.5.3 =
* Fix download label button for orders with already created labels.

= 3.5.2 =
* Deutsche Post : Fix order list fetal error

= 3.5.1 =
* Fix WC versions compatibility

= 3.5.0 =
* Add Signature service
* Fix PHP warnings

= 3.4.6 =
* Fix fatal error in some environments
* Update business center url

= 3.4.5 =
* Fix AWBS bulk labels creation

= 3.4.4 =
* Round customs item weight to 4 digits

= 3.4.3 =
* Fix white screen in CPT bulk action

= 3.4.2 =
* Support for upcoming HPOS changes in WooCommerce

= 3.4.1 =
* DHL Paket:  Added Postal Delivered Duty Paid (PDDP) service for Norway

= 3.4.0 =
* DHL Paket:  Support for Shipment REST API

= 3.3.0 =
* DHL Paket:  Add endorsement service
* DHL Paket:  WooCommerce Subscriptions plugin compatibility
* DHL Paket:  Fix FPDF bug

= 3.2.2 =
* DHL Paket:  Bug fix - If FPDF used in other plugins

= 3.2.1 =
* DHL Paket:  Fix location finder dropdown

= 3.2.0 =
* DHL Paket: Add bulk delete labels on orders page
* DHL Paket: Fix - Disable bulk button after pressed to avoid duplicate label creation.
* DHL Paket: Fix - Additional setting weight after WC decimal modification

= 3.1.1 =
* DHL Parcel: Removed DHL Parcel Benelux
* DHL Parcel: Added a notification for DHL Parcel Benelux users

= 3.1.0 =
* DHL Paket: Added Closest drop-point delivery (CDP) service

= 3.0.1 =
* DHL Parcel: Updated label downloads to now serve from the temporary folder instead of the public folder for additional security and storage usage

= 3.0.0 =
* DHL Paket: Added Postal Delivered Duty Paid (PDDP) service
* DHL Paket: Added support for EU exceptions that require customs e.g. Canary Islands
* DHL Paket: Fix - allow empty street number outside of Germany
* DHL Paket: Fix - round error message
* DHL Paket: Fix - PHP 8.0 error for private function that should be public
* DHL Paket: Fix - add back variant name in product description

= 2.9.3 =
* DHL Parcel: Added a new service option: Secure delivery by code
* DHL Parcel: Added an option to show Same Day Delivery as a separate shipping method when delivery times are enabled

= 2.9.2 =
* DHL Parcel: Added a setting to automatically enable the service: send a message to recipient
* DHL Parcel: Improved street and housenumber parsing
* DHL Parcel: Fixed an issue with price rules in shipping zones not using the right delimiter settings
* DHL Parcel: Tested for WordPress 6.0

= 2.9.1 =
* DHL Paket: Modify plugin title

= 2.9.0 =
* DHL Paket: Add wizard modal box for the first install only.
* DHL Paket: Organize the settings UI section into tabs.
* DHL Paket: Organize the order label UI into logical sections.
* DHL Paket: Add support for "name3" in the API.

= 2.8.16 =
* DHL Paket: Fix decimal field does not follow general decimal setting.
* DHL Paket: Bump the tested up to.
* DHL Paket: Change default export type to Sale of goods.
* DHL Paket: Return cat/tags of parent for export description.
* DHL Paket: Add setting for Tracking url language.

= 2.8.15 =
* DHL Paket: Fix for countries with no zip format. Error creating a label.
* DHL Paket: Fix the displayed warning in the empty row in order table.
* DHL Paket: Update Readme description.

= 2.8.14 =
* DHL Parcel: Updated the migration notice when to change colors

= 2.8.13 =
* DHL Paket: Modify account creation link
* DPI: Modify Production API URL
* DPI: Ensure AWB always returns PDF content type

= 2.8.12 =
* DHL Paket: Fix Shipper Address, House Number not allowing a letter
* DHL Paket: Fix Default Premium setting when creating international labels in bulk

= 2.8.11 =
* DHL Parcel: Updated tracking url for Belgium
* DHL Parcel: Updated product-based automatic mailbox selection to work with areas where mailbox delivery is not available when used with bulk actions
* DHL Parcel: Fixed an issue where product-based automatic mailbox selection is being applied to sequential orders when used in a bulk action

= 2.8.10 =
* DHL Parcel: Fixed a bug with pages not loading when DHL for WooCommerce and DHL Parcel for WooCommerce are both activated

= 2.8.9 =
* DHL Parcel: Updated migration notice text
* DHL Parcel: Migration notice made translatable

= 2.8.8 =
* DHL Parcel: Added a migration notification for current users
* DHL Parcel: Updated listing tags

= 2.8.7 =
* DHL Paket: Add order currency in customs export
* DHL Paket: Fix pickup address matching issue

= 2.8.6 =
* DHL Parcel: Added additional sanitization, escapes and validation
* DHL Parcel: Updated readme
* DHL Parcel: Standalone release

= 2.8.5 =
* DHL Paket: Add DHL COD setting to set which payment gateway is COD
* DHL Paket: Add "Package Description" setting to set customs description based on product name, category or tag.
* DHL Paket: Add WP Filter hook to modify "Export Type".
* DHL Paket: Fix "undefined" text in Location Finder modal.

= 2.8.4 =
* Revert plugin naming and description

= 2.8.3 =
* DHL Parcel: Fixed a deprecation warning on sorted package sized in the label creation screen for PHP 8 compatibility
* DHL Parcel: Fixed a reference warning in the label creation screen for PHP 8 compatibility

= 2.8.2 =
* DHL Paket: Fix drop-off points. Switched to Unified REST API for drop-off points.
* DHL Paket: Remove PDFMerge unnecessary fonts
* DHL Paket: "Delivery Day" text fixes

= 2.8.1 =
* DHL Parcel: Added a new available action hook for label creation

= 2.8.0 =
* DHL Paket: Updated PDFMerger to be compatible with PHP 8.0
* DHL Paket: Add Warenpost International product
* DHL Paket: Bug fix - Ensure multiple return shipment ids are display
* DHL Paket: Shorten packet field names to avoid long query string with WC order search
* DHL Paket: Verify "WC()->payment_gateways" exists, to avoid calling on null
* DHL Paket: Fix Apple Pay conflict with "address type" field
* DHL eCS Asia: Remove eCS Asia from the plugin

= 2.7.6 =
* DHL Paket: Unify Pickup API call when selecting multiple orders to be picked up
* DHL Paket: Add new label formats to settings
* DHL Paket: Fix payment gateway conflict issue with frontend services

= 2.7.5 =
* DHL Parcel: Small fix for ServicePoint locator in checkout
  = 2.7.4 =
* DHL Paket: Add new preferred field label German translation

= 2.7.3 =
* DHL Paket: Add "shipmentNumber" to Pickup API call
* DHL Paket: Add Warenpost label size - 100x70mm
* DHL Paket: Change preferred field labels
* DHL Paket: Add link to modify DHL Notification Emails in settings
* DHL Paket: Remove limitation of 6 items per order
* DHL Paket: Fix weight 0 throwing error when using multiple packages option

= 2.7.2 =
* DHL Parcel: Fixed an issue with reference values not loading correctly

= 2.7.1 =
* DHL Parcel: Changed evening delivery times text to 17.30 - 22:00
* DHL Parcel: Add order number in REFERENCE2 and add a filter to change it programmatically

= 2.7.0 =
* DHL Paket: Add DHL Pickup request option in order bulk actions

= 2.6.2 =
* DHL Parcel: Added ServicePoint information for order completion mail

= 2.6.1 =
* DHL eCommerce Asia: Add required tax fields for European shipment destinations
* DHL eCommerce Asia: Update DHL product list per country

= 2.6.0 =
* DHL Paket: Added "DHL Label Created" and "DHL Tracking Number" columns in order list
* DHL Paket: Allow "Post Number" to be editable in the admin edit order page
* DHL Paket: Added return ID number to the order note
* DHL Paket: Fix issue with bulk create label when "Create Return Label default" setting checked
* DHL Paket: Fix issue so the plugin is translatable on translate.wordpress.org

= 2.5.13 =
* DHL Parcel: Added a setting to set additional shipping fees for specific products
* DHL Parcel: Added support for additional order status
* DHL Parcel: Added automatic label creation and printing
* DHL Parcel: Updated developer settings text to avoid confusion

= 2.5.12 =
* DHL eCS: Fixed phone field being empty, due to new shipping phone field in WC

= 2.5.11 =
* DHL Parcel: Fixed autoloader for PHP8

= 2.5.10 =
* DHL Paket: Fix tracking link

= 2.5.9 =
* DHL Parcel: Fixed an issue where decimals were not calculated correctly in conditional rules for delivery options

= 2.5.8 =
* DHL Parcel: Added a new bulk setting to print all labels with Same Day Delivery
* DHL Parcel: Added a product setting to automatically use the parcel type mailbox based on conditions
* DHL Parcel: Added a setting to display free shipping in different ways
* DHL Parcel: Added snippet information to the settings interface for custom order numbers
* DHL Parcel: Updated translation
* DHL Parcel: Fixed an issue where ServicePoint Locator isn’t loaded depending on shipping zones

= 2.5.7 =
* DHL: Add compatibility with Microsoft Server for label creation
* DHL Paket: Skip virtual variation products
* DHL Paket: Add POT file for translation
* DHL Paket: Fix weight error issue
* DHL eCS Asia: Add additional DHL International services

= 2.5.6 =
* DHL Parcel: Use multi-label API instead of PDFMerger
* DHL Parcel: Add filter for reference

= 2.5.5 =
* DHL Paket: Skip sending virtual ordered item (products) to DHL

= 2.5.4 =
* DHL Paket: Change business portal sign up link

= 2.5.3 =
* DHL Parcel: Updated to use the newest version of the ServicePoint Locator
* DHL Parcel: Improved automatic search of the closest ServicePoint to filter on last mile
* DHL Parcel: Added filters to bulk redirects for developers to customize
* DHL Parcel: Fixed an issue where conditionally disabled shipping methods were not applied to delivery times

= 2.5.2 =
* DHL Paket: Fix, if "Send Customer Email" setting is set to "Terms & Conditions", send DHL notification email
* DHL Paket: Disable tracking pixel on frontend

= 2.5.1 =
* Deutsche Post: Fix, if an ordered item value is 0, set product price to avoid error

= 2.5 =
* DHL eCommerce Asia: Add Closeout manifest bulk action
* DHL Paket: Add Sandbox mode to the plugin
* DHL Paket: Add services outside Germany; Additional insurance, Premium, Bulk Goods, Email Notification
* DHL Paket: Fix shipment reference and multiple packages bug

= 2.4.4 =
* DHL Paket: Remove 'GB' from EU countries, to force customs fields to display

= 2.4.3 =
* DHL Paket: Fix 'streetName' soap error for Postfiliale address

= 2.4.2 =
* DHL Paket: Fix 'streetName' soap error for Packstation address

= 2.4.1 =
* DHL Paket: Fix array illegal offset issue i.e. 'tracking_number'

= 2.4 =
* DHL Paket: Add support for multiple packages per order
* DHL Paket: Add settings for sending email and phone to DHL
* DHL Paket: Add invoice field to be added on customs forms for cross border packages
* DHL Paket: Add product bulk edit for HS code & Manufacturer
* DHL Paket: Fix zip code bug being required e.g. Hong Kong
* DHL Paket: Add validation on shipper reference field when logo setting is checked
* DHL Paket: Flush rewrite rules to avoid manually doing it
* Deutsche Post: Add Sender and Importer customs reference fields
* Deutsche Post: Round grams to nearest integer

= 2.3 =
* Deutsche Post: Add "Paket Priority"
* Deutsche Post: Add AWB copy count option
* Deutsche Post: Add product options; country of origin, HS code and customs description
* Deutsche Post: Fixed non-EU labels merging with EU labels
* Deutsche Post: Fixed “contentpiecevalue” to include quantity

= 2.2.10 =
* DHL Parcel: Fixed an issue where the DPI module was not loading on certain pages

= 2.2.9 =
* DHL Parcel: Fixed an issue with certain settings not being able to save with certain themes

= 2.2.8 =
* DHL: Fix registration email issue, by confirming is WC_Order
* DHL Paket: Fix wrong key "dhl_default_identcheck_dob"

= 2.2.7 =
* DHL Paket: Fix exclusion of transfer days issue in German language

= 2.2.6 =
* DHL Paket: Added {pr_dhl_tracking_note} replace placeholder for WC emails. This adds the tracking note within an email.
* DHL Paket: Added [pr_dhl_tracking_note order_id="12345"] shortcode to display tracking note info for a given order id.
* DHL Paket: Added [pr_dhl_tracking_link order_id="12345"] shortcode to display tracking link for a given order id.
* DHL Paket: Add spacing for tracking note display.
* DHL Paket: Ensure only shop managers can download label.
* DHL Paket: Modify label format names
* DHL Paket: Default label format to 910-300-700 (Laser printer 105 x 205 mm)

= 2.2.5 =
* DHL Parcel: Added a fallback notice for switching between DHL Parcel and Deutsche Post International

= 2.2.4 =
* DHL Parcel: Improved mailpost bulk processing

= 2.2.3 =
* DHL Parcel: Updated Mopinion code to include language setting
* DHL Parcel: Removed shortcode for tracking information to prevent errors

= 2.2.2 =
* DHL Parcel: Added a shortcode for tracking information
* DHL Parcel: Added additional meta data of preferred delivery date for third party exports
* DHL Parcel: Improved PDFMerger loading
* DHL Parcel: Fixed an issue with multiple warnings showing in admin
* DHL Parcel: Updated feedback system from Usabilla to Mopinion

= 2.2.1 =
* DHL Parcel: Fixed an issue with warnings when loading settings

= 2.2.0 =
* DHL Parcel: Enabled Austria as shipping country
* DHL Parcel: Added a setting to show the selected ServicePoint information in e-mails
* DHL Parcel: Added requirements and visual indicators to enable Same Day delivery so it works without enabling delivery times
* DHL Parcel: Added additional meta data for third party exports
* DHL Parcel: Added a dynamic notification to switch between Deutsche Post International and DHL Parcel
* DHL Parcel: Updated address parsing to support addresses starting with numbers
* DHL Parcel: Fixed an issue where limiting DHL methods didn't work if none were selected
* DHL Parcel: Fixed correct printer responses being sent as error reports
* DHL Parcel: Fixed an issue with non-default price decimals not being handled correctly
* DHL Parcel: Removed sending error reports when credentials not configured and still empty

= 2.1.0 =
* DHL Paket: Add "Warenpost" DHL product
* DHL Paket: Enable logo addition via customer portal "Shipper Reference" setting
* DHL Paket: Add label format setting
* DHL Paket: Add setting to automatically generate the label on a specific order status
* DHL Paket: Add WordPress filter to relocate email notifcation on checkout, called "pr_shipping_dhl_email_notification_position"
* DHL Paket: Bug fix when address number is at the beginning in "address 1" for addresses outside of Germany
* DHL Paket: Bug fix when tracking link is empty do not add any text to email

= 2.0.0 =
* DHL eCommerce: New API integration for Asia, specifically; SG, HK, TH, CN, MY, VN, AU, IN
* DHL eCommerce: Include support for value added services; COD, Insurance and OBOX.
* DHL eCommerce: Remove old API integration for Asia and North America
* Deutsche Post: Suppress Waybill email to end client

= 1.7.0 =
* DHL Paket: Remove "Preferred Time" service
* DHL Paket: Remove "DHL Paket Taggleich" product

= 1.6.9 =
* DHL Parcel: Updated ServicePoint selector width to scale to full width
* DHL Parcel: Updated ServicePoint selector to block the enter key on input to prevent accidental form submission
* DHL Parcel: Fixed an issue that caused PHP warning errors on pages with cart data
* DHL Parcel: Updated translation texts

= 1.6.8 =
* DHL Paket: Fix label creation error message to display correctly

= 1.6.7 =
* DHL Parcel: Added a postnumber input pop-up for Packstations that require it with the mapless locator
* DHL Parcel: Fixed an issue with logged in users not seeing shipping methods in the checkout

= 1.6.6 =
* Deutsche Post: Fix item level value formatting
* Bug Fix: WC 4.0 compatibility with "Test Connection" setting

= 1.6.5 =
* Deutsche Post: Add merchant phone number for "Express" customers
* Deutsche Post: Modify settings descriptions
* Update "WC tested up to" "4.0"

= 1.6.4 =
* DHL Parcel: Added an error message when trying to create a label without country information
* DHL Parcel: Added download and print button after creating labels in bulk
* DHL Parcel: Added customizable track & trace text
* DHL Parcel: Added a fallback ServicePoint selector when no Google Maps key is provided
* DHL Parcel: Added secondary reference service
* DHL Parcel: Updated number parsing from address data
* DHL Parcel: Updated error responses to include detail information
* DHL Parcel: Updated evening detection for more dynamic delivery times
* DHL Parcel: Fixed German packStations being selected by default
* DHL Parcel: Fixed an issue with same day delivery when combined with delivery times
* DHL Parcel: Removed cash on delivery service

= 1.6.3 =
* Deutsche Post: Addition of the “Contents Type” required for international orders outside of EU.
* Deutsche Post: Addition of yellow brand color on order metaboxes.
* Deutsche Post: Small changes in the text on the order metaboxes.
* Deutsche Post: Sanitization of fields to the API to ensure they comply with the API specs.
* Deutsche Post: Pass product id instead of product SKU to API.

= 1.6.2 =
* DHL Paket: Fix conflict with PDFMerger libraries

= 1.6.1 =
* DHL Paket: Bug fix - If PDFMerge does not exist for cross-border label return without customs docs
* DHL Paket: Bug fix - Verify setting exists to not cause errors
* DHL Paket: Bug fix - Add new version in SOAP call

= 1.6.0 =
* DHL Paket: Paket SOAP API v3.0 update
* DHL Paket: Added Parcel Outlet Routing
* DHL Paket: Added default setting for each service
* DHL Paket: Added "Email Notification" setting to enable user optin on the checkout page
* DHL Paket: Added Google Maps enable/disable option on frontend
* DHL Paket: Added setting to set an order to "Completed" once a label is generated
* DHL Paket: Added setting to add tracking information in "Completed" email
* DHL Paket: Added setting for additional weight
* DHL Paket: Added hook to support "Advanced Shipment Tracking for WooCommerce" plugin
* DHL Paket: Added order id as reference in the label
* DHL Paket: Added text to preferred day and time if no options returned from the API
* DHL Paket: Bug fix - added shipping fees to customs info
* DHL Paket: Bug fix - COD outside Germany
* DHL Paket: Bug fix - bulk label causing 500 error when hundreds of orders selected
* DHL Paket: Bug fix - created "FPDF" loader to avoid conflict with other plugins loading same library
* DHL Paket: Bug fix - "pr_shipping_dhl_label_created" being called incorrectly


= 1.5.8 =
* DHL Parcel:  Restored street number validation on addresses based on feedback
* DHL Parcel:  Added a setting to turn off street number validation (by default on)
* DHL Parcel:  Updated delivery times to show evening times based on starting time 17:00 and higher

= 1.5.7 =
- DHL Parcel: Added a setting to change order status after label creation
- DHL Parcel: Fixed an issue with the settings menu jumping on certain browsers
- DHL Parcel: Fixed an issue with unavailable service combinations on bulk creation
- DHL Parcel: Removed number validation on addresses due to some addresses not requiring it

= 1.5.6 =
* DHL Parcel: Fixed issue with package rate not being properly calculated based on logged in users
* DHL Parcel: Added product based shipping restrictions
* DHL Parcel: Fixed an issue with shipping time windows

= 1.5.5 =
* DHL Parcel: Fixed custom shipping methods not sorting after logging in
* DHL Parcel: Fixed an issue with certain sites not saving the settings
* DHL Parcel: Fixed an issue causing a warning error

= 1.5.4 =
* DHL Paket: Add filters to override DHL products

= 1.5.3 =
* DHL Paket: Add filter to override base country

= 1.5.2 =
* DHL Parcel: Updated delivery times to correctly calculate with timezone settings
* DHL Parcel: Packstation postnumber input limited to DE
* DHL Parcel: Fixed sort position setting not working for shipping zones
* DHL Parcel: Fixed an issue where orders weren't linked with DHL order data

= 1.5.1 =
* Disabled Deutsche Post International (DPI) for DHL Parcel countries

= 1.5 =
* Add Deutsche Post International (DPI) for European countries
* DHL Paket: Austria is no longer supported by DHL Paket, added to DPI

= 1.4.3 =
* DHL Parcel: Added the age check 18+ service
* DHL Parcel: Updated Packstation code input text
* DHL Parcel: Updated structure of settings with a new tab for label settings
* DHL Parcel: Updated Google Maps text for more clarification
* DHL Parcel: Removed input box for delivery times in the checkout
* DHL Parcel: Added developer methods to update shipment requests

= 1.4.2 =
* DHL Paket: Verify buffer exists before emptying it
* DHL eCommerce: Remove "global" variable that overrides US states

= 1.4.1 =
* DHL Paket: Switch from "ob_get_clean" to "ob_clean" since latter does not close the buffer.

= 1.4.0 =
* DHL Parcel: Added support for Direct Label Printing
* DHL Parcel: Added setting for maximum number of days shown for delivery times
* DHL Parcel: Updated bulk settings to also work with the open in new window setting
* DHL Parcel: Updated setting for combined labels to display page options only (A4)
* DHL Parcel: Updated translations to no longer use system codes
* DHL Parcel: Updated country check in the plugin
* DHL Parcel: Updated ServicePoint locator to support Packstation code input
* DHL Parcel: Updated code for increased compatibility with WooCommerce 2.6 (or higher)
* DHL Parcel: Removed placeholder Google Maps key

= 1.3.19 =
* DHL Paket: Fix corrupted file when clicking "Download Label" in edit order, needed to flush output buffer.

= 1.3.18 =
* DHL Parcel: Fixed delivery times not loading for newest WooCommerce release
* DHL Parcel: Fixed an issue where postal code is case sensitive

= 1.3.17 =
* DHL Parcel: Fixed pricing filters rounding prices

= 1.3.16 =
* DHL Parcel: Added pricing filters for weight and cart totals
* DHL Parcel: Added multiple labels per page option for bulk printing
* DHL Parcel: Added an addition field for addresses for address additions after the street and number
* DHL Parcel: Fixed an issue for addresses starting with numbers first
* DHL Parcel: Fixed ServicePoint not always searching for the selected country
* DHL Parcel: Fixed an issue where return labels had incorrect hide shipper information

= 1.3.15 =
* DHL Paket: Add tracking setting, to enable/disable services tracking on the frontend

= 1.3.14 =
* DHL Parcel: Updated the ServicePoint locator to load from DHL's own servers instead of third party
* DHL Parcel: Updated the ServicePoint locator to select the closest ServicePoint automatically
* DHL Parcel: Added developer filters for price manipulation
* DHL Parcel: Fixed delivery times API call to not send data when no postal code is set
* DHL Parcel: Fixed tax adjustment calculation

= 1.3.13 =
* DHL Parcel: Added missing files

= 1.3.12 =
* DHL Parcel: Fixed automatic order id reference not being added for bulk
* DHL Parcel: Fixed ServicePoint locator not loading
* DHL Parcel: Added developer hooks to customise templates

= 1.3.11 =
* DHL Paket: Ensure "address 2" is never empty

= 1.3.10 =
* DHL Parcel: Updated feedback information to be multilingual
* DHL Parcel: Shipping methods can be sorted by default, price or custom order
* DHL Parcel: Added a setting to automatically create return labels
* DHL Parcel: Added a setting to automatically add order numbers as reference
* DHL Parcel: Updated the API endpoint for label creation
* DHL Parcel: Updated translations
* DHL Parcel: Fixed close button not showing on certain websites

= 1.3.9 =
* DHL Paket: Add CN23/CP71 forms to returned shipping label
* DHL Paket: Ensure receiver "streetNumber" is a numeric value
* DHL eCommerce: Fix "declaredValue" to include product discounts

= 1.3.8 =
* DHL eCommerce: Fix sub string to use 'UTF-8' for Asian chars
* DHL Paket: Fix delete meta data before API call

= 1.3.8 =
* DHL eCommerce: Fix sub string to use 'UTF-8' for Asian chars
* DHL Paket: Fix delete meta data before API call

= 1.3.7 =
* DHL Parcel: Fixed an issue with delivery times not always loading in the right order
* DHL Parcel: Fixed an issue that causes Customizer not to load on specific themes

= 1.3.6 =
* DHL Parcel: Updated bulk label creation from 1 type to each type enable-able separately
* DHL Parcel: Added mailbox option for bulk label creation
* DHL Parcel: Added optional fields to replace shipping text in the checkout
* DHL Parcel: Added Same Day, No Neighbour shipping for checkout
* DHL Parcel: Added Evening, No Neighbour shipping for checkout
* DHL Parcel: Added delivery times for No Neighbour shipping methods
* DHL Parcel: Fixed a compatibility issue with third party plugins

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
* DHL Parcel: Added Usabilla feedback button to the plugin settings page
* DHL Parcel: Added an option to calculate free shipping after applying discounts
* DHL Parcel: Updated free shipping settings to be either free, or for discounts
* DHL Parcel: Each delivery option can now be seperately set to be eligable for free or discounted shipping
* DHL Parcel: Each delivery option has now it's own free or discounted pricing
* DHL Parcel: Enabled most shipping options available in My DHL Platform.
* DHL Parcel: ServicePoint can now be selected and changed in the admin, whether a customer has selected a ServicePoint or not
* DHL Parcel: Updated label creation interface to be in-line with My DHL Platform
* DHL Parcel: Updated ServicePoint Locator to use the unified React Component version
* DHL eCommerce: Bulk generate labels for all formats
* DHL eCommerce: Force DHL product in bulk label generation
* DHL eCommerce: Add fixed weight to package in settings
* DHL eCommerce: Set label format settings
* DHL eCommerce: Set "Incoterms" in order
* DHL eCommerce: Add COD in order
* DHL eCommerce: Add Vietnam states to WooCommerce

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
