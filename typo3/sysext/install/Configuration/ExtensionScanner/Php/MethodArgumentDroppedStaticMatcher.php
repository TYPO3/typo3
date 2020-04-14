<?php

return [
    'TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-73516-VariousGeneralUtilityMethods.rst'
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::wrapJS' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-75621-GeneralUtilityMethods.rst'
        ],
    ],
    'TYPO3\CMS\Recycler\Utility\RecyclerUtility::getRecordPath' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-75637-DeprecateOptionalParametersOfRecyclerUtilitygetRecordPath.rst'
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction' => [
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-74533-ThrowExceptionIfUserFunctionDoesNotExist.rst',
        ]
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-54152-DeprecateArgumentsOfBackendUtilityGetPagesTSconfig.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-82899-ExtensionManagementUtilityMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::explodeUrl2Array' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85801-GeneralUtilityexplodeUrl2Array-2ndMethodArgument.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty' => [
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-87332-AvoidRuntimeReflectionCallsInObjectAccess.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyInternal' => [
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-87332-AvoidRuntimeReflectionCallsInObjectAccess.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty' => [
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-87332-AvoidRuntimeReflectionCallsInObjectAccess.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::getUrl' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-90956-AlternativeFetchMethodsAndReportsForGeneralUtilitygetUrl.rst',
        ],
    ],
];
