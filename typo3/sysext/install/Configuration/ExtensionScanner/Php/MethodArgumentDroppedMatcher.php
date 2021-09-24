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
            'Deprecation-71917-DeprecateTheArgumentHscForGetLLGetLLLAndSL.rst',
        ],
    ],
    'TYPO3\CMS\Lang\LanguageService->getLLL' => [
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-71917-DeprecateTheArgumentHscForGetLLGetLLLAndSL.rst',
        ],
    ],
    'TYPO3\CMS\Lang\LanguageService->sL' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-71917-DeprecateTheArgumentHscForGetLLGetLLLAndSL.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Page\PageRepository->getRawRecord' => [
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-81218-NoWSOLArgumentInPageRepository-getRawRecord.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\History\RecordHistory->performRollback' => [
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-55298-DecoupledHistoryFunctionality.rst',
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
    'TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper->getPlainValue' => [
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-83241-ExtbaseRemovedCustomFunctionalityForDataMapper-getPlainValue.rst',
        ],
    ],
    'TYPO3\CMS\Impexp\Controller\ImportExportController->addRecordsForPid' => [
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-83592-ImpexpRemovedMaximumNumberOfRecordsRestriction.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Impexp\Controller\ImportExportController->exec_listQueryPid' => [
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-83592-ImpexpRemovedMaximumNumberOfRecordsRestriction.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\core\Authentication\BackendUserAuthentication->getTSConfig' => [
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84993-DeprecateSomeTSconfigRelatedMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository->fetchOriginLanguage' => [
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-84877-MethodsOfLocalizationRepositoryChanged.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository->getLocalizedRecordCount' => [
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-84877-MethodsOfLocalizationRepositoryChanged.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository->fetchAvailableLanguages' => [
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-84877-MethodsOfLocalizationRepositoryChanged.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository->getRecordsToCopyDatabaseResult' => [
        'maximumNumberOfArguments' => 4,
        'restFiles' => [
            'Breaking-84877-MethodsOfLocalizationRepositoryChanged.rst',
        ],
    ],
    'TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt->getHashedPassword' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85804-SaltedPasswordHashClassDeprecations.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Saltedpasswords\Salt\Md5Salt->getHashedPassword' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85804-SaltedPasswordHashClassDeprecations.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt->getHashedPassword' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85804-SaltedPasswordHashClassDeprecations.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt->getHashedPassword' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85804-SaltedPasswordHashClassDeprecations.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Page\PageRenderer->addInlineLanguageLabelArray' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85806-SecondArgumentOfPageRendereraddInlineLanguageLabelArray.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Authentication\BackendUserAuthentication->modAccess' => [
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-86441-VariousMethodsAndPropertiesInsideBackendUserAuthentication.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Frontend\Page\PageRepository->enableFields' => [
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-88574-4thParameterOfPageRepository-enableFieldsRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Localization\LanguageService->includeLLFile' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-88567-GLOBALS_LOCAL_LANG.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\IndexedSearch\Indexer->backend_initIndexer' => [
        'maximumNumberOfArguments' => 5,
        'restFiles' => [
            'Breaking-88741-CHashCalculationInIndexedSearchRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Resource\ResourceCompressor->concatenateCssFiles' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-88758-SelectiveConcatenationOfCSSFilesInResourceCompressorRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility->wrapClickMenuOnIcon' => [
        'maximumNumberOfArguments' => 4,
        'restFiles' => [
            'Deprecation-92583-DeprecateLastArgumentsOfWrapClickMenuOnIcon.rst',
        ],
    ],
    'TYPO3\CMS\Core\Page\PageRenderer->render' => [
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-93003-LimitationOfPageRendererToOnlyRenderFullPage.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->getQueryArguments' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-93041-RemoveTypoScriptOptionAddQueryStringmethod.rst',
        ],
    ],
    'TYPO3\CMS\Core\Resource\AbstractFile->getPublicUrl' => [
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-94193-PublicUrlWithRelativePathsInFALAPI.rst',
        ],
    ],
    'TYPO3\CMS\Core\Resource\FileReference->getPublicUrl' => [
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-94193-PublicUrlWithRelativePathsInFALAPI.rst',
        ],
    ],
    'TYPO3\CMS\Core\Resource\Folder->getPublicUrl' => [
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-94193-PublicUrlWithRelativePathsInFALAPI.rst',
        ],
    ],
    'TYPO3\CMS\Core\Resource\ResourceStorage->getPublicUrl' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-94193-PublicUrlWithRelativePathsInFALAPI.rst',
        ],
    ],
    'TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\YouTubeHelper->getPublicUrl' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-94193-PublicUrlWithRelativePathsInFALAPI.rst',
        ],
    ],
    'TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\VimeoHelper->getPublicUrl' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-94193-PublicUrlWithRelativePathsInFALAPI.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\ApplicationInterface->run' => [
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-94272-DeprecatedApplication-runCallback.rst',
        ],
    ],
    'TYPO3\CMS\Core\Database\RelationHandler->writeForeignField' => [
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-95062-SkipSortingArgumentOfRelationHandler-writeForeignField.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->getATagParams' => [
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-95219-TypoScriptFrontendController-ATagParams.rst',
        ],
    ],
    'TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->writeUC' => [
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-95320-VariousMethodArgumentsInAuthenticationObjects.rst',
        ],
    ],
    'TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->unpack_uc' => [
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-95320-VariousMethodArgumentsInAuthenticationObjects.rst',
        ],
    ],
    'TYPO3\CMS\Core\Authentication\BackendUserAuthentication->backendCheckLogin' => [
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-95320-VariousMethodArgumentsInAuthenticationObjects.rst',
        ],
    ],
    'TYPO3\CMS\Core\Authentication\BackendUserAuthentication->isInWebMount' => [
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-95320-VariousMethodArgumentsInAuthenticationObjects.rst',
        ],
    ],
];
