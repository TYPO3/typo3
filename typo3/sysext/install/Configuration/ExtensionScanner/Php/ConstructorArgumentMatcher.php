<?php

return [
    'TYPO3\CMS\Core\Package\PackageManager' => [
        'required' => [
            'numberOfMandatoryArguments' => 1,
            'maximumNumberOfArguments' => 1,
            'restFiles' => [
                'Deprecation-84109-DeprecateDependencyResolver.rst',
                'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
            ],
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController' => [
        'dropped' => [
            'maximumNumberOfArguments' => 5,
            'restFiles' => [
                'Breaking-82572-RDCTFunctionalityRemoved.rst',
                'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
            ],
        ],
        'unused' => [
            'unusedArgumentNumbers' => [ 6, 7 ],
            'restFiles' => [
                'Deprecation-86002-TSFEConstructorWithNo_cacheArgument.rst',
                'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
                'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
            ],
        ],
    ],
    'TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper' => [
        'called' => [
            'numberOfMandatoryArguments' => 7,
            'maximumNumberOfArguments' => 8,
            'restFiles' => [
                'Breaking-87305-UseConstructorInjectionInDataMapper.rst',
                'Deprecation-87305-UseConstructorInjectionInDataMapper.rst',
                'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
            ],
        ],
    ],
];
