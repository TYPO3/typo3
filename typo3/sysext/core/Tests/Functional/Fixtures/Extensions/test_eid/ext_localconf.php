<?php

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['test_eid'] = \TYPO3\TestEid\Eid\EidAutoResponder::class . '::processRequest';
