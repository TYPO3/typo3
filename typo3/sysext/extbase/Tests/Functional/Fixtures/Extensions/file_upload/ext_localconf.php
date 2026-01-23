<?php

defined('TYPO3') or die();

use TYPO3Tests\FileUpload\Domain\Model\ModelWithTextfield;
use TYPO3Tests\FileUpload\Domain\Model\XClassForModelWithTextfield;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][ModelWithTextfield::class] = [
    'className' => XClassForModelWithTextfield::class,
];
