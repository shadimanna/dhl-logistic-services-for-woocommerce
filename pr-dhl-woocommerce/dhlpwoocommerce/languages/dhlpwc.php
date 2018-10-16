<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Translations')) :

/**
 * This is a custom class only for the purpose of adding dynamic strings to PO files with POT generators
 */
class DHLPWC_Translations
{

    private function parceltype()
    {
        __('PARCELTYPE_SMALL');
        __('PARCELTYPE_MEDIUM');
        __('PARCELTYPE_LARGE');
        __('PARCELTYPE_PALLET');
        __('PARCELTYPE_BULKY');
        __('PARCELTYPE_XSMALL');
        __('PARCELTYPE_XLARGE');
    }

    private function option()
    {
        // Delivery option
        __('OPTION_PS');
        __('OPTION_DOOR');
        __('OPTION_BP');
        __('OPTION_H');

        // Service option
        __('OPTION_COD_CASH');
        __('OPTION_EXP');
        __('OPTION_BOUW');
        __('OPTION_REFERENCE2');
        __('OPTION_EXW');
        __('OPTION_EA');
        __('OPTION_EVE');
        __('OPTION_RECAP');
        __('OPTION_COD_CHECK');
        __('OPTION_INS');
        __('OPTION_REFERENCE');
        __('OPTION_HANDT');
        __('OPTION_NBB');
        __('OPTION_ADD_RETURN_LABEL');
        __('OPTION_SSN');
        __('OPTION_PERS_NOTE');
        __('OPTION_SDD');
        __('OPTION_S');
        __('OPTION_IS_BULKY');
    }

    private function option_description()
    {
        __('Delivery to the address of the recipient');
        __('Reference');
        __('E-mail to receiver');
        __('Same-day delivery');
        __('Print extra label for return shipment');
        __('Extra assurance');
        __('Signature on delivery');
        __('Evening delivery');
        __('No neighbour delivery');
        __('Reference');
        __('Delivery to the specified DHL Parcelshop or DHL Parcelstation');
        __('Mailbox delivery');
        __('Hold for collection');
        __('Print extra label for return shipment');
        __('All risks insurance');
        __('Saturday delivery');
        __('Expresser');
        __('Undisclosed sender');
        __('Cash on delivery. Payment method cash, paid by sender.');
        __('Delivery to construction site');
        __('Ex Works');
    }

}

endif;
