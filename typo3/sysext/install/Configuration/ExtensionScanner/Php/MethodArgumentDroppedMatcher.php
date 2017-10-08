<?php
return [
    'TYPO3\CMS\Core\Charset\CharsetConverter->euc_char_mapping' => [
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->sb_char_mapping' => [
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->utf8_char_mapping' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\DataHandling\DataHandler->extFileFunctions' => [
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80513-DataHandlerVariousMethodsAndMethodArguments.rst',
        ],
    ],
    'TYPO3\CMS\Core\Localization\LanguageStore->setConfiguration' => [
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Localization\Parser\AbstractXmlParser->getParsedData' => [
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80486-SettingCharsetViaLocalizationParserInterface-getParsedData.rst',
        ],
    ],
    'TYPO3\CMS\Core\Localization\Parser\LocalizationParserInterface->getParsedData' => [
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80486-SettingCharsetViaLocalizationParserInterface-getParsedData.rst',
        ],
    ],
    'TYPO3\CMS\Core\Localization\Parser\LocallangXmlParser->getParsedData' => [
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80486-SettingCharsetViaLocalizationParserInterface-getParsedData.rst',
        ],
    ],
    'TYPO3\CMS\Core\Page\PageRenderer->addInlineLanguageLabelFile' => [
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Page\PageRenderer->includeLanguageFileForInline' => [
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Persistence\Generic\Query->like' => [
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-57385-DeprecateParameterCaseSensitiveOfExtbaseLikeComparison.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Plugin\AbstractPlugin->pi_getLL' => [
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-71917-DeprecateTheArgumentHscForGetLLGetLLLAndSL.rst',
        ],
    ],
    'TYPO3\CMS\Lang\LanguageService->getLL' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-71917-DeprecateTheArgumentHscForGetLLGetLLLAndSL.rst'
        ],
    ],
    'TYPO3\CMS\Lang\LanguageService->getLLL' => [
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-71917-DeprecateTheArgumentHscForGetLLGetLLLAndSL.rst'
        ],
    ],
    'TYPO3\CMS\Lang\LanguageService->sL' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-71917-DeprecateTheArgumentHscForGetLLGetLLLAndSL.rst'
        ],
    ],
    'TYPO3\CMS\Frontend\Page\PageRepository->getRawRecord' => [
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-81218-NoWSOLArgumentInPageRepository-getRawRecord.rst',
        ],
    ],
    'TYPO3\CMS\Backend\History\RecordHistory->performRollback' => [
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-55298-DecoupledHistoryFunctionality.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->__construct' => [
        'maximumNumberOfArguments' => 7,
        'restFiles' => [
            'Breaking-82572-RDCTFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\DataHandling\DataHandler->printLogErrorMessages' => [
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-82629-TceDbOptionsPrErrAndUPTRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility->mkdir_deep' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-82702-SecondArgumentOfGeneralUtilitymkdir_deep.rst',
        ],
    ],
];
