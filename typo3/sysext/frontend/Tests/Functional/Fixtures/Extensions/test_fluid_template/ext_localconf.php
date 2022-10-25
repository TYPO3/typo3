<?php

use TYPO3Tests\TestFluidTemplate\ContentObjectCurrentValue;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['postInit'][] = ContentObjectCurrentValue::class;
