<?php
return [
    // Removed methods
    'TYPO3\CMS\Backend\Clipboard\Clipboard->confirmMsg' => [
        'numberOfMandatoryArguments' => 4,
        'maximumNumberOfArguments' => 5,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\BackendController->addCssFile' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80491-BackendControllerInclusionHooks.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\BackendController->addJavascript' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80491-BackendControllerInclusionHooks.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\BackendController->addJavascriptFile' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80491-BackendControllerInclusionHooks.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\BackendController->includeLegacyBackendItems' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\Page\LocalizationController->getRecordUidsToCopy' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78872-DeprecateMethodGetRecordUidsToCopy.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\Page\PageLayoutController->printContent' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80445-DeprecatePrintContentMethods.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository->getAllowedLanguagesForBackendUser' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-75760-DeprecateMethodsOfLocalizationRepository.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository->getExcludeQueryPart' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-75760-DeprecateMethodsOfLocalizationRepository.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository->getPreviousLocalizedRecordUid' => [
        'numberOfMandatoryArguments' => 5,
        'maximumNumberOfArguments' => 5,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-79258-MethodsInLocalizationRepository.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository->getRecordLocalization' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 4,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-79258-MethodsInLocalizationRepository.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Form\FormDataProvider\AbstractItemProvider->sanitizeMaxItems' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78899-FormEngineMethods.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Module\AbstractFunctionModule->getBackPath' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78314-AbstractFunctionModule-getBackPath.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Module\AbstractFunctionModule->incLocalLang' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80511-AbstractFunctionModule-incLocalLangAndThisPath.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Form\AbstractFormElement->isWizardsDisabled' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Form\AbstractFormElement->renderWizards' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 9,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78899-FormEngineMethods.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Form\AbstractNode->getValidationDataAsDataAttribute' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78899-FormEngineMethods.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Form\FormResultCompiler->JStop' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-75363-DeprecateFormResultCompilerJStop.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Routing\UriBuilder->buildUriFromAjaxId' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-75340-MethodsRelatedToGeneratingTraditionalBackendAJAXURLs.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Template\DocumentTemplate->divider' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-71260-DocumentTemplateMethods.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Template\DocumentTemplate->funcMenu' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-72733-DeprecateMoreMethodsOfDocumentTemplate.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Template\DocumentTemplate->getContextMenuCode' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-72859-DeprecateMethodsOfDocumentTemplate.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Template\DocumentTemplate->getDragDropCode' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-72733-DeprecateMoreMethodsOfDocumentTemplate.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Template\DocumentTemplate->getHeader' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 6,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-72859-DeprecateMethodsOfDocumentTemplate.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Template\DocumentTemplate->getResourceHeader' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-72859-DeprecateMethodsOfDocumentTemplate.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Template\DocumentTemplate->getTabMenu' => [
        'numberOfMandatoryArguments' => 4,
        'maximumNumberOfArguments' => 6,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-72733-DeprecateMoreMethodsOfDocumentTemplate.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Template\DocumentTemplate->getTabMenuRaw' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Template\DocumentTemplate->header' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-72859-DeprecateMethodsOfDocumentTemplate.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Template\DocumentTemplate->icons' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-72859-DeprecateMethodsOfDocumentTemplate.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Template\DocumentTemplate->loadJavascriptLib' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-72859-DeprecateMethodsOfDocumentTemplate.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Template\DocumentTemplate->section' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 6,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-72859-DeprecateMethodsOfDocumentTemplate.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Template\DocumentTemplate->sectionBegin' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-71260-DocumentTemplateMethods.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Template\DocumentTemplate->sectionEnd' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-71260-DocumentTemplateMethods.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Template\DocumentTemplate->sectionHeader' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-71260-DocumentTemplateMethods.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Template\DocumentTemplate->t3Button' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-72859-DeprecateMethodsOfDocumentTemplate.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Template\DocumentTemplate->getVersionSelector' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-72733-DeprecateMoreMethodsOfDocumentTemplate.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Template\DocumentTemplate->viewPageIcon' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-72859-DeprecateMethodsOfDocumentTemplate.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Template\DocumentTemplate->wrapInCData' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-72733-DeprecateMoreMethodsOfDocumentTemplate.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Template\DocumentTemplate->wrapScriptTags' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-72859-DeprecateMethodsOfDocumentTemplate.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Template\ModuleTemplate->getVersionSelector' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-72733-DeprecateMoreMethodsOfDocumentTemplate.rst',
        ],
    ],
    'TYPO3\CMS\Backend\View\PageLayoutView->pages_getTree' => [
        'numberOfMandatoryArguments' => 5,
        'maximumNumberOfArguments' => 5,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-76520-DeprecateMethodPages_getTreeOfPageLayoutView.rst',
        ],
    ],
    'TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->veriCode' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-79327-DeprecateAbstractUserAuthenticationveriCodeMethod.rst',
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->convCapitalize' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78670-DeprecatedCharsetConverterMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->conv_case' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78670-DeprecatedCharsetConverterMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->euc_char2byte_pos' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78670-DeprecatedCharsetConverterMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->euc_strlen' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78670-DeprecatedCharsetConverterMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->euc_strtrunc' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78670-DeprecatedCharsetConverterMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->euc_substr' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 4,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78670-DeprecatedCharsetConverterMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->getPreferredClientLanguage' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-73511-BrowserLanguageDetectionMovedToLocales.rst',
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->strlen' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78670-DeprecatedCharsetConverterMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->strtrunc' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78670-DeprecatedCharsetConverterMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->substr' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 4,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78670-DeprecatedCharsetConverterMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->utf8_byte2char_pos' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78670-DeprecatedCharsetConverterMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->utf8_strlen' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78670-DeprecatedCharsetConverterMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->utf8_strpos' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78670-DeprecatedCharsetConverterMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->utf8_strrpos' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78670-DeprecatedCharsetConverterMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->utf8_strtrunc' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78670-DeprecatedCharsetConverterMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->utf8_substr' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78670-DeprecatedCharsetConverterMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap->loadExtensionTables' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80079-DeprecatedBootstraploadExtensionTables.rst',
        ],
    ],
    'TYPO3\CMS\Core\Database\RelationHandler->readyForInterface' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78899-FormEngineMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Database\QueryView->tableWrap' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-77557-MethodQueryView-tableWrap.rst',
        ],
    ],
    'TYPO3\CMS\Core\Imaging\GraphicalFunctions->createTempSubDir' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80514-GraphicalFunctions-tempPathAndCreateTempSubDir.rst',
        ],
    ],
    'TYPO3\CMS\Core\Imaging\GraphicalFunctions->prependAbsolutePath' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-74022-GraphicalFunctions-prependAbsolutePath.rst',
        ],
    ],
    'TYPO3\CMS\Core\Imaging\IconRegistry->getDeprecationSettings' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-73606-DeprecateIconRegistrygetDeprecationSettings.rst',
        ],
    ],
    'TYPO3\CMS\Core\Messaging\FlashMessage->getIconName' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78477-RefactoringOfFlashMessageRendering.rst',
        ],
    ],
    'TYPO3\CMS\Core\TypoScript\TemplateService->splitConfArray' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78650-TemplateService-splitConfArray.rst',
        ],
    ],
    'TYPO3\CMS\Core\TypoScript\TemplateService->fileContent' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-77477-TemplateService-fileContent.rst',
        ],
    ],
    'TYPO3\CMS\Core\TypoScript\TemplateService->removeQueryString' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-74156-TemplateServicesortedKeyListAndTemplateService-removeQueryString.rst',
        ],
    ],
    'TYPO3\CMS\Core\TypoScript\TemplateService->sortedKeyList' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-74156-TemplateServicesortedKeyListAndTemplateService-removeQueryString.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Persistence\Generic\Qom\Comparison->getParameterIdentifier' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-77502-ExtbasePreparsingOfQueriesRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Persistence\Generic\Qom\Comparison->setParameterIdentifier' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-77502-ExtbasePreparsingOfQueriesRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings->getUsePreparedStatement' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-77432-ExtbasePreparedStatementQueryOption.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings->getUseQueryCache' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Breaking-77460-ExtbaseQueryCacheRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings->usePreparedStatement' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-77432-ExtbasePreparedStatementQueryOption.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings->useQueryCache' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Breaking-77460-ExtbaseQueryCacheRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Fluid\Core\Rendering\RenderingContext->getObjectManager' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-79972-DeprecatedFluidOverrides.rst',
        ],
    ],
    'TYPO3\CMS\Fluid\Core\Rendering\RenderingContext->getTemplateVariableContainer' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-69863-DeprecateGetTemplateVariableContainerFunction.rst',
        ],
    ],
    'TYPO3\CMS\Fluid\Core\Rendering\RenderingContext->injectObjectManager' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Fluid\Core\Rendering\RenderingContext->setLegacyMode' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-79972-DeprecatedFluidOverrides.rst',
        ],
    ],
    'TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement->onSubmit' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Important-80301-ExtFormCleanupAndCallbackMigration.rst',
        ],
    ],
    'TYPO3\CMS\Form\Domain\Model\FormElements\AbstractSection->onSubmit' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Important-80301-ExtFormCleanupAndCallbackMigration.rst',
        ],
    ],
    'TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload->onBuildingFinished' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Important-80301-ExtFormCleanupAndCallbackMigration.rst',
        ],
    ],
    'TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface->onSubmit' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Important-80301-ExtFormCleanupAndCallbackMigration.rst',
        ],
    ],
    'TYPO3\CMS\Form\Domain\Model\FormElements\UnknownFormElement->onSubmit' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Important-80301-ExtFormCleanupAndCallbackMigration.rst',
        ],
    ],
    'TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable->beforeRendering' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Important-80301-ExtFormCleanupAndCallbackMigration.rst',
        ],
    ],
    'TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable->onBuildingFinished' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Important-80301-ExtFormCleanupAndCallbackMigration.rst',
        ],
    ],
    'TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface->onBuildingFinished' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Important-80301-ExtFormCleanupAndCallbackMigration.rst',
        ],
    ],
    'TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface->beforeRendering' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Important-80301-ExtFormCleanupAndCallbackMigration.rst',
        ],
    ],
    'TYPO3\CMS\Form\Domain\Runtime\FormRuntime->beforeRendering' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Important-80301-ExtFormCleanupAndCallbackMigration.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication->record_registration' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-70316-FrontendBasketWithRecs.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\AbstractContentObject->getContentObject' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-68748-DeprecateAbstractContentObjectgetContentObject.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->URLqMark' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80510-ContentObjectRenderer-URLqMark.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->clearTSProperties' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80532-GifBuilder-relatedMethodsInContentObjectRenderer.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->fileResource' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-77524-DeprecatedMethodFileResourceOfContentObjectRenderer.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->fillInMarkerArray' => [
        // Note: This was moved from ContentObjectRenderer to TemplateService
        // If usage is adapted to TemplateService, it will still match (no class instance check)
        // And will turn into a false positive match.
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 6,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80527-Marker-relatedMethodsInContentObjectRenderer.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->getClosestMPvalueForPage' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->getSubpart' => [
        // Note: This was moved from ContentObjectRenderer to TemplateService
        // If usage is adapted to TemplateService, it will still match (no class instance check)
        // And will turn into a false positive match.
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80527-Marker-relatedMethodsInContentObjectRenderer.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->getWhere' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->gifBuilderTextBox' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80532-GifBuilder-relatedMethodsInContentObjectRenderer.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->includeLibs' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-73514-IncludeLibraryMethods.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->linebreaks' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80532-GifBuilder-relatedMethodsInContentObjectRenderer.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->processParams' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-72851-DeprecateSomeFunctionsNotInUseAnymoreInTheCore.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->removeBadHTML' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-15415-DeprecateRemoveBadHTML.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->stdWrap_fontTag' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-76383-DeprecateFontTag.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->stdWrap_removeBadHTML' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-15415-DeprecateRemoveBadHTML.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->substituteMarker' => [
        // Note: This was moved from ContentObjectRenderer to TemplateService
        // If usage is adapted to TemplateService, it will still match (no class instance check)
        // And will turn into a false positive match.
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80527-Marker-relatedMethodsInContentObjectRenderer.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->substituteMarkerAndSubpartArrayRecursive' => [
        // Note: This was moved from ContentObjectRenderer to TemplateService
        // If usage is adapted to TemplateService, it will still match (no class instance check)
        // And will turn into a false positive match.
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 5,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80527-Marker-relatedMethodsInContentObjectRenderer.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->substituteMarkerArray' => [
        // Note: This was moved from ContentObjectRenderer to TemplateService
        // If usage is adapted to TemplateService, it will still match (no class instance check)
        // And will turn into a false positive match.
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 5,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80527-Marker-relatedMethodsInContentObjectRenderer.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->substituteMarkerArrayCached' => [
        // Note: This was moved from ContentObjectRenderer to TemplateService
        // If usage is adapted to TemplateService, it will still match (no class instance check)
        // And will turn into a false positive match.
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 4,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80527-Marker-relatedMethodsInContentObjectRenderer.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->substituteMarkerInObject' => [
        // Note: This was moved from ContentObjectRenderer to TemplateService
        // If usage is adapted to TemplateService, it will still match (no class instance check)
        // And will turn into a false positive match.
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80527-Marker-relatedMethodsInContentObjectRenderer.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->substituteSubpart' => [
        // Note: This was moved from ContentObjectRenderer to TemplateService
        // If usage is adapted to TemplateService, it will still match (no class instance check)
        // And will turn into a false positive match.
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 4,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80527-Marker-relatedMethodsInContentObjectRenderer.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->substituteSubpartArray' => [
        // Note: This was moved from ContentObjectRenderer to TemplateService
        // If usage is adapted to TemplateService, it will still match (no class instance check)
        // And will turn into a false positive match.
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80527-Marker-relatedMethodsInContentObjectRenderer.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->beLoginLinkIPList' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80444-TypoScriptFrontendController-BeLoginLinkIPList.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->csConv' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-75327-TSFE-csConvObjAndTSFE-csConv.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->encryptCharcode' => [
        'numberOfMandatoryArguments' => 4,
        'maximumNumberOfArguments' => 4,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-79858-TSFE-relatedPropertiesAndMethods.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->encryptEmail' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-79858-TSFE-relatedPropertiesAndMethods.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->generatePage_whichScript' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-79858-TSFE-relatedPropertiesAndMethods.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->includeLibraries' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-73514-IncludeLibraryMethods.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->setParseTime' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Page\PageRepository->getPathFromRootline' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-77405-PageRepository-getPathFromRootline.rst',
        ],
    ],
    'TYPO3\CMS\IndexedSearch\Indexer->includeCrawlerClass' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78679-CrawlerInclusionViaRequire_onceInIndexedSearch.rst',
        ],
    ],
    'TYPO3\CMS\Lang\LanguageService->addModuleLabels' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-72340-MovedModuleLabelsFromLanguageServiceToModuleLoader.rst',
        ],
    ],
    'TYPO3\CMS\Lang\LanguageService->getParserFactory' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-73482-LANG-csConvObjAndLANG-parserFactory.rst',
        ],
    ],
    'TYPO3\CMS\Lang\LanguageService->makeEntities' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-71916-LanguageService-makeEntities.rst',
        ],
    ],
    'TYPO3\CMS\Lang\LanguageService->overrideLL' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-72496-DeprecatedLANG-overrideLL.rst',
        ],
    ],
    'TYPO3\CMS\Lowlevel\Utility\ArrayBrowser->wrapValue' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80440-EXTlowlevelArrayBrowser-wrapValue.rst',
        ],
    ],
    'TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList->makeQueryArray' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 4,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-76259-DeprecateMethodMakeQueryArrayOfAbstractDatabaseRecordList.rst',
        ],
    ],
    'TYPO3\CMS\Taskcenter\Controller\TaskModuleController->printContent' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80445-DeprecatePrintContentMethods.rst',
        ],
    ],
    'TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController->processTemplateRowAfterLoading' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-81171-EditAbilityOfTypoScriptTemplateInEXTtstemplateRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController->processTemplateRowBeforeSaving' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-81171-EditAbilityOfTypoScriptTemplateInEXTtstemplateRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup->setHideInList' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-81534-DatabaseFieldBe_groupshide_in_listsDropped.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup->getHideInList' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-81534-DatabaseFieldBe_groupshide_in_listsDropped.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Template\DocumentTemplate->formWidth' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-81540-DeprecateDocumentTemplateformWidth.rst',
        ],
    ],
    'TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility->sendSqlDumpFileToBrowserAndDelete' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-82148-DownloadSQLDumpDroppedInEM.rst',
        ],
    ],
    'TYPO3\CMS\Backend\History\RecordHistory->main' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-55298-DecoupledHistoryFunctionality.rst',
        ],
    ],
    'TYPO3\CMS\Backend\History\RecordHistory->toggleHighlight' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-55298-DecoupledHistoryFunctionality.rst',
        ],
    ],
    'TYPO3\CMS\Backend\History\RecordHistory->displaySettings' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-55298-DecoupledHistoryFunctionality.rst',
        ],
    ],
    'TYPO3\CMS\Backend\History\RecordHistory->displayHistory' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-55298-DecoupledHistoryFunctionality.rst',
        ],
    ],
    'TYPO3\CMS\Backend\History\RecordHistory->displayMultipleDiff' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-55298-DecoupledHistoryFunctionality.rst',
        ],
    ],
    'TYPO3\CMS\Backend\History\RecordHistory->renderDiff' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-55298-DecoupledHistoryFunctionality.rst',
        ],
    ],
    'TYPO3\CMS\Backend\History\RecordHistory->generateTitle' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-55298-DecoupledHistoryFunctionality.rst',
        ],
    ],
    'TYPO3\CMS\Backend\History\RecordHistory->createRollbackLink' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-55298-DecoupledHistoryFunctionality.rst',
        ],
    ],
    'TYPO3\CMS\Backend\History\RecordHistory->linkPage' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 4,
        'restFiles' => [
            'Breaking-55298-DecoupledHistoryFunctionality.rst',
        ],
    ],
    'TYPO3\CMS\Backend\History\RecordHistory->removeFilefields' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-55298-DecoupledHistoryFunctionality.rst',
        ],
    ],
    'TYPO3\CMS\Backend\History\RecordHistory->resolveElement' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-55298-DecoupledHistoryFunctionality.rst',
        ],
    ],
    'TYPO3\CMS\Backend\History\RecordHistory->resolveShUid' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-55298-DecoupledHistoryFunctionality.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\ContentElement\ElementHistoryController->main' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-55298-DecoupledHistoryFunctionality.rst',
        ],
    ],
    'TYPO3\CMS\Core\TypoScript\ConfigurationForm->ext_makeHelpInformationForCategory' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-82398-RemoveSpecialConstantTSConstantEditor.rst',
        ],
    ],
    'TYPO3\CMS\Core\TypoScript\ConfigurationForm->ext_displayExample' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-82398-RemoveSpecialConstantTSConstantEditor.rst',
        ],
    ],
    'TYPO3\CMS\Core\TypoScript\ExtendedTemplateService->ext_getTSCE_config' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-82398-RemoveSpecialConstantTSConstantEditor.rst',
        ],
    ],
    'TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationCategory->setHighlightText' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-82398-RemoveSpecialConstantTSConstantEditor.rst',
        ],
    ],
    'TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationCategory->getHighlightText' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-82398-RemoveSpecialConstantTSConstantEditor.rst',
        ],
    ],
    'TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem->setHighlight' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-82398-RemoveSpecialConstantTSConstantEditor.rst',
        ],
    ],
    'TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem->getHighlight' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-82398-RemoveSpecialConstantTSConstantEditor.rst',
        ],
    ],
    'TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository->findByPidsAndAuthor' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-82506-RemoveBackendUserRepositoryInjectionInNoteController.rst',
        ],
    ],
    'TYPO3\CMS\Core\Service\AbstractService->devLog' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-52694-DeprecatedGeneralUtilitydevLog.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->sendRedirect' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-82572-RDCTFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->updateMD5paramsRecord' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-82572-RDCTFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap->ensureClassLoadingInformationExists' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Version\DataHandler\CommandMap->setWorkspacesConsiderReferences' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-82701-AlwaysConsiderPublishingReferencesInWorkspaces.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->readLLfile' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-81217-TSFE-relatedLanguageMethods.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getLLL' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-81217-TSFE-relatedLanguageMethods.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->initLLvars' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-81217-TSFE-relatedLanguageMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Page\PageRenderer->addMetaTag' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-81464-AddAPIForMetaTagManagement.rst',

        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\PropertyReflection->isTaggedWith' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-57594-OptimizeReflectionServiceCacheHandling.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\PropertyReflection->getTagsValues' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-57594-OptimizeReflectionServiceCacheHandling.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\PropertyReflection->getTagValues' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-57594-OptimizeReflectionServiceCacheHandling.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\ClassSchema->addProperty' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 4,
        'restFiles' => [
            'Deprecation-57594-OptimizeReflectionServiceCacheHandling.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\ClassSchema->setModelType' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-57594-OptimizeReflectionServiceCacheHandling.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\ClassSchema->getModelType' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-57594-OptimizeReflectionServiceCacheHandling.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\ClassSchema->setUuidPropertyName' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-57594-OptimizeReflectionServiceCacheHandling.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\ClassSchema->getUuidPropertyName' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-57594-OptimizeReflectionServiceCacheHandling.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\ClassSchema->markAsIdentityProperty' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-57594-OptimizeReflectionServiceCacheHandling.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\ClassSchema->getIdentityProperties' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-57594-OptimizeReflectionServiceCacheHandling.rst',
        ],
    ],
    'TYPO3\CMS\Scheduler\Scheduler->scheduleNextSchedulerRunUsingAtDaemon' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-82832-UseAtDaemonDroppedFromScheduler.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getDomainNameForPid' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-82926-DomainRelatedApiMethodInTSFE.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider->getTranslationTable' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-82445-PageTranslationRelatedFunctionality.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider->isTranslationInOwnTable' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-82445-PageTranslationRelatedFunctionality.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider->foreignTranslationTable' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-82445-PageTranslationRelatedFunctionality.rst',
        ],
    ],
    'TYPO3\CMS\Core\DataHandling\DataHandler->newlog2' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 5,
        'restFiles' => [
            'Deprecation-83121-LoggingMethodDataHandler-newlog2.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Page\PageRepository->deleteClause' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-83118-DeleteClauseMethods.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->stdWrap_spaceBefore' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-83124-RemoveStdWrapOptionsSpaceSpaceBeforeSpaceAfter.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->stdWrap_spaceAfter' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-83124-RemoveStdWrapOptionsSpaceSpaceBeforeSpaceAfter.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->stdWrap_space' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-83124-RemoveStdWrapOptionsSpaceSpaceBeforeSpaceAfter.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\BackendController->loadResourcesForRegisteredNavigationComponents' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-82426-ExtJSAndExtDirectRemoval.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Tree\Pagetree\ExtdirectTreeDataProvider->getNodeTypes' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-82426-ExtJSAndExtDirectRemoval.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Tree\Pagetree\ExtdirectTreeDataProvider->loadResources' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-82426-ExtJSAndExtDirectRemoval.rst',
        ],
    ],
    'TYPO3\CMS\Core\Page\PageRenderer->setExtJsPath' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-82426-ExtJSAndExtDirectRemoval.rst',
        ],
    ],
    'TYPO3\CMS\Core\Page\PageRenderer->getExtJsPath' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-82426-ExtJSAndExtDirectRemoval.rst',
        ],
    ],
    'TYPO3\CMS\Core\Page\PageRenderer->addExtOnReadyCode' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-82426-ExtJSAndExtDirectRemoval.rst',
        ],
    ],
    'TYPO3\CMS\Core\Page\PageRenderer->addExtDirectCode' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-82426-ExtJSAndExtDirectRemoval.rst',
        ],
    ],
    'TYPO3\CMS\Core\Page\PageRenderer->loadExtJS' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-82426-ExtJSAndExtDirectRemoval.rst',
        ],
    ],
    'TYPO3\CMS\Core\Page\PageRenderer->enableExtJsDebug' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-82426-ExtJSAndExtDirectRemoval.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication->extGetNumberOfCachedPages' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-81460-DeprecateGetByTagOnCacheFrontends.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->stdWrap_TCAselectItem' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-83122-RemovedStdWrapOptionTCAselectItem.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->TCAlookup' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-83122-RemovedStdWrapOptionTCAselectItem.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->clean_directory' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-83256-RemovedLockFilePathFunctionality.rst',
        ],
    ],
    'TYPO3\CMS\Core\TypoScript\TemplateService->printTitle' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-83254-MovedPageGenerationMethodsIntoTSFE.rst',
        ],
    ],
    'TYPO3\CMS\Core\Html\RteHtmlParser->transformStyledATags' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-83252-Link-tagSyntaxProcesssing.rst',
        ],
    ],
    'TYPO3\CMS\Core\Html\RteHtmlParser->TS_links_rte' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-83252-Link-tagSyntaxProcesssing.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->pageUnavailableAndExit' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-83883-PageNotFoundAndErrorHandlingInFrontend.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->pageNotFoundAndExit' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-83883-PageNotFoundAndErrorHandlingInFrontend.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->checkPageUnavailableHandler' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-83883-PageNotFoundAndErrorHandlingInFrontend.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->pageUnavailableHandler' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-83883-PageNotFoundAndErrorHandlingInFrontend.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->pageNotFoundHandler' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-83883-PageNotFoundAndErrorHandlingInFrontend.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->pageErrorHandler' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-83883-PageNotFoundAndErrorHandlingInFrontend.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Tree\View\AbstractTreeView->setDataFromArray' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-83904-ArrayHandlingInAbstractTreeView.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Tree\View\AbstractTreeView->setDataFromTreeArray' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-83904-ArrayHandlingInAbstractTreeView.rst',
        ],
    ],
    'TYPO3\CMS\Filelist\FileFacade->getIcon' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-83942-DeprecatedFileFacadegetIcon.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap->redirectToInstallTool' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Important-83869-RemovedRequestTypeSpecificCodeInBootstrap.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap->registerRequestHandlerImplementation' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Important-83869-RemovedRequestTypeSpecificCodeInBootstrap.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap->resolveRequestHandler' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Important-83869-RemovedRequestTypeSpecificCodeInBootstrap.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap->handleRequest' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Important-83869-RemovedRequestTypeSpecificCodeInBootstrap.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap->sendResponse' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Important-83869-RemovedRequestTypeSpecificCodeInBootstrap.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap->checkLockedBackendAndRedirectOrDie' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Important-83869-RemovedRequestTypeSpecificCodeInBootstrap.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap->checkBackendIpOrDie' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Important-83869-RemovedRequestTypeSpecificCodeInBootstrap.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap->checkSslBackendAndRedirectIfNeeded' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Important-83869-RemovedRequestTypeSpecificCodeInBootstrap.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap->initializeOutputCompression' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Important-83869-RemovedRequestTypeSpecificCodeInBootstrap.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap->sendHttpHeaders' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Important-83869-RemovedRequestTypeSpecificCodeInBootstrap.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap->shutdown' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Important-83869-RemovedRequestTypeSpecificCodeInBootstrap.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap->initializeBackendTemplate' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Important-83869-RemovedRequestTypeSpecificCodeInBootstrap.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap->endOutputBufferingAndCleanPreviousOutput' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Important-83869-RemovedRequestTypeSpecificCodeInBootstrap.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap->getApplicationContext' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Important-83869-RemovedRequestTypeSpecificCodeInBootstrap.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap->getRequestId' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Important-83869-RemovedRequestTypeSpecificCodeInBootstrap.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\View\AdminPanelView->getAdminPanelHeaderData' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84118-VariousPublicMethodsOfAdminPanelViewDeprecated.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\View\AdminPanelView->isAdminModuleEnabled' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84118-VariousPublicMethodsOfAdminPanelViewDeprecated.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\View\AdminPanelView->saveConfigOptions' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84118-VariousPublicMethodsOfAdminPanelViewDeprecated.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\View\AdminPanelView->extGetFeAdminValue' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-84118-VariousPublicMethodsOfAdminPanelViewDeprecated.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\View\AdminPanelView->forcePreview' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84118-VariousPublicMethodsOfAdminPanelViewDeprecated.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\View\AdminPanelView->isAdminModuleOpen' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84118-VariousPublicMethodsOfAdminPanelViewDeprecated.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\View\AdminPanelView->extGetHead' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84118-VariousPublicMethodsOfAdminPanelViewDeprecated.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\View\AdminPanelView->linkSectionHeader' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84118-VariousPublicMethodsOfAdminPanelViewDeprecated.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\View\AdminPanelView->extGetItem' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 5,
        'restFiles' => [
            'Deprecation-84118-VariousPublicMethodsOfAdminPanelViewDeprecated.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Tree\View\ElementBrowserFolderTreeView->ext_isLinkable' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84145-DeprecateExt_isLinkable.rst'
        ],
    ],
    'TYPO3\CMS\Core\Package\PackageManager->injectDependencyResolver' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84109-DeprecateDependencyResolver.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\EditDocumentController->preInit' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84195-ProtectedMethodsAndPropertiesInEditDocumentController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\EditDocumentController->doProcessData' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84195-ProtectedMethodsAndPropertiesInEditDocumentController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\EditDocumentController->processData' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84195-ProtectedMethodsAndPropertiesInEditDocumentController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\EditDocumentController->makeEditForm' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84195-ProtectedMethodsAndPropertiesInEditDocumentController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\EditDocumentController->compileForm' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84195-ProtectedMethodsAndPropertiesInEditDocumentController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\EditDocumentController->shortCutLink' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84195-ProtectedMethodsAndPropertiesInEditDocumentController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\EditDocumentController->openInNewWindowLink' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84195-ProtectedMethodsAndPropertiesInEditDocumentController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\EditDocumentController->languageSwitch' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-84195-ProtectedMethodsAndPropertiesInEditDocumentController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\EditDocumentController->localizationRedirect' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84195-ProtectedMethodsAndPropertiesInEditDocumentController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\EditDocumentController->getLanguages' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84195-ProtectedMethodsAndPropertiesInEditDocumentController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\EditDocumentController->fixWSversioningInEditConf' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84195-ProtectedMethodsAndPropertiesInEditDocumentController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\EditDocumentController->getRecordForEdit' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-84195-ProtectedMethodsAndPropertiesInEditDocumentController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\EditDocumentController->compileStoreDat' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84195-ProtectedMethodsAndPropertiesInEditDocumentController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\EditDocumentController->getNewIconMode' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-84195-ProtectedMethodsAndPropertiesInEditDocumentController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\EditDocumentController->closeDocument' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84195-ProtectedMethodsAndPropertiesInEditDocumentController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\EditDocumentController->setDocument' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-84195-ProtectedMethodsAndPropertiesInEditDocumentController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\FileSystemNavigationFrameController->initPage' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84273-ProtectedMethodsAndPropertiesInFileSystemNavigationFrameController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\LogoutController->logout' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84275-ProtectedMethodInLogoutController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\ContentElement\ElementInformationController->getLabelForTableColumn' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-84284-ProtectedMethodsAndPropertiesInContentElementElementInformationController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\File\EditFileController->target' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84295-UseServerRequestInterfaceInFileEditFileController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\LoginController->makeInterfaceSelectorBox' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84274-ProtectedMethodsAndPropertiesInLoginController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\File\FileUploadController->renderUploadForm' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84326-ProtectedMethodsAndPropertiesInFileUploadController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\File\FileController->initClipboard' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84324-UseServerRequestInterfaceInFileFileController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\File\FileController->finish' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84324-UseServerRequestInterfaceInFileFileController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\SimpleDataHandlerController->initClipboard' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84374-ProtectedMethodsAndPropertiesInSimpleDataHandlerController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\Wizard\TableController->tableWizard' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84338-ProtectedMethodsAndPropertiesInTableController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\Wizard\TableController->getConfigCode' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84338-ProtectedMethodsAndPropertiesInTableController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\Wizard\TableController->getTableHTML' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84338-ProtectedMethodsAndPropertiesInTableController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\Wizard\TableController->changeFunc' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84338-ProtectedMethodsAndPropertiesInTableController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\Wizard\TableController->cfgArray2CfgString' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84338-ProtectedMethodsAndPropertiesInTableController.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Controller\Wizard\TableController->cfgString2CfgArray' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-84338-ProtectedMethodsAndPropertiesInTableController.rst',
        ],
    ],
    'TYPO3\CMS\Rsaauth\RsaEncryptionEncoder->getRsaPublicKey' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84407-AJAXRequestMethodsInRsaEncryptionEncoder.rst',
        ],
    ],
    'TYPO3\CMS\Rsaauth\RsaEncryptionEncoder->getRsaPublicKeyAjaxHandler' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84407-AJAXRequestMethodsInRsaEncryptionEncoder.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Validation\ValidatorResolver->buildMethodArgumentsValidatorConjunctions' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-83475-AggregateValidatorInformationInClassSchema-1.rst',
        ],
    ],
    'TYPO3\CMS\Install\Service\CoreVersionService->getDownloadBaseUrl' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84549-DeprecateMethodsInCoreVersionService.rst',
        ],
    ],
    'TYPO3\CMS\Install\Service\CoreVersionService->isYoungerPatchDevelopmentReleaseAvailable' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84549-DeprecateMethodsInCoreVersionService.rst',
        ],
    ],
    'TYPO3\CMS\Install\Service\CoreVersionService->getYoungestPatchDevelopmentRelease' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84549-DeprecateMethodsInCoreVersionService.rst',
        ],
    ],
    'TYPO3\CMS\Install\Service\CoreVersionService->updateVersionMatrix' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84549-DeprecateMethodsInCoreVersionService.rst',
        ],
    ],
    'TYPO3\CMS\Core\TypoScript\TemplateService->linkData' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 8,
        'restFiles' => [
            'Deprecation-84637-TemplateService-linkDataFunctionalityMovedInPageLinkBuilder.rst',
        ],
    ],
    'TYPO3\CMS\Core\TypoScript\TemplateService->getFromMPmap' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84637-TemplateService-linkDataFunctionalityMovedInPageLinkBuilder.rst',
        ],
    ],
    'TYPO3\CMS\Core\TypoScript\TemplateService->initMPmap_create' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-84637-TemplateService-linkDataFunctionalityMovedInPageLinkBuilder.rst',
        ],
    ],
    'TYPO3\CMS\Backend\FrontendBackendUserAuthentication->initializeAdminPanel' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84641-DeprecatedAdminPanelRelatedMethods.rst',
        ],
    ],
    'TYPO3\CMS\Backend\FrontendBackendUserAuthentication->initializeFrontendEdit' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84641-DeprecatedAdminPanelRelatedMethods.rst',
        ],
    ],
    'TYPO3\CMS\Backend\FrontendBackendUserAuthentication->isFrontendEditingActive' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84641-DeprecatedAdminPanelRelatedMethods.rst',
        ],
    ],
    'TYPO3\CMS\Backend\FrontendBackendUserAuthentication->displayAdminPanel' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84641-DeprecatedAdminPanelRelatedMethods.rst',
        ],
    ],
    'TYPO3\CMS\Backend\FrontendBackendUserAuthentication->isAdminPanelVisible' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84641-DeprecatedAdminPanelRelatedMethods.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->findDomainRecord' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84725-SysDomainResolvingMovedIntoMiddleware.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Page\PageRepository->getDomainStartPage' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-84725-SysDomainResolvingMovedIntoMiddleware.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->connectToDB' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84965-VariousTypoScriptFrontendControllerMethods.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->checkAlternativeIdMethods' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84965-VariousTypoScriptFrontendControllerMethods.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->initializeBackendUser' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84965-VariousTypoScriptFrontendControllerMethods.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->handleDataSubmission' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84965-VariousTypoScriptFrontendControllerMethods.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->setCSS' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-84965-VariousTypoScriptFrontendControllerMethods.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->convPOSTCharset' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-84965-VariousTypoScriptFrontendControllerMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Authentication\BackendUserAuthentication->addTScomment' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84980-BackendUserAuthentication-addTScommentDeprecated.rst',
        ],
    ],
    'TYPO3\CMS\Core\Authentication\BackendUserAuthentication->simplelog' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-84981-BackendUserAuthentication-simplelogDeprecated.rst',
        ],
    ],
    'TYPO3\CMS\Core\Authentication\BackendUserAuthentication->getTSConfigVal' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84993-DeprecateSomeTSconfigRelatedMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Authentication\BackendUserAuthentication->getTSConfigProp' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84993-DeprecateSomeTSconfigRelatedMethods.rst',
        ],
    ],
    'TYPO3\CMS\backend\Tree\View\PagePositionMap->getModConfig' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84993-DeprecateSomeTSconfigRelatedMethods.rst',
        ],
    ],
    'TYPO3\CMS\core\DataHandling\DataHandler->getTCEMAIN_TSconfig' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84993-DeprecateSomeTSconfigRelatedMethods.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository->getUsedLanguagesInPageAndColumn' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-84877-MethodsOfLocalizationRepositoryChanged.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Validation\ValidatorResolver->buildSubObjectValidator' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-85005-DeprecateMethodsAndConstantsInValidatorResolver.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Validation\ValidatorResolver->parseValidatorAnnotation' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85005-DeprecateMethodsAndConstantsInValidatorResolver.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Validation\ValidatorResolver->parseValidatorOptions' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85005-DeprecateMethodsAndConstantsInValidatorResolver.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Validation\ValidatorResolver->unquoteString' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85005-DeprecateMethodsAndConstantsInValidatorResolver.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Mvc\Controller\Argument->getValidationResults' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-85012-OnlyValidateMethodParamsIfNeeded.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Mvc\Controller\Arguments->getValidationResults' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-85012-OnlyValidateMethodParamsIfNeeded.rst',
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->parse_charset' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85122-FunctionalityInCharsetConverter.rst'
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->convArray' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 4,
        'restFiles' => [
            'Deprecation-85122-FunctionalityInCharsetConverter.rst'
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->utf8_to_entities' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85122-FunctionalityInCharsetConverter.rst'
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->entities_to_utf8' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85122-FunctionalityInCharsetConverter.rst'
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->crop' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 4,
        'restFiles' => [
            'Deprecation-85122-FunctionalityInCharsetConverter.rst'
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->convCaseFirst' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-85122-FunctionalityInCharsetConverter.rst'
        ],
    ],
    'TYPO3\CMS\Core\Charset\CharsetConverter->utf8_char2byte_pos' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-85122-FunctionalityInCharsetConverter.rst'
        ],
    ],
    'TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver->getCharsetConversion' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-85125-UsagesOfCharsetConverterInCore.rst'
        ],
    ],
    'TYPO3\CMS\Backend\Routing\UriBuilder->buildUriFromModule' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-85113-LegacyBackendModuleRoutingMethods.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getPageShortcut' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 6,
        'restFiles' => [
            'Deprecation-85130-TSFE-getPageShortcutMovedToPageRepository.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\ReflectionService->getClassTagsValues' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85004-DeprecateMethodsInReflectionService.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\ReflectionService->getClassTagValues' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-85004-DeprecateMethodsInReflectionService.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\ReflectionService->getClassPropertyNames' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85004-DeprecateMethodsInReflectionService.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\ReflectionService->hasMethod' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-85004-DeprecateMethodsInReflectionService.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\ReflectionService->getMethodTagsValues' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-85004-DeprecateMethodsInReflectionService.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\ReflectionService->getMethodParameters' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-85004-DeprecateMethodsInReflectionService.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\ReflectionService->getPropertyTagsValues' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-85004-DeprecateMethodsInReflectionService.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\ReflectionService->getPropertyTagValues' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-85004-DeprecateMethodsInReflectionService.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\ReflectionService->isClassTaggedWith' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-85004-DeprecateMethodsInReflectionService.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\ReflectionService->isPropertyTaggedWith' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-85004-DeprecateMethodsInReflectionService.rst',
        ],
    ],
    'TYPO3\CMS\Setup\Controller\SetupModuleController->languageUpdate' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-85196-ProtectSetupModuleController.rst'
        ],
    ],
    'TYPO3\CMS\Setup\Controller\SetupModuleController->simulateUser' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-85196-ProtectSetupModuleController.rst'
        ],
    ],
];
