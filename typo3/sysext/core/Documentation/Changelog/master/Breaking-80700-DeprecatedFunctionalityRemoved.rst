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
* TYPO3\CMS\Core\Messaging\AbstractStandaloneMessage
* TYPO3\CMS\Core\Messaging\ErrorpageMessage
* TYPO3\CMS\Core\TimeTracker\NullTimeTracker
* TYPO3\CMS\Extbase\Utility\ArrayUtility
* TYPO3\CMS\Frontend\Page\FramesetRenderer
* TYPO3\CMS\Lowlevel\CleanerCommand

The following PHP class methods that have been previously deprecated for v8 have been removed:
* TYPO3\CMS\Backend\Routing\UriBuilder->buildUriFromAjaxId()
* TYPO3\CMS\Backend\Utility\BackendUtility::getAjaxUrl()
* TYPO3\CMS\Backend\Utility\BackendUtility::getFlexFormDS()
* TYPO3\CMS\Backend\Utility\BackendUtility::getListViewLink()
* TYPO3\CMS\Backend\Utility\BackendUtility::getRecordRaw()
* TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField()
* TYPO3\CMS\Backend\Utility\BackendUtility::getSpecConfParametersFromArray()
* TYPO3\CMS\Backend\Utility\BackendUtility::getSpecConfParts()
* TYPO3\CMS\Backend\Utility\BackendUtility::getSQLselectableList()
* TYPO3\CMS\Backend\Utility\BackendUtility::titleAltAttrib()
* TYPO3\CMS\Backend\Utility\BackendUtility::makeConfigForm()
* TYPO3\CMS\Backend\Utility\BackendUtility::processParams()
* TYPO3\CMS\Backend\Utility\BackendUtility::replaceL10nModeFields()
* TYPO3\CMS\Backend\Utility\BackendUtility::RTEsetup()
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
* TYPO3\CMS\Core\TypoScript\TemplateService->splitConfArray()
* TYPO3\CMS\Core\TypoScript\TemplateService->fileContent()
* TYPO3\CMS\Core\TypoScript\TemplateService->removeQueryString()
* TYPO3\CMS\Core\TypoScript\TemplateService->sortedKeyList()
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
* TYPO3\CMS\Fluid\Core\Rendering\RenderingContext->getObjectManager()
* TYPO3\CMS\Fluid\Core\Rendering\RenderingContext->getTemplateVariableContainer()
* TYPO3\CMS\Fluid\Core\Rendering\RenderingContext->injectObjectManager()
* TYPO3\CMS\Fluid\Core\Rendering\RenderingContext->setLegacyMode()
* TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication->record_registration()
* TYPO3\CMS\Frontend\ContentObject\AbstractContentObject->getContentObject()
* TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->URLqMark()
* TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->clearTSProperties()
* TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->fileResource()
* TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->fillInMarkerArray()
* TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->getSubpart()
* TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->getWhere()
* TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->gifBuilderTextBox()
* TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->includeLibs()
* TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->linebreaks()
* TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->processParams()
* TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->removeBadHTML()
* TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->stdWrap_fontTag()
* TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->stdWrap_removeBadHTML()
* TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->substituteMarker()
* TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->substituteMarkerAndSubpartArrayRecursive()
* TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->substituteMarkerArray()
* TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->substituteMarkerArrayCached()
* TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->substituteMarkerInObject()
* TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->substituteSubpart()
* TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->substituteSubpartArray()
* TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->beLoginLinkIPList()
* TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->csConv()
* TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->encryptCharcode()
* TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->encryptEmail()
* TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->generatePage_whichScript()
* TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->includeLibraries()
* TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->setParseTime()
* TYPO3\CMS\Frontend\Page\PageGenerator::pagegenInit()
* TYPO3\CMS\Frontend\Page\PageRepository->getPathFromRootline()
* TYPO3\CMS\Frontend\Page\PageRepository::getHash()
* TYPO3\CMS\Frontend\Page\PageRepository::storeHash()
* TYPO3\CMS\Lang\LanguageService->addModuleLabels()
* TYPO3\CMS\Lang\LanguageService->getParserFactory()
* TYPO3\CMS\Lang\LanguageService->makeEntities()
* TYPO3\CMS\Lang\LanguageService->overrideLL()

The following methods changed signature according to previous deprecations in v8:
* TYPO3\CMS\Core\Charset\CharsetConverter->euc_char_mapping() - Third and fourth argument dropped
* TYPO3\CMS\Core\Charset\CharsetConverter->sb_char_mapping() - Third and fourth argument dropped
* TYPO3\CMS\Core\Charset\CharsetConverter->utf8_char_mapping() - Second and third argument dropped
* TYPO3\CMS\Core\Html\HtmlParser->RTE_transform() - Second argument unused
* TYPO3\CMS\Core\Localization\LanguageStore->setConfiguration() - Third argument dropped
* TYPO3\CMS\Core\Localization\LocalizationFactory->getParsedData() - Third and fourth argument unused
* TYPO3\CMS\Core\Localization\Parser\AbstractXmlParser->getParsedData() - Third argument dropped
* TYPO3\CMS\Core\Localization\Parser\LocalizationParserInterface->getParsedData() - Third argument dropped
* TYPO3\CMS\Core\Localization\Parser\LocallangXmlParser->getParsedData() - Third argument dropped
* TYPO3\CMS\Core\Page\PageRenderer->addInlineLanguageLabelFile() - Fourth argument dropped
* TYPO3\CMS\Core\Page\PageRenderer->includeLanguageFileForInline() - Fourth argument dropped
* TYPO3\CMS\Core\TypoScript\TemplateService->linkData() - Fourth argument unused
* TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction() - Persistent or file prefix in first argument removed
* TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName() - Second and thrird argument dropped
* TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj() - File reference prefix in first argument removed
* TYPO3\CMS\Core\Utility\GeneralUtility::wrapJS() - Second argument dropped
* TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->searchWhere() - Third parameter is now mandatory
* TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->whichWorkspace() - First argument removed
* TYPO3\CMS\Frontend\Plugin\AbstractPlugin->__constructor() - First argument unused
* TYPO3\CMS\Lang\LanguageService->getLL() - Second argument dropped
* TYPO3\CMS\Lang\LanguageService->getLLL() - Third argument dropped
* TYPO3\CMS\Lang\LanguageService->getsL() - Second argument dropped

The following public class properties have been dropped:
* TYPO3\CMS\Core\Charset\CharsetConverter->charSetArray
* TYPO3\CMS\Core\Charset\CharsetConverter->fourByteSets
* TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->compensateFieldWidth
* TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->excludeCHashVars
* TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->scriptParseTime
* TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->csConvObj
* TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->defaultCharSet
* TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->renderCharset
* TYPO3\CMS\Lang\LanguageService->charSet
* TYPO3\CMS\Lang\LanguageService->csConvObj
* TYPO3\CMS\Lang\LanguageService->moduleLabels
* TYPO3\CMS\Lang\LanguageService->parserFactory

The following configuration options are not evaluated anymore:
* $TYPO3_CONF_VARS[SC_OPTIONS][GLOBAL][cliKeys]
* $TYPO3_CONF_VARS[FE][noPHPscriptInclude]
* $TYPO3_CONF_VARS[FE][maxSessionDataSize]

The following entry points have been removed:
* typo3/cli_dispatch.phpsh

The following hooks have been removed:
* $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['getFlexFormDSClass']

The following TypoScript options have been removed:
* stdWrap.fontTag
* stdWrap.removeBadHTML
* config.mainScript
* config.frameReloadIfNotInFrameset
* config.noScaleUp
* config.setJS_mouseOver
* config.setJS_openPic
* config.doctype = xhtml_frames
* config.xhtmlDoctype = xhtml_frames
* config.pageGenScript
* config.beLoginLinkIPList
* config.beLoginLinkIPList_login
* config.beLoginLinkIPList_logout
* page.frameSet
* page.insertClassesFromRTE

Impact
======

Instantiating or requiring the PHP classes, will result in PHP fatal errors.

Calling the entry points via CLI will result in a file not found error.

.. index:: PHP-API
