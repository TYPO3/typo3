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
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::RTEsetup' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 4,
        'restFiles' => [
            'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
            'Deprecation-79341-MethodsRelatedToRichtextConfiguration.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getUpdateSignalCode' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-96136-DeprecateInlineJavaScriptInBackendUpdateSignals.rst',
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
            'Deprecation-75621-GeneralUtilityMethods.rst',
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
            'Deprecation-80449-GeneralUtilityfreetypeDpiComp.rst',
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
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Utility\TypeHandlingUtility::hex2bin' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-87613-DeprecateTYPO3CMSExtbaseUtilityTypeHandlingUtilityhex2bin.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::idnaEncode' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-87894-GeneralUtilityidnaEncode.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
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
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\VersionNumberUtility::raiseVersionNumber' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-88554-DeprecatedMethodsInVersionNumberUtility.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\VersionNumberUtility::convertIntegerToVersionNumber' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-88554-DeprecatedMethodsInVersionNumberUtility.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
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
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-88787-BackendUtilityEditOnClick.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::viewOnClick' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 7,
        'restFiles' => [
            'Important-91123-AvoidUsingBackendUtilityViewOnClick.rst',
            'Deprecation-91806-BackendUtilityViewOnClick.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::getApplicationContext' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-89631-UseEnvironmentAPIToFetchApplicationContext.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getRawPagesTSconfig' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-89718-LegacyPageTSconfigParsingLowlevelAPI.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::TYPO3_copyRightNotice' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-89756-BackendUtilityTYPO3_copyRightNotice.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Resource\ResourceFactory::getInstance' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-90260-ResourceFactorygetInstancePseudo-factory.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::isRunningOnCgiServerApi' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-90800-GeneralUtilityisRunningOnCgiServerApi.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::verifyFilenameAgainstDenyPattern' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-90147-UnifiedFileNameValidator.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::IPv6Hex2Bin' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-91001-VariousMethodsWithinGeneralUtility.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::IPv6Bin2Hex' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-91001-VariousMethodsWithinGeneralUtility.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::compressIPv6' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-91001-VariousMethodsWithinGeneralUtility.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-91001-VariousMethodsWithinGeneralUtility.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::linkThisUrl' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-91001-VariousMethodsWithinGeneralUtility.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::flushDirectory' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-91001-VariousMethodsWithinGeneralUtility.rst',
            'Breaking-91473-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::hideIfDefaultLanguage' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-92551-GeneralUtilityMethodsRelatedToPagesl18n_cfgBehavior.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::hideIfNotTranslated' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-92551-GeneralUtilityMethodsRelatedToPagesl18n_cfgBehavior.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::fixVersioningPid' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-92598-Workspace-relatedMethodsFixVersioningPid.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::uniqueList' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-92607-DeprecatedGeneralUtilityuniqueList.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Database\RelationHandler::isOnSymmetricSide' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-93080-RelationHandlerInternalsProtected.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::compileSelectedGetVarsFromArray' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-94252-DeprecatedGeneralUtilitycompileSelectedGetVarsFromArray.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::stdAuthCode' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-94309-DeprecatedGeneralUtilitystdAuthCode.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::rmFromList' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-94311-DeprecatedGeneralUtilityrmFromList.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-94684-GeneralUtilityShortMD5.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\HttpUtility::redirect' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-94316-DeprecatedHTTPHeaderManipulatingMethodsFromHttpUtility.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\HttpUtility::setResponseCode' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-94316-DeprecatedHTTPHeaderManipulatingMethodsFromHttpUtility.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\HttpUtility::setResponseCodeAndExit' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-94316-DeprecatedHTTPHeaderManipulatingMethodsFromHttpUtility.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Localization\LanguageService::create' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-94414-DeprecateLanguageServiceContainerEntry.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Localization\LanguageService::createFromUserPreferences' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-94414-DeprecateLanguageServiceContainerEntry.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Localization\LanguageService::createFromSiteLanguage' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-94414-DeprecateLanguageServiceContainerEntry.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 5,
        'restFiles' => [
            'Deprecation-85613-CategoryRegistry.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::softRefParserObj' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-94687-SoftReferenceIndex.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::explodeSoftRefParserList' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-94687-SoftReferenceIndex.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::minifyJavaScript' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-94791-GeneralUtilityminifyJavaScript.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-95257-GeneralUtilityisFirstPartOfStr.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\StringUtility::beginsWith' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-95293-StringUtilitystartsWithAndStringUtilityendsWith.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\StringUtility::endsWith' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-95293-StringUtilitystartsWithAndStringUtilityendsWith.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::getInstance' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-95326-VariousGetInstanceStaticMethodsOnSingletonInterfaces.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Resource\Index\FileIndexRepository::getInstance' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-95326-VariousGetInstanceStaticMethodsOnSingletonInterfaces.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Resource\Index\MetaDataRepository::getInstance' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-95326-VariousGetInstanceStaticMethodsOnSingletonInterfaces.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry::getInstance' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-95326-VariousGetInstanceStaticMethodsOnSingletonInterfaces.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Resource\Rendering\RendererRegistry::getInstance' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-95326-VariousGetInstanceStaticMethodsOnSingletonInterfaces.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry::getInstance' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-95326-VariousGetInstanceStaticMethodsOnSingletonInterfaces.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Form\Service\TranslationService::getInstance' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-95326-VariousGetInstanceStaticMethodsOnSingletonInterfaces.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\T3editor\Registry\AddonRegistry::getInstance' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-95326-VariousGetInstanceStaticMethodsOnSingletonInterfaces.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\T3editor\Registry\ModeRegistry::getInstance' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-95326-VariousGetInstanceStaticMethodsOnSingletonInterfaces.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-95367-GeneralUtilityisAbsPath.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::isAllowedHostHeaderValue' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-95395-GeneralUtilityIsAllowedHostHeaderValueAndTrustedHostsPatternConstants.rst',
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Utility\ExtensionUtility::getControllerClassName' => [
        'numberOfMandatoryArguments' => 4,
        'maximumNumberOfArguments' => 4,
        'restFiles' => [
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Utility\ExtensionUtility::resolveVendorFromExtensionAndControllerClassName' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Utility\ExtensionUtility::resolveControllerAliasFromControllerClassName' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-94117-RegisterExtbaseTypeConvertersAsServices.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::isModuleSetInTBE_MODULES' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-96733-DeprecatedTBE_MODULESRelatedFunctionality.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 5,
        'restFiles' => [
            'Breaking-96733-RemovedSupportForModuleHandlingBasedOnTBE_MODULES.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addNavigationComponent' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Breaking-96733-RemovedSupportForModuleHandlingBasedOnTBE_MODULES.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addCoreNavigationComponent' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Breaking-96733-RemovedSupportForModuleHandlingBasedOnTBE_MODULES.rst',
        ],
    ],
    'TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 6,
        'restFiles' => [
            'Breaking-96733-RemovedSupportForModuleHandlingBasedOnTBE_MODULES.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getFuncInput' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 6,
        'restFiles' => [
            'Breaking-96829-RemovedBackendUtility-getFuncInput.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::cshItem' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 4,
        'restFiles' => [
            'Deprecation-97312-DeprecateCSH-relatedMethods.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-97312-DeprecateCSH-relatedMethods.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getPreviewUrl' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 7,
        'restFiles' => [
            'Deprecation-97544-PreviewURIGenerationRelatedFunctionalityInBackendUtility.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 4,
        'restFiles' => [
            'Deprecation-98479-DeprecatedFileReferenceRelatedFunctionality.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Breaking-98487-GLOBALSPAGES_TYPESRemoved.rst',
            'Deprecation-98487-ExtensionManagementUtilityallowTableOnStandardPages.rst',
            'Feature-98487-TCAOptionCtrlsecurityignorePageTypeRestriction.rst',
        ],
    ],
    'TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 99,
        'restFiles' => [
            'Deprecation-99098-StaticUsageOfFormProtectionFactory.rst',
        ],
    ],
    'TYPO3\CMS\Core\FormProtection\FormProtectionFactory::purgeInstances' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 99,
        'restFiles' => [
            'Deprecation-99098-StaticUsageOfFormProtectionFactory.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu' => [
        'numberOfMandatoryArguments' => 4,
        'maximumNumberOfArguments' => 6,
        'restFiles' => [
            'Deprecation-99519-DeprecatedBackendUtilitygetFuncMenu.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getDropdownMenu' => [
        'numberOfMandatoryArguments' => 4,
        'maximumNumberOfArguments' => 6,
        'restFiles' => [
            'Deprecation-99564-DeprecatedBackendUtilityGetDropdownMenu.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck' => [
        'numberOfMandatoryArguments' => 3,
        'maximumNumberOfArguments' => 6,
        'restFiles' => [
            'Deprecation-99579-BackendUtilityGetFuncCheck.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::_GPmerged' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-99615-GeneralUtilityGPMerged.rst',
        ],
    ],
    'TYPO3\CMS\Core\Core\Environment::getBackendPath' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 0,
        'restFiles' => [
            'Deprecation-99638-EnvironmentgetBackendPath.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::_POST' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-99633-GeneralUtilityPOST.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::_GP' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-100053-GeneralUtility_GP.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getRecordToolTip' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-100459-BackendUtilitygetRecordToolTip.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getThumbnailUrl' => [
        'numberOfMandatoryArguments' => 2,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-100597-BackendUtilityMethodsGetThumbnailUrlAndGetLinkToDataHandlerAction.rst',
        ],
    ],
    'TYPO3\CMS\Backend\Utility\BackendUtility::getLinkToDataHandlerAction' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-100597-BackendUtilityMethodsGetThumbnailUrlAndGetLinkToDataHandlerAction.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-100584-GeneralUtilitylinkThisScript.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\GeneralUtility::_GET' => [
        'numberOfMandatoryArguments' => 0,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-100596-GeneralUtility_GET.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\DebugUtility::debugRows' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 2,
        'restFiles' => [
            'Deprecation-100653-DeprecatedSomeMethodsInDebugUtility.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\DebugUtility::printArray' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 1,
        'restFiles' => [
            'Deprecation-100653-DeprecatedSomeMethodsInDebugUtility.rst',
        ],
    ],
    'TYPO3\CMS\Core\Utility\DebugUtility::debugInPopUpWindow' => [
        'numberOfMandatoryArguments' => 1,
        'maximumNumberOfArguments' => 3,
        'restFiles' => [
            'Deprecation-100653-DeprecatedSomeMethodsInDebugUtility.rst',
        ],
    ],
];
