=== DHL Shipping Germany for WooCommerce ===
Contributors: DHL, shadim, utzfu
Donate link:
Tags: DPDHL, DHL, DHL eCommerce, DHL Paket Germany, Shipping
Requires at least: 4.6
Requires PHP: 5.6
Tested up to: 6.6
Stable tag: 3.7.3
Requires Plugins: woocommerce
WC requires at least: 3.0
WC tested up to: 9.3
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

= 3.7.3 =
* DHL Paket: Fix Pickup locations.

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

= 3.3.0 =
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


[See changelog for all versions](https://github.com/shadimanna/dhl-logistic-services-for-woocommerce/blob/master/pr-dhl-woocommerce/readme.md).