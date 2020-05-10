<?php

return [
    // Removed methods
    'TYPO3\CMS\Backend\Utility\BackendUtility::getAjaxUrl' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-75340-MethodsRelatedToGeneratingTraditionalBackendAJAXURLs.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getFlexFormDS' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 6,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78581-FlexFormRelatedParsing.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getListViewLink' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-73190-DeprecateBackendUtilitygetListViewLink.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getRecordRaw' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80317-DeprecateBackendUtilityGetRecordRaw.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 9,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-79122-DeprecateBackendUtilitygetRecordsByField.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getSpecConfParametersFromArray' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-79341-MethodsRelatedToRichtextConfiguration.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getSpecConfParts' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78899-FormEngineMethods.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getSQLselectableList' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-72851-DeprecateSomeFunctionsNotInUseAnymoreInTheCore.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::titleAltAttrib' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-72851-DeprecateSomeFunctionsNotInUseAnymoreInTheCore.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::makeConfigForm' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-72851-DeprecateSomeFunctionsNotInUseAnymoreInTheCore.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::processParams' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-72851-DeprecateSomeFunctionsNotInUseAnymoreInTheCore.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::replaceL10nModeFields' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst'
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::RTEsetup' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 4,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-79341-MethodsRelatedToRichtextConfiguration.rst'
        ],
    ],
    'TYPO3\CMS\Core\DataHandling\DataHandler::rmComma' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-79580-MethodsInDataHandlerRelatedToPageDeleteAccess.rst',
        ],
    ],
    'TYPO3\CMS\Core\DataHandling\DataHandler::destPathFromUploadFolder' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80513-DataHandlerVariousMethodsAndMethodArguments.rst',
        ],
    ],
    'TYPO3\CMS\Core\DataHandling\DataHandler::noRecordsFromUnallowedTables' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-79580-MethodsInDataHandlerRelatedToPageDeleteAccess.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\ArrayUtility::inArray' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-79316-DeprecateArrayUtilityinArray.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\ClientUtility::getDeviceType' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-79560-DeprecateClientUtilitygetDeviceType.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addExtJSModule' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 5,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80491-BackendControllerInclusionHooks.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\ExtensionManagementUtility::appendToTypoConfVars' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80583-TYPO3_CONF_VARS_extensionAdded.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78193-ExtensionManagementUtilityextRelPath.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-73352-DeprecateOld-schoolAJAXRequests.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerExtDirectComponent' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 4,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80048-MarkExtJSRelatedAPICallsAsDeprecated.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::pushErrorMessagesToFlashMessageQueue' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-71255-ExtendedFileUtilitypushErrorMessagesToFlashMessageQueue.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::array2xml_cs' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 4,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-75371-Array2xml_cs.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::compat_version' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-75621-GeneralUtilityMethods.rst'
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::convertMicrotime' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-75621-GeneralUtilityMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::csvValues' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80451-DeprecateGeneralUtilitycsvValues.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::deHSCentities' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-75621-GeneralUtilityMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::flushOutputBuffers' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-73516-VariousGeneralUtilityMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::freetypeDpiComp' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80449-GeneralUtilityfreetypeDpiComp.rst'
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::generateRandomBytes' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-73050-DeprecatedRandomGeneratorMethodsInGeneralUtility.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::getMaximumPathLength' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-75621-GeneralUtilityMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::getRandomHexString' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-73050-DeprecatedRandomGeneratorMethodsInGeneralUtility.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::imageMagickCommand' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-73516-VariousGeneralUtilityMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::lcfirst' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-75621-GeneralUtilityMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::rawUrlEncodeFP' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-75621-GeneralUtilityMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::rawUrlEncodeJS' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-75621-GeneralUtilityMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::removeXSS' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-76164-DeprecateRemoveXSS.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::requireFile' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-73067-DeprecateGeneralUtilityrequireOnceAndGeneralUtilityrequireFile.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::requireOnce' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-73067-DeprecateGeneralUtilityrequireOnceAndGeneralUtilityrequireFile.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::resolveAllSheetsInDS' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78581-FlexFormRelatedParsing.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::resolveSheetDefInDS' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-78581-FlexFormRelatedParsing.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::slashJS' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-75621-GeneralUtilityMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::strtolower' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-76804-DeprecateGeneralUtilitystrtoupperStrtolower.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::strtoupper' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-76804-DeprecateGeneralUtilitystrtoupperStrtolower.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::xmlGetHeaderAttribs' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-73516-VariousGeneralUtilityMethods.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Page\PageGenerator::pagegenInit' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-79858-TSFE-relatedPropertiesAndMethods.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Page\PageRepository::getHash' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80524-PageRepositorygetHashAndPageRepositorystoreHash.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Page\PageRepository::storeHash' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 4,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-80524-PageRepositorygetHashAndPageRepositorystoreHash.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-80993-GeneralUtilitygetUserObj.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Utility\EidUtility::initTCA' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-81201-EidUtilityinitTCA.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getListGroupNames' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-81534-BackendUtilitygetListGroupNamesDeprecated.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::makeRedirectUrl' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-82572-RDCTFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getInlineLocalizationMode' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-82438-DeprecationMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedViewHelperAttribute' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-82438-DeprecationMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::getDeprecationLogFileName' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-82438-DeprecationMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-82438-DeprecationMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-82899-ExtensionManagementUtilityMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionKeyByPrefix' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-82899-ExtensionManagementUtilityMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\ExtensionManagementUtility::removeCacheFiles' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-82899-ExtensionManagementUtilityMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\ExtensionManagementUtility::configureModule' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-82902-CustomBackendModuleRegistrationMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getOriginalTranslationTable' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-82445-PageTranslationRelatedFunctionality.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::llXmlAutoFileName' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-83083-GeneralUtilityllXmlAutoFileName.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getHash' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-83116-CachingFrameworkWrapperMethodsInBackendUtility.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::storeHash' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-83116-CachingFrameworkWrapperMethodsInBackendUtility.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-83118-DeleteClauseMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Page\PageGenerator::generatePageTitle' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-83254-MovedPageGenerationMethodsIntoTSFE.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Page\PageGenerator::isAllowedLinkVarValue' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-83254-MovedPageGenerationMethodsIntoTSFE.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Mvc\Controller\ActionController::getActionMethodParameters' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 4,
        'restFiles' => [
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-83475-AggregateValidatorInformationInClassSchema-2.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getPidForModTSconfig' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-84994-BackendUtilitygetPidForModTSconfigDeprecated.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-84993-DeprecateSomeTSconfigRelatedMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::unsetMenuItems' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-84993-DeprecateSomeTSconfigRelatedMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::isUsageEnabled' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85027-SaltedPasswordsRelatedMethodsAndClasses.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::arrayToLogString' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-85086-GeneralUtilityArrayToLogString.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\PhpOptionsUtility::isSessionAutoStartEnabled' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-85102-PhpOptionsUtility.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\PhpOptionsUtility::getIniValueBoolean' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85102-PhpOptionsUtility.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-85113-LegacyBackendModuleRoutingMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::shortcutExists' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-84414-BackendUtilityshortcutExists.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::getHostname' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85759-GeneralUtilitygetHostName.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::unQuoteFilenames' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-85760-GeneralUtilityunQuoteFilenames.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-85796-SaltedPasswordsCleanups.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::determineSaltingHashingMethod' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-85796-SaltedPasswordsCleanups.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::setPreferredHashingMethod' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85796-SaltedPasswordsCleanups.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::getNumberOfBackendUsersWithInsecurePassword' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-85796-SaltedPasswordsCleanups.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController::renderList' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-81430-TypoScriptTemplateModuleControllerrenderList.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap::usesComposerClassLoading' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-85821-BootstrapMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap::getInstance' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-85821-BootstrapMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap::checkIfEssentialConfigurationExists' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85821-BootstrapMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap::loadConfigurationAndInitialize' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-85821-BootstrapMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap::populateLocalConfiguration' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85821-BootstrapMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap::disableCoreCache' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-85821-BootstrapMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap::initializeCachingFramework' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85821-BootstrapMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Bootstrap::setFinalCachingFrameworkCacheConfiguration' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85821-BootstrapMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Page\PageGenerator::renderContent' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-85822-PageGenerator.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Page\PageGenerator::renderContentWithHeader' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85822-PageGenerator.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Page\PageGenerator::inline2TempFile' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-85822-PageGenerator.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getTCAtypes' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-85836-BackendUtilitygetTCAtypes.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::clientInfo' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85858-GeneralUtilityclientInfo.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Utility\EidUtility::initLanguage' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85878-EidUtilityAndVariousTSFEMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Utility\EidUtility::initFeUser' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-85878-EidUtilityAndVariousTSFEMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Frontend\Utility\EidUtility::initExtensionTCA' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85878-EidUtilityAndVariousTSFEMethods.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getDomainStartPage' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-85892-VariousMethodsRegardingSysDomainResolving.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::firstDomainRecord' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-85892-VariousMethodsRegardingSysDomainResolving.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::_GETset' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-86389-GeneralUtility_GETsetAndTSFE-mergingWithGetVars.rst',
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Reflection\ObjectAccess::buildSetterMethodName' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-87332-AvoidRuntimeReflectionCallsInObjectAccess.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Utility\TypeHandlingUtility::hex2bin' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-87613-DeprecateTYPO3CMSExtbaseUtilityTypeHandlingUtilityhex2bin.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::idnaEncode' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-87894-GeneralUtilityidnaEncode.rst',
        ],
    ],
    'TYPO3\CMS\Core\Context\LanguageAspectFactory::createFromTypoScript' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\VersionNumberUtility::splitVersionRange' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-88554-DeprecatedMethodsInVersionNumberUtility.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\VersionNumberUtility::raiseVersionNumber' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-88554-DeprecatedMethodsInVersionNumberUtility.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\VersionNumberUtility::convertIntegerToVersionNumber' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-88554-DeprecatedMethodsInVersionNumberUtility.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Utility\ClassNamingUtility::translateModelNameToValidatorName' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-87957-DoNotMagicallyRegisterValidators.rst',
        ],
    ],
    'TYPO3\CMS\Core\Localization\Locales::initialize' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-88569-LocalesinitializeInFavorOfRegularSingletonInstance.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getViewDomain' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-88499-BackendUtilitygetViewDomain.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-88787-BackendUtilityEditOnClick.rst'
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::getApplicationContext' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-89631-UseEnvironmentAPIToFetchApplicationContext.rst'
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getRawPagesTSconfig' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-89718-LegacyPageTSconfigParsingLowlevelAPI.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::TYPO3_copyRightNotice' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-89756-BackendUtilityTYPO3_copyRightNotice.rst',
        ],
    ],
    'TYPO3\CMS\Core\Resource\ResourceFactory::getInstance' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-90260-ResourceFactorygetInstancePseudo-factory.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::isRunningOnCgiServerApi' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-90800-GeneralUtilityisRunningOnCgiServerApi.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::verifyFilenameAgainstDenyPattern' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-90147-UnifiedFileNameValidator.rst'
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::IPv6Hex2Bin' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-91001-VariousMethodsWithinGeneralUtility.rst'
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::IPv6Bin2Hex' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-91001-VariousMethodsWithinGeneralUtility.rst'
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::compressIPv6' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-91001-VariousMethodsWithinGeneralUtility.rst'
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-91001-VariousMethodsWithinGeneralUtility.rst'
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::linkThisUrl' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-91001-VariousMethodsWithinGeneralUtility.rst'
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::flushDirectory' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-91001-VariousMethodsWithinGeneralUtility.rst'
        ],
    ],
];
