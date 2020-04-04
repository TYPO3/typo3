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
            'Deprecation-86046-AdditionalArgumentsInSeveralTypoScriptFrontendControllerMethods.rst'
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->preparePageContentGeneration' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-86046-AdditionalArgumentsInSeveralTypoScriptFrontendControllerMethods.rst'
        ],
    ],
    'TYPO3\CMS\Core\Routing\Aspect\AspectFactory->createAspects' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Important-88720-RespectSiteForPersistedMappers.rst',
        ],
    ],
];
