=== DHL Parcel for WooCommerce ===
Contributors:         dhlparcel, dhlsupport, shindhl
Tags:                 DHL, DHL Parcel, DHL Parcel for WooCommerce, DHLParcel, DHL Parcel NL, DHL Parcel Benelux, WooCommerce, Shipping, Shipping labels, Shipping rates
Requires at least:    4.7.16
Requires PHP:         5.6
Tested up to:         5.9
Stable tag:           2.0.6
WC requires at least: 3.0.0
WC tested up to:      5.3.0
License:              GPL v3 or later
License URI:          https://www.gnu.org/licenses/gpl-3.0.html

DHL Parcel (Benelux) presents: The official DHL Parcel for WooCommerce plugin to automate your e-commerce shipping process.

== Description ==

Use the free official DHL Parcel for WooCommerce plugin to professionalize your online store frontstage and behind the scenes. Manage your shipments easily. Offer visitors a unique experience by tailoring services to your business. Whether you process orders individually or a hundred at a time: you always have the right labels ready as soon as you want them.

*Please note that this plug-in can be used by online stores that ship orders from the Benelux region.*

= Benefits & Features =

1. Fast and easy **label creation** of your orders. Within the Netherlands, Belgium and Luxembourg and into Europe.
1. You can easily generate and print **multiple labels** at once from the order screen.
1. Let customers select their preferred **shipping method**.
1. Use **additional delivery services** such as the 18+ check, insured shipping, signature on receipt and different return address.
1. Set **delivery times** so that customers can select a **specific time slot** in which their products will be delivered (only available for customers in the Netherlands).
1. Show **DHL ServicePoints** in the checkout of your online store so that customers can select a pickup point nearby.
1. Automatically receive a **tracking code** for each label.
1. Create a **return label automatically** for every shipment.
1. Charge **variable shipping costs** for recipients in different countries by using Shipping Zones.
1. Set rules for **variable shipping costs**. For instance: increase shipping costs automatically for heavier products or make **shipping cheaper from a number of items** in the shopping cart.
1. Offer your customers **free or discounted delivery**.
1. Choose from various **automation rules** to ship faster than ever.
1. Experience **premium support**, timely compatibility updates and bug fixes.

This plugin has certified compatibility with WMPL that enables you to leverage multilingual capabilities. Click [here](https://wpml.org/plugin/dhl-for-woocommerce/) for further information.

= Availability by countries and prerequisites =

This plug-in can be used by online stores that ship orders from the Benelux region into Europe.

**DHL Parcel for Benelux:** Please self-generate your API credentials with your business customer portal account.

***Not a customer yet?***

* Dutch customers: [Self-onboard](https://www.dhlparcel.nl/en/business/start-shipping-immediately) yourselves as a business customer of DHL Parcel Netherlands in a jiffy. You will directly receive an activation email to start shipping immediately.
* Belgium customers: Ask for a [quote](https://www.dhlparcel.be/en/business/request-quote) if you ship from Belgium or Luxembourg.

= Additional information =

A “Google Maps API Key” is required if you wish to display DHL locations on a map for your customers.

== Changelog ==
 
= 2.0.5 =
- Updated the migration notice when to change colors
 
= 2.0.4 =
- Updated tracking url for Belgium
- Updated product-based automatic mailbox selection to work with areas where mailbox delivery is not available when used with bulk actions
- Fixed an issue where product-based automatic mailbox selection is being applied to sequential orders when used in a bulk action
 
= 2.0.3 =
- Fixed a bug with pages not loading when DHL for WooCommerce and DHL Parcel for WooCommerce are both activated
 
= 2.0.2 =
- Updated migration notice text
- Migration notice made translatable
 
= 2.0.1 =
- Added a migration notification for current users
- Updated listing tags

= 2.0.0 =
* Added additional sanitization, escapes and validation
* Updated readme
* Standalone release

= 1.3.19 =
* Fixed a deprecation warning on sorted package sized in the label creation screen for PHP 8 compatibility
* Fixed a reference warning in the label creation screen for PHP 8 compatibility

= 1.3.18 =
* Added a new available action hook for label creation

= 1.3.17 =
* Small fix for ServicePoint locator in checkout

= 1.3.16 =
* Fixed an issue with reference values not loading correctly

= 1.3.15 =
* Changed evening delivery times text to 17.30 - 22:00
* Add order number in REFERENCE2 and add a filter to change it programmatically

= 1.3.14 =
* Added ServicePoint information for order completion mail

= 1.3.13 =
* Added a setting to set additional shipping fees for specific products
* Added support for additional order status
* Added automatic label creation and printing
* Updated developer settings text to avoid confusion

= 1.3.12 =
* Fixed autoloader for PHP8

= 1.3.11 =
* Fixed an issue where decimals were not calculated correctly in conditional rules for delivery options

= 1.3.10 =
* Added a new bulk setting to print all labels with Same Day Delivery
* Added a product setting to automatically use the parcel type mailbox based on conditions
* Added a setting to display free shipping in different ways
* Added snippet information to the settings interface for custom order numbers
* Updated translation
* Fixed an issue where ServicePoint Locator isn’t loaded depending on shipping zones

= 1.3.9 =
* Use multi-label API instead of PDFMerger
* Add filter for reference

= 1.3.8 =
* Updated to use the newest version of the ServicePoint Locator
* Improved automatic search of the closest ServicePoint to filter on last mile
* Added filters to bulk redirects for developers to customize
* Fixed an issue where conditionally disabled shipping methods were not applied to delivery times

= 1.3.7 =
* Fixed an issue where the DPI module was not loading on certain pages

= 1.3.6 =
* Fixed an issue with certain settings not being able to save with certain themes

= 1.3.5 =
* Added a fallback notice for switching between DHL Parcel and Deutsche Post International

= 1.3.4 =
* Improved mailpost bulk processing

= 1.3.2 =
* Updated Mopinion code to include language setting
* Removed shortcode for tracking information to prevent errors

= 1.3.1 =
* Added a shortcode for tracking information
* Added additional meta data of preferred delivery date for third party exports
* Improved PDFMerger loading
* Fixed an issue with multiple warnings showing in admin
* Updated feedback system from Usabilla to Mopinion

= 1.3.0 =
* Enabled Austria as shipping country
* Added a setting to show the selected ServicePoint information in e-mails
* Added requirements and visual indicators to enable Same Day delivery so it works without enabling delivery times
* Added additional meta data for third party exports
* Added a dynamic notification to switch between Deutsche Post International and DHL Parcel
* Updated address parsing to support addresses starting with numbers
* Fixed an issue where limiting DHL methods didn't work if none were selected
* Fixed correct printer responses being sent as error reports
* Fixed an issue with non-default price decimals not being handled correctly
* Removed sending error reports when credentials not configured and still empty

= 1.2.19 =
* Updated ServicePoint selector width to scale to full width
* Updated ServicePoint selector to block the enter key on input to prevent accidental form submission
* Fixed an issue that caused PHP warning errors on pages with cart data
* Updated translation texts

= 1.2.18 =
* Added a postnumber input pop-up for Packstations that require it with the mapless locator
* Fixed an issue with logged in users not seeing shipping methods in the checkout

= 1.2.17 =
* Added an error message when trying to create a label without country information
* Added download and print button after creating labels in bulk
* Added customizable track & trace text
* Added a fallback ServicePoint selector when no Google Maps key is provided
* Added secondary reference service
* Updated number parsing from address data
* Updated error responses to include detail information
* Updated evening detection for more dynamic delivery times
* Fixed German packStations being selected by default
* Fixed an issue with same day delivery when combined with delivery times
* Removed cash on delivery service

= 1.2.16 =
* Restored street number validation on addresses based on feedback
* Added a setting to turn off street number validation (by default on)
* Updated delivery times to show evening times based on starting time 17:00 and higher

= 1.2.15 =
* Added a setting to change order status after label creation
* Fixed an issue with the settings menu jumping on certain browsers
* Fixed an issue with unavailable service combinations on bulk creation
* Removed number validation on addresses due to some addresses not requiring it

= 1.2.14 =
* Fixed issue with package rate not being properly calculated based on logged in users
* Added product based shipping restrictions
* Fixed an issue with shipping time windows

= 1.2.13 =
* Updated delivery times to correctly calculate with timezone settings
* Packstation postnumber input limited to DE
* Fixed sort position setting not working for shipping zones
* Fixed an issue where orders weren't linked with DHL order data

= 1.2.12 =
* Added the age check 18+ service
* Updated Packstation code input text
* Updated structure of settings with a new tab for label settings
* Updated Google Maps text for more clarification
* Removed input box for delivery times in the checkout
* Added developer methods to update shipment requests

= 1.2.11 =
* Added support for Direct Label Printing
* Added setting for maximum number of days shown for delivery times
* Updated bulk settings to also work with the open in new window setting
* Updated setting for combined labels to display page options only (A4)
* Updated translations to no longer use system codes
* Updated country check in the plugin
* Updated ServicePoint locator to support Packstation code input
* Updated code for increased compatibility with WooCommerce 2.6 (or higher)
* Removed placeholder Google Maps key

= 1.2.10 =
* Fixed delivery times not loading for newest WooCommerce release
* Fixed an issue where postal code is case sensitive

= 1.2.9 =
* Fixed pricing filters rounding prices

= 1.2.8 =
* Added pricing filters for weight and cart totals
* Added multiple labels per page option for bulk printing
* Added a box field for addresses for address additions after the street and number
* Fixed an issue addresses starting with numbers first
* Fixed ServicePoint not always searching for the selected country
* Fixed an issue where return labels had incorrect hide shipper information

= 1.2.7 =
* Updated the ServicePoint locator to load from DHL's own servers instead of third party
* Updated the ServicePoint locator to select the closest ServicePoint automatically
* Added developer filters for price manipulation
* Fixed delivery times API call to not send data when no postal code is set
* Fixed tax adjustment calculation

= 1.2.6 =
* Fixed automatic order id reference not being added for bulk
* Fixed ServicePoint locator not loading
* Added developer methods to update templates

= 1.2.5 =
* Updated feedback information to be multilingual
* Shipping methods can be sorted by default, price or custom order
* Added a setting to automatically create return labels
* Added a setting to automatically add order numbers as reference
* Updated the API endpoint for label creation
* Updated translations
* Fixed close button not showing on certain websites

= 1.2.4 =
* Fixed an issue with delivery times not always loading in the right order
* Fixed an issue that causes Customizer not to load on specific themes

= 1.2.3 =
* Updated bulk label creation from 1 type to each type enable-able separately
* Added mailbox option for bulk label creation
* Added optional fields to replace shipping text in the checkout
* Added Same Day, No Neighbour shipping for checkout
* Added Evening, No Neighbour shipping for checkout
* Added delivery times for No Neighbour shipping methods
* Fixed a compatibility issue with third party plugins

= 1.2.2 =
* Added selectable delivery times based on location
* Added an automatic switch between Same Day / Home and Evening delivery for delivery times
* Added a filter to sort orders based on estimated shipping days in the admin
* Added cutoff times for delivery times
* Added days needed for shipping for delivery times
* Added colored indicators for estimated shipping days in the admin
* Added configurable shipping days for delivery times

= 1.2.1 =
* Additional return labels can be created alongside regular labels
* Added settings to set a default address for return labels
* Added bulk label creation and bulk label printing
* Added a setting to set the default size preference for bulk label creation
* Added the service option to hide shipping address
* Added settings to set a default address when hiding sender address

= 1.2.0 =
* Added Usabilla feedback button to the plugin settings page
* Added an option to calculate free shipping after applying discounts
* Updated free shipping settings to be either free, or for discounts
* Each delivery option can now be seperately set to be eligable for free or discounted shipping
* Each delivery option has now it's own free or discounted pricing
* Enabled most shipping options available in My DHL Platform.
* Shipping option - Reference
* Shipping option - Same-day delivery
* Shipping option - Extra Assured
* Shipping option - Shipment insurance
* Shipping option - Saturday delivery
* Shipping option - Expresser (before 11 AM shipping)
* Shipping option - Terminal
* ServicePoint can now be selected and changed in the admin, whether a customer has selected a ServicePoint or not
* Updated label creation interface to be in-line with My DHL Platform
* Updated ServicePoint Locator to use the unified React Component version
* Updated ServicePoint Locator - Removed land selection and automatically matches customer's country
* Updated ServicePoint Locator - Updated logic to use customer's postcode on first load
* Updated ServicePoint Locator - Optimized for mobile, phone and desktop

= 1.0.2 =
* Shipping zones added
* Added Cypress tests
* Checkout shows only allowed shipping methods
* Added missing customer fields that prevented customers from receiving notifications
* Signature can be enabled by default
* Track & trace link updated to include postcode

= 1.0.1 =
* Track & trace added to email

= 1.0.0 =
* Webshop owners can create DHL labels.
* Customers can view their track & trace status on their account page.
* Customers can select DHL ServicePoint locations to deliver to.

== Frequently Asked Questions ==

Do you have any questions about our WooCommerce plug-in? We are ready to assist you and we will try to provide you with an answer as soon as possible. In need of a quick solution? See if your question is in the shortlist below or check our [manual](https://www.dhlparcel.nl/sites/default/files/content/PDF/Manual_WooCommerce_plug-in_EN.pdf). If you didn’t find what you were looking for, we will gladly assist you if you send us an [e-mail](mailto:cimparcel@dhl.com) or call us at 088 34 54 333.

= How to get started =

Click [here](https://www.dhlparcel.nl/en/business/woocommerce-plugin) for the manual on how to get started

= What do the additional services for consumers entail? =

What additional services can be picked from depends on the chosen delivery method. For instance, if you decide to send a mailbox parcel, it is not possible to choose the Signature option. However, this option is available when you decide to send the parcel as a regular home delivery shipment.

**Overview additional consumer services:**

* Reference: you can add a reference to the shipment, which will be shown as text on the shipping label.
* Return label: a return label can be created when printing the initial shipping label.
* DHL SameDay: if this product is in your DHL contract, we will be able to deliver your parcel the same day between 6 PM and 9 PM.
* Extra Assured: in case of damage or loss you will be able to claim the purchase value up until €500,-.
* Signature: the recipient will sign upon receipt. This signature will be visible in track and trace.
* Evening delivery: we will deliver the parcel between 6 PM and 9.30 PM.
* No delivery at neighbor: we will deliver the parcel at the recipient and if they are not home, we will not attempt a delivery at one of their neighbors.
* Age check 18+: the courier checks the recipient's age upon delivery.

= What do the additional services for businesses entail? =

What additional services can be picked from depends on the chosen delivery method.

**Overview additional business services:**

* Reference: you can add a reference to the shipment, which will be shown as text on the shipping label.
* Return label: a return label can be created when printing the initial shipping label.
* Additional transport insurance: additional insurance for your valuable shipments. If your goods are worth more than € 50.000, it is important to contact our customer service for permission.
* Saturday delivery: delivery on Saturday between 9 AM and 3 PM.
* Expresser: delivery next day before 11 AM.
* Hide sender: you will be able to show an alternative shipper name and address on the shipping label.
* Construction site delivery: delivery on locations that are under construction.
* Ex works: the recipient will pay DHL the shipping costs.

= Migrating from DHL for WooCommerce =

If you were using DHL for WooCommerce plugin previously, this plugin will continue using the same settings. No additional configuration is required, but we do recommend disabling the original DHL for WooCommerce plugin after verifying everything works with DHL Parcel for WooCommerce.

== Upgrade Notice ==

= 2.0.0 =
* Stand-alone version released

== Screenshots ==
1. Create labels from the order screen
2. Settings in WooCommerce > Settings > Shipping > DHL for WooCommerce
3. Customize delivery options in DHL for WooCommerce > Shipment Options
4. Print labels directly from the order screen
5. Delivery times in the checkout
6. ServicePoint locator in the checkout
