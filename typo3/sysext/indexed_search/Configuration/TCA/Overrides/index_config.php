<?php

defined('TYPO3') or die();

$GLOBALS['TCA']['index_config']['columns']['hidden']['config']['default'] = 1;
unset($GLOBALS['TCA']['index_config']['columns']['hidden']['exclude']);
