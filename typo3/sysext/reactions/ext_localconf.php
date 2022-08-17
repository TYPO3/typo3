<?php

declare(strict_types=1);

use TYPO3\CMS\Reactions\Form\Element\FieldMapElement;
use TYPO3\CMS\Reactions\Form\Element\UuidElement;
use TYPO3\CMS\Reactions\Hooks\DataHandlerHook;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1660911089] = [
    'nodeName' => 'fieldMap',
    'priority' => 40,
    'class' => FieldMapElement::class,
];

// @todo This should be a dedicated TCA type instead
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1660911009] = [
    'nodeName' => 'uuid',
    'priority' => 40,
    'class' => UuidElement::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = DataHandlerHook::class;
