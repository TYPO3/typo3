<?php

declare(strict_types=1);

use TYPO3\TestEid\Eid\EidAutoResponder;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['test_eid'] = EidAutoResponder::class . '::processRequest';
