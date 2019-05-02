# DHL Parcel plugin for WooCommerce

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
