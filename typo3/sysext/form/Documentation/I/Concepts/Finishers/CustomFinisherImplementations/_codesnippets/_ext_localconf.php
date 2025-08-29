<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addTypoScriptSetup('
        # for the backend
        module.tx_form.settings.yamlConfigurations {
            123456789 = EXT:yourExtension/Configuration/Form/CustomFormSetup.yaml
        }
        # for the frontend - otherwise the custom finisher class is not found
        # because of the missing "implementationClassName"
        plugin.tx_form.settings.yamlConfigurations {
            123456789 = EXT:yourExtension/Configuration/Form/Backend.yaml
        }
    ');
