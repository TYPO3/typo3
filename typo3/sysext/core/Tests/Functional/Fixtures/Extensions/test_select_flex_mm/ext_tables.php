<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::allowTableOnStandardPages('tx_testselectflexmm_local');
ExtensionManagementUtility::allowTableOnStandardPages('tx_testselectflexmm_foreign');
