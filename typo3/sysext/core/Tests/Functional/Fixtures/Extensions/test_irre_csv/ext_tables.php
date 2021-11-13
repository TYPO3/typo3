<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::allowTableOnStandardPages('tx_testirrecsv_hotel');
ExtensionManagementUtility::allowTableOnStandardPages('tx_testirrecsv_offer');
ExtensionManagementUtility::allowTableOnStandardPages('tx_testirrecsv_price');
