<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::allowTableOnStandardPages(
    'tx_testirremnattributeinline_hotel,tx_testirremnattributeinline_hotel_offer_rel,tx_testirremnattributeinline_offer,tx_testirremnattributeinline_price'
);
