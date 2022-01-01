<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::allowTableOnStandardPages(
    'tx_testirremnsymmetric_hotel,tx_testirremnsymmetric_hotel_rel'
);
