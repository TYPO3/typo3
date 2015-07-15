<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TCA']['fe_users']['columns']['password']['config']['renderType'] = 'rsaInput';
