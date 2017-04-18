.. include:: ../../Includes.txt

===================================================
Breaking: #80700 - Deprecated functionality removed
===================================================

See :issue:`80700`

Description
===========

The following PHP classes that have been previously deprecated for v8 have been removed:
* RemoveXSS
* TYPO3\CMS\Backend\Console\Application
* TYPO3\CMS\Backend\Console\CliRequestHandler
* TYPO3\CMS\Core\Controller\CommandLineController
* TYPO3\CMS\Core\Http\AjaxRequestHandler
* TYPO3\CMS\Lowlevel\CleanerCommand

The following PHP class methods that have been previously deprecated for v8 have been removed:
* TYPO3\CMS\Backend\Routing\UriBuilder->buildUriFromAjaxId()
* TYPO3\CMS\Backend\Utility\BackendUtility::getAjaxUrl()
* TYPO3\CMS\Core\Charset\CharsetConverter->convCapitalize()
* TYPO3\CMS\Core\Charset\CharsetConverter->conv_case()
* TYPO3\CMS\Core\Charset\CharsetConverter->euc_char2byte_pos()
* TYPO3\CMS\Core\Charset\CharsetConverter->euc_strlen()
* TYPO3\CMS\Core\Charset\CharsetConverter->euc_strtrunc()
* TYPO3\CMS\Core\Charset\CharsetConverter->euc_substr()
* TYPO3\CMS\Core\Charset\CharsetConverter->getPreferredClientLanguage()
* TYPO3\CMS\Core\Charset\CharsetConverter->strlen()
* TYPO3\CMS\Core\Charset\CharsetConverter->strtrunc()
* TYPO3\CMS\Core\Charset\CharsetConverter->substr()
* TYPO3\CMS\Core\Charset\CharsetConverter->utf8_byte2char_pos()
* TYPO3\CMS\Core\Charset\CharsetConverter->utf8_strlen()
* TYPO3\CMS\Core\Charset\CharsetConverter->utf8_strpos()
* TYPO3\CMS\Core\Charset\CharsetConverter->utf8_strrpos()
* TYPO3\CMS\Core\Charset\CharsetConverter->utf8_strtrunc()
* TYPO3\CMS\Core\Charset\CharsetConverter->utf8_substr()
* TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addExtJSModule()
* TYPO3\CMS\Core\Utility\ExtensionManagementUtility::appendToTypoConfVars()
* TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath()
* TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler()
* TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerExtDirectComponent()
* TYPO3\CMS\Core\Utility\GeneralUtility::array2xml_cs()
* TYPO3\CMS\Core\Utility\GeneralUtility::compat_version()
* TYPO3\CMS\Core\Utility\GeneralUtility::convertMicrotime()
* TYPO3\CMS\Core\Utility\GeneralUtility::csvValues()
* TYPO3\CMS\Core\Utility\GeneralUtility::deHSCentities()
* TYPO3\CMS\Core\Utility\GeneralUtility::flushOutputBuffers()
* TYPO3\CMS\Core\Utility\GeneralUtility::freetypeDpiComp()
* TYPO3\CMS\Core\Utility\GeneralUtility::generateRandomBytes()
* TYPO3\CMS\Core\Utility\GeneralUtility::getMaximumPathLength()
* TYPO3\CMS\Core\Utility\GeneralUtility::getRandomHexString()
* TYPO3\CMS\Core\Utility\GeneralUtility::imageMagickCommand()
* TYPO3\CMS\Core\Utility\GeneralUtility::lcfirst()
* TYPO3\CMS\Core\Utility\GeneralUtility::rawUrlEncodeFP()
* TYPO3\CMS\Core\Utility\GeneralUtility::rawUrlEncodeJS()
* TYPO3\CMS\Core\Utility\GeneralUtility::removeXSS()
* TYPO3\CMS\Core\Utility\GeneralUtility::requireFile()
* TYPO3\CMS\Core\Utility\GeneralUtility::requireOnce()
* TYPO3\CMS\Core\Utility\GeneralUtility::resolveAllSheetsInDS()
* TYPO3\CMS\Core\Utility\GeneralUtility::resolveSheetDefInDS()
* TYPO3\CMS\Core\Utility\GeneralUtility::slashJS()
* TYPO3\CMS\Core\Utility\GeneralUtility::strtolower()
* TYPO3\CMS\Core\Utility\GeneralUtility::strtoupper()
* TYPO3\CMS\Core\Utility\GeneralUtility::xmlGetHeaderAttribs()
* TYPO3\CMS\Lang\LanguageService->addModuleLabels()
* TYPO3\CMS\Lang\LanguageService->getParserFactory()
* TYPO3\CMS\Lang\LanguageService->makeEntities()
* TYPO3\CMS\Lang\LanguageService->overrideLL()

The following methods changed signature according to previous deprecations in v8:
* TYPO3\CMS\Core\Charset\CharsetConverter->euc_char_mapping() - Third and fourth argument dropped
* TYPO3\CMS\Core\Charset\CharsetConverter->sb_char_mapping() - Third and fourth argument dropped
* TYPO3\CMS\Core\Charset\CharsetConverter->utf8_char_mapping() - Second and third argument dropped
* TYPO3\CMS\Core\Localization\LanguageStore->setConfiguration() - Third argument dropped
* TYPO3\CMS\Core\Localization\LocalizationFactory->getParsedData() - Third and fourth argument unused
* TYPO3\CMS\Core\Localization\Parser\AbstractXmlParser->getParsedData() - Third argument dropped
* TYPO3\CMS\Core\Localization\Parser\LocalizationParserInterface->getParsedData() - Third argument dropped
* TYPO3\CMS\Core\Localization\Parser\LocallangXmlParser->getParsedData() - Third argument dropped
* TYPO3\CMS\Core\Page\PageRenderer->addInlineLanguageLabelFile() - Fourth argument dropped
* TYPO3\CMS\Core\Page\PageRenderer->includeLanguageFileForInline() - Fourth argument dropped
* TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction() - Persistent or file prefix in first argument removed
* TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName() - Second and thrird argument dropped
* TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj() - File reference prefix in first argument removed
* TYPO3\CMS\Core\Utility\GeneralUtility::wrapJS() - Second argument dropped
* TYPO3\CMS\Lang\LanguageService->getLL() - Second argument dropped
* TYPO3\CMS\Lang\LanguageService->getLLL() - Third argument dropped
* TYPO3\CMS\Lang\LanguageService->getsL() - Second argument dropped

The following class properties have been dropped:
* TYPO3\CMS\Core\Charset\CharsetConverter->charSetArray
* TYPO3\CMS\Core\Charset\CharsetConverter->fourByteSets
* TYPO3\CMS\Lang\LanguageService->charSet
* TYPO3\CMS\Lang\LanguageService->csConvObj
* TYPO3\CMS\Lang\LanguageService->moduleLabels
* TYPO3\CMS\Lang\LanguageService->parserFactory

The following configuration options are not evaluated anymore:
* $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']

The following entry points have been removed:
* typo3/cli_dispatch.phpsh


Impact
======

Instantiating or requiring the PHP classes, will result in PHP fatal errors.

Calling the entry points via CLI will result in a file not found error.

.. index:: PHP-API
