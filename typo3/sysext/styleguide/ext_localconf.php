<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

// Register own renderType for tx_styleguide_elements_basic user_1 as user1Element
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1536238257] = [
    'nodeName' => 'user1Element',
    'priority' => 40,
    'class' => \TYPO3\CMS\Styleguide\Form\Element\User1Element::class,
];

$GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['RTE-Styleguide'] = 'EXT:styleguide/Configuration/RTE/RTE-Styleguide.yaml';

ExtensionManagementUtility::addTypoScriptSetup('
module.tx_form.settings.yamlConfigurations {
  100 = EXT:styleguide/Configuration/Yaml/FormSetup.yaml
}
');
