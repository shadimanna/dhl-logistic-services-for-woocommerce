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
    }

    private function option()
    {
        // Main send method
        __('OPTION_PS');
        __('OPTION_DOOR');
        __('OPTION_BP');
        __('OPTION_H');

        // Additional and special services
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
        __('OPTION_PERST_NOTE');
        __('OPTION_SDD');
        __('OPTION_S');
    }

}

endif;