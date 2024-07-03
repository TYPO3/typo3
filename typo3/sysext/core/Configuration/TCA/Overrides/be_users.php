<?php

defined('TYPO3') or die();

// New be_users are disabled by default and can not disable themselves
$GLOBALS['TCA']['be_users']['columns']['disable']['displayCond'] = 'USER:' . \TYPO3\CMS\Core\Hooks\TcaDisplayConditions::class . '->isRecordCurrentUser:false';
$GLOBALS['TCA']['be_users']['columns']['disable']['config']['default'] = 1;
