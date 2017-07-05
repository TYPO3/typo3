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
];
