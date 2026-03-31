<?php

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['buildFormDefinitionValidationConfiguration'][]
    = \MyVendor\MyExtension\Hooks\MyValidationConfigurationHook::class;
