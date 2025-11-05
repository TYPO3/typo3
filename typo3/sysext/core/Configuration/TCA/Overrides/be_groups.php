<?php

defined('TYPO3') or die();

$GLOBALS['TCA']['be_groups']['columns']['hidden']['authenticationContext']['group'] = 'be.userManagement';
$GLOBALS['TCA']['be_groups']['columns']['hidden']['label'] = 'core.db.accounts:group.enabled';
