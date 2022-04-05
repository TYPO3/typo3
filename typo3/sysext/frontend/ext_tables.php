<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

// Add allowed records to pages
ExtensionManagementUtility::allowTableOnStandardPages('tt_content,sys_template,backend_layout');
