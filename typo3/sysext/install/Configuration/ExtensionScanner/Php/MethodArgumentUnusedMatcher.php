<?php
return [
    'TYPO3\CMS\Core\Html\RteHtmlParser->RTE_transform' => [
        'unusedArgumentNumbers' => [ 2 ],
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-79341-MethodsRelatedToRichtextConfiguration.rst',
        ],
    ],
    'TYPO3\CMS\Core\Localization\LocalizationFactory->getParsedData' => [
        'unusedArgumentNumbers' => [ 3, 4 ],
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80486-SettingCharsetViaLocalizationParserInterface-getParsedData.rst',
        ],
    ],
    'TYPO3\CMS\Core\TypoScript\TemplateService->linkData' => [
        'unusedArgumentNumbers' => [ 4 ],
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->whichWorkspace' => [
        'unusedArgumentNumbers' => [ 1 ],
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80485-MethodParameterOfTSFE-whichWorkspaceToReturnTheWorkspaceTitle.rst',
        ],
    ],
    'TYPO3\CMS\Core\Html\RteHtmlParser->TS_AtagToAbs' => [
        'unusedArgumentNumbers' => [ 2 ],
        'restFiles' => [
            'Deprecation-83252-Link-tagSyntaxProcesssing.rst',
        ],
    ],
];
