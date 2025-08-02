<?php

defined('TYPO3') or die();

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

ExtensionUtility::registerPlugin(
    'extbase_upload',
    'Pi1',
    'Upload plugin for single file property in a domain object'
);
