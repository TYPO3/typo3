<?php
return [
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->searchWhere' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Package\PackageManager->__construct' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84109-DeprecateDependencyResolver.rst',
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
];
