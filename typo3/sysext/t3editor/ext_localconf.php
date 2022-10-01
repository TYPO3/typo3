<?php

declare(strict_types=1);

use TYPO3\CMS\T3editor\Form\Element\T3editorElement;
use TYPO3\CMS\T3editor\Hook\FileEditHook;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/file_edit.php']['preOutputProcessingHook'][] =
    FileEditHook::class . '->preOutputProcessingHook';

// Register backend FormEngine node
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1433089350] = [
    'nodeName' => 't3editor',
    'priority' => 40,
    'class' => T3editorElement::class,
];
