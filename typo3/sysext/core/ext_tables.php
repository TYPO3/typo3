<?php

$GLOBALS['TCA']['sys_category'] = include(__DIR__ . '/Configuration/TCA/SysCategory.php');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('sys_category');

?>