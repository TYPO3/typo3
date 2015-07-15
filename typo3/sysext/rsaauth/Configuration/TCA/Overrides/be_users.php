<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TCA']['be_users']['columns']['password']['config']['renderType'] = 'rsaInput';
