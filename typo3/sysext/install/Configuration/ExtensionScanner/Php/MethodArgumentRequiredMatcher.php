<?php

return [
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->searchWhere' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->calculateLinkVars' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-86046-AdditionalArgumentsInSeveralTypoScriptFrontendControllerMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->preparePageContentGeneration' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-86046-AdditionalArgumentsInSeveralTypoScriptFrontendControllerMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Routing\Aspect\AspectFactory->createAspects' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Important-88720-RespectSiteForPersistedMappers.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Persistence\QueryInterface->logicalAnd' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => PHP_INT_MAX,
        'restFiles' => [
            'Breaking-96044-HardenMethodSignatureOfLogicalAndAndLogicalOr.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Persistence\Generic\Query->logicalAnd' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => PHP_INT_MAX,
        'restFiles' => [
            'Breaking-96044-HardenMethodSignatureOfLogicalAndAndLogicalOr.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Persistence\QueryInterface->logicalOr' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => PHP_INT_MAX,
        'restFiles' => [
            'Breaking-96044-HardenMethodSignatureOfLogicalAndAndLogicalOr.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Persistence\Generic\Query->logicalOr' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => PHP_INT_MAX,
        'restFiles' => [
            'Breaking-96044-HardenMethodSignatureOfLogicalAndAndLogicalOr.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->generatePage_postProcessing' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-97862-HooksRelatedToGeneratingPageContentRemoved.rst',
        ],
    ],
];
