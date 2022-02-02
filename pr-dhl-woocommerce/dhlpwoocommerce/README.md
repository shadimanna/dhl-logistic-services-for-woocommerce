# DHL Parcel plugin for WooCommerce
 
v1.3.17
## Changes
- Small fix for ServicePoint locator in checkout
 
v1.3.16
## Changes
- Fixed an issue with reference values not loading correctly
 
v1.3.15
## Changes
- Changed evening delivery times text to 17.30 - 22:00
- Add order number in REFERENCE2 and add a filter to change it programmatically
 
v1.3.14
## Changes
- Added ServicePoint information for order completion mail
 
v1.3.13
## Changes
- Added a setting to set additional shipping fees for specific products
- Added support for additional order status
- Added automatic label creation and printing
- Updated developer settings text to avoid confusion
 
v1.3.12
## Changes
- Fixed autoloader for PHP8
 
v1.3.11
## Changes
- Fixed an issue where decimals were not calculated correctly in conditional rules for delivery options
 
v1.3.10
## Changes
- Added a new bulk setting to print all labels with Same Day Delivery
- Added a product setting to automatically use the parcel type mailbox based on conditions
- Added a setting to display free shipping in different ways
- Added snippet information to the settings interface for custom order numbers
- Updated translation
- Fixed an issue where ServicePoint Locator isn’t loaded depending on shipping zones
 
v1.3.9
## Changes
- Use multi-label API instead of PDFMerger
- Add filter for reference
 
v1.3.8
## Changes
- Updated to use the newest version of the ServicePoint Locator
- Improved automatic search of the closest ServicePoint to filter on last mile
- Added filters to bulk redirects for developers to customize
- Fixed an issue where conditionally disabled shipping methods were not applied to delivery times
 
v1.3.7
## Changes
- Fixed an issue where the DPI module was not loading on certain pages
 
v1.3.6
## Changes
- Fixed an issue with certain settings not being able to save with certain themes

v1.3.5
## Changes
- Added a fallback notice for switching between DHL Parcel and Deutsche Post International

v1.3.4
## Changes
- Improved mailpost bulk processing

v1.3.2
## Changes
- Updated Mopinion code to include language setting
- Removed shortcode for tracking information to prevent errors

v1.3.1
## Changes
- Added a shortcode for tracking information
- Added additional meta data of preferred delivery date for third party exports
- Improved PDFMerger loading
- Fixed an issue with multiple warnings showing in admin 
- Updated feedback system from Usabilla to Mopinion

v1.3.0
## Changes
- Enabled Austria as shipping country
- Added a setting to show the selected ServicePoint information in e-mails
- Added requirements and visual indicators to enable Same Day delivery so it works without enabling delivery times
- Added additional meta data for third party exports
- Added a dynamic notification to switch between Deutsche Post International and DHL Parcel
- Updated address parsing to support addresses starting with numbers
- Fixed an issue where limiting DHL methods didn't work if none were selected
- Fixed correct printer responses being sent as error reports
- Fixed an issue with non-default price decimals not being handled correctly
- Removed sending error reports when credentials not configured and still empty

v1.2.19

## Changes
- Updated ServicePoint selector width to scale to full width
- Updated ServicePoint selector to block the enter key on input to prevent accidental form submission
- Fixed an issue that caused PHP warning errors on pages with cart data
- Updated translation texts

v1.2.18

## Changes
- Added a postnumber input pop-up for Packstations that require it with the mapless locator 
- Fixed an issue with logged in users not seeing shipping methods in the checkout

v1.2.17

## Changes
- Added an error message when trying to create a label without country information
- Added download and print button after creating labels in bulk
- Added customizable track & trace text
- Added a fallback ServicePoint selector when no Google Maps key is provided
- Added secondary reference service
- Updated number parsing from address data
- Updated error responses to include detail information
- Updated evening detection for more dynamic delivery times
- Fixed German packStations being selected by default
- Fixed an issue with same day delivery when combined with delivery times
- Removed cash on delivery service

v1.2.16

## Changes
- Restored street number validation on addresses based on feedback
- Added a setting to turn off street number validation (by default on)
- Updated delivery times to show evening times based on starting time 17:00 and higher

v1.2.15

## Changes
- Added a setting to change order status after label creation
- Fixed an issue with the settings menu jumping on certain browsers
- Fixed an issue with unavailable service combinations on bulk creation
- Removed number validation on addresses due to some addresses not requiring it

v1.2.14

## Changes
- Fixed issue with package rate not being properly calculated based on logged in users
- Added product based shipping restrictions
- Fixed an issue with shipping time windows

v1.2.13

## Changes
- Updated delivery times to correctly calculate with timezone settings
- Packstation postnumber input limited to DE
- Fixed sort position setting not working for shipping zones
- Fixed an issue where orders weren't linked with DHL order data

v1.2.12

## Changes
- Added the age check 18+ service
- Updated Packstation code input text
- Updated structure of settings with a new tab for label settings
- Updated Google Maps text for more clarification
- Removed input box for delivery times in the checkout
- Added developer methods to update shipment requests

v1.2.11

## Changes
- Added support for Direct Label Printing
- Added setting for maximum number of days shown for delivery times
- Updated bulk settings to also work with the open in new window setting
- Updated setting for combined labels to display page options only (A4)
- Updated translations to no longer use system codes
- Updated country check in the plugin
- Updated ServicePoint locator to support Packstation code input
- Updated code for increased compatibility with WooCommerce 2.6 (or higher)
- Removed placeholder Google Maps key

v1.2.10

## Changes
- Fixed delivery times not loading for newest WooCommerce release
- Fixed an issue where postal code is case sensitive

V1.2.9

## Changes
- Fixed pricing filters rounding prices

V1.2.8

## Changes
- Added pricing filters for weight and cart totals
- Added multiple labels per page option for bulk printing
- Added a box field for addresses for address additions after the street and number
- Fixed an issue addresses starting with numbers first
- Fixed ServicePoint not always searching for the selected country
- Fixed an issue where return labels had incorrect hide shipper information

V1.2.7

## Changes
- Updated the ServicePoint locator to load from DHL's own servers instead of third party
- Updated the ServicePoint locator to select the closest ServicePoint automatically
- Added developer filters for price manipulation
- Fixed delivery times API call to not send data when no postal code is set
- Fixed tax adjustment calculation

V1.2.6

## Changes
- Fixed automatic order id reference not being added for bulk
- Fixed ServicePoint locator not loading
- Added developer methods to update templates

V1.2.5

## Changes
- Updated feedback information to be multilingual
- Shipping methods can be sorted by default, price or custom order
- Added a setting to automatically create return labels
- Added a setting to automatically add order numbers as reference
- Updated the API endpoint for label creation
- Updated translations
- Fixed close button not showing on certain websites

V1.2.4

## Changes
- Fixed an issue with delivery times not always loading in the right order
- Fixed an issue that causes Customizer not to load on specific themes

V1.2.3

## Changes
- Updated bulk label creation from 1 type to each type enable-able separately
- Added mailbox option for bulk label creation
- Added optional fields to replace shipping text in the checkout
- Added Same Day, No Neighbour shipping for checkout 
- Added Evening, No Neighbour shipping for checkout
- Added delivery times for No Neighbour shipping methods
- Fixed a compatibility issue with third party plugins

V1.2.2

## Changes
- Added selectable delivery times based on location
- Added an automatic switch between Same Day / Home and Evening delivery for delivery times
- Added a filter to sort orders based on estimated shipping days in the admin
- Added cutoff times for delivery times
- Added days needed for shipping for delivery times
- Added colored indicators for estimated shipping days in the admin
- Added configurable shipping days for delivery times

V1.2.1

## Changes
- Additional return labels can be created alongside regular labels
- Added settings to set a default address for return labels
- Added bulk label creation and bulk label printing
- Added a setting to set the default size preference for bulk label creation
- Added the service option to hide shipping address
- Added settings to set a default address when hiding sender address

V1.2.0

## Changes
- Added Usabilla feedback button to the plugin settings page
- Added an option to calculate free shipping after applying discounts
- Updated free shipping settings to be either free, or for discounts
- Each delivery option can now be seperately set to be eligable for free or discounted shipping
- Each delivery option has now it's own free or discounted pricing
- Enabled most shipping options available in My DHL Platform.
    - Reference
    - Same-day delivery
    - Extra Assured
    - Shipment insurance
    - Saturday delivery
    - Expresser (before 11 AM shipping)
    - Terminal
- ServicePoint can now be selected and changed in the admin, whether a customer has selected a ServicePoint or not
- Updated label creation interface to be in-line with My DHL Platform
- Updated ServicePoint Locator to use the unified React Component version
    - Removed land selection and automatically matches customer's country
    - Updated logic to use customer's postcode on first load
    - Optimized for mobile, phone and desktop

V1.0.2

## Changes
- Shipping zones added
- Added Cypress tests
- Checkout shows only allowed shipping methods
- Added missing customer fields that prevented customers from receiving notifications
- Signature can be enabled by default
- Track & trace link updated to include postcode

V1.0.1

## Changes
- Track & trace added to email

V1.0.0

## Features

- Webshop owners can create DHL labels.
- Customers can view their track & trace status on their account page.
- Customers can select DHL ServicePoint locations to deliver to.
