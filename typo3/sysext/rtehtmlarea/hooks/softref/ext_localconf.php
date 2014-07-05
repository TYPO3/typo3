<?php
defined('TYPO3_MODE') or die();

// Registering soft reference parser for img tags in RTE content
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['softRefParser']['rtehtmlarea_images'] = '&TYPO3\\CMS\\Rtehtmlarea\\Hook\\SoftReferenceHook';
