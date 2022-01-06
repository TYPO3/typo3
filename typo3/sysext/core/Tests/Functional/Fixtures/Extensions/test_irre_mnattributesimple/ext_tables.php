<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::allowTableOnStandardPages(
    'tx_testirremnattributesimple_hotel,tx_testirremnattributesimple_offer,tx_testirremnattributesimple_hotel_offer_rel'
);
