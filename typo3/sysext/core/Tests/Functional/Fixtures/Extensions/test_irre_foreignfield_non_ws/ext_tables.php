<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::allowTableOnStandardPages('tx_testirreforeignfieldnonws_hotel');
ExtensionManagementUtility::allowTableOnStandardPages('tx_testirreforeignfieldnonws_offer');
ExtensionManagementUtility::allowTableOnStandardPages('tx_testirreforeignfieldnonws_price');
