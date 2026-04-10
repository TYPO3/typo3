<?php

return [
    'TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addNavigationComponent' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-82899-MoreRestrictingChecksForAPIMethodsInExtensionManagementUtility.rst',
            'Breaking-100963-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::isOnCurrentHost' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-109523-GeneralUtilityIsOnCurrentHostWithoutRequest.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-101311-MakeParameterForGeneralUtilitySanitizeLocalUrlRequired.rst',
            'Deprecation-109544-GeneralUtilitySanitizeLocalUrlWithoutRequest.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin' => [
        'numberOfMandatoryArguments' => 5,
        'maximumNumberOfArguments' => 5,
        'restFiles' => [
            'Deprecation-105076-PluginContentElementAndPluginSubTypes.rst',
        ],
    ],
];
