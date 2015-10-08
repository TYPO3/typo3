<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TCA']['be_users']['columns']['password']['config']['max'] = 100;

// Backend configuration for saltedpasswords
// Get eval field operations methods as array keys
$operations = array_flip(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TCA']['be_users']['columns']['password']['config']['eval'], true));
// Remove md5 and temporary password from the list of evaluated methods
unset($operations['md5'], $operations['password']);
// Append new methods to have "password" as last operation.
$operations['TYPO3\\CMS\\Saltedpasswords\\Evaluation\\BackendEvaluator'] = 1;
$operations['password'] = 1;
$GLOBALS['TCA']['be_users']['columns']['password']['config']['eval'] = implode(',', array_keys($operations));
unset($operations);
