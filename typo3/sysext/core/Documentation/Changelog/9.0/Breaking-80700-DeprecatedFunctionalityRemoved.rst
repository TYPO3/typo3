.. include:: /Includes.rst.txt

===================================================
Breaking: #80700 - Deprecated functionality removed
===================================================

See :issue:`80700`

Description
===========

The following PHP classes that have been previously deprecated for v8 have been removed:

* :php:`RemoveXSS`
* :php:`TYPO3\CMS\Backend\Console\Application`
* :php:`TYPO3\CMS\Backend\Console\CliRequestHandler`
* :php:`TYPO3\CMS\Backend\Controller\Wizard\ColorpickerController`
* :php:`TYPO3\CMS\Backend\Form\Container\SoloFieldContainer`
* :php:`TYPO3\CMS\Backend\Form\Wizard\SuggestWizard`
* :php:`TYPO3\CMS\Backend\Form\Wizard\ValueSliderWizard`
* :php:`TYPO3\CMS\Core\Cache\CacheFactory`
* :php:`TYPO3\CMS\Core\Controller\CommandLineController`
* :php:`TYPO3\CMS\Core\Http\AjaxRequestHandler`
* :php:`TYPO3\CMS\Core\Messaging\AbstractStandaloneMessage`
* :php:`TYPO3\CMS\Core\Messaging\ErrorpageMessage`
* :php:`TYPO3\CMS\Core\TimeTracker\NullTimeTracker`
* :php:`TYPO3\CMS\Extbase\Utility\ArrayUtility`
* :php:`TYPO3\CMS\Fluid\ViewHelpers\CaseViewHelper`
* :php:`TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper`
* :php:`TYPO3\CMS\Form\Domain\Model\FormElements\AdvancedPassword`
* :php:`TYPO3\CMS\Form\ViewHelpers\Form\CheckboxViewHelper`
* :php:`TYPO3\CMS\Form\ViewHelpers\Form\PlainTextMailViewHelper`
* :php:`TYPO3\CMS\Frontend\Page\FramesetRenderer`
* :php:`TYPO3\CMS\Lowlevel\CleanerCommand`

The following PHP interfaces have been dropped:

* :php:`TYPO3\CMS\Backend\Form\DatabaseFileIconsHookInterface`

The following PHP interface signatures have been changed:

* :php:`TYPO3\CMS\Extbase\Persistence\Generic\QueryInterface->like()` - Third argument dropped

The following PHP static class methods that have been previously deprecated for v8 have been removed:

* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getAjaxUrl()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getFlexFormDS()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getInlineLocalizationMode()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getListViewLink()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getRecordRaw()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getSpecConfParametersFromArray()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getSpecConfParts()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getSQLselectableList()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::titleAltAttrib()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::makeConfigForm()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::processParams()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::replaceL10nModeFields()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::RTEsetup()`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler::rmComma()`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler::destPathFromUploadFolder()`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler::noRecordsFromUnallowedTables()`
* :php:`TYPO3\CMS\Core\Utility\ArrayUtility::inArray()`
* :php:`TYPO3\CMS\Core\Utility\ClientUtility::getDeviceType()`
* :php:`TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addExtJSModule()`
* :php:`TYPO3\CMS\Core\Utility\ExtensionManagementUtility::appendToTypoConfVars()`
* :php:`TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath()`
* :php:`TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler()`
* :php:`TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerExtDirectComponent()`
* :php:`TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::pushErrorMessagesToFlashMessageQueue()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::array2xml_cs()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::compat_version()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::convertMicrotime()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::csvValues()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::deHSCentities()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::flushOutputBuffers()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::freetypeDpiComp()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::generateRandomBytes()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::getMaximumPathLength()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::getRandomHexString()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::imageMagickCommand()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::lcfirst()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::rawUrlEncodeFP()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::rawUrlEncodeJS()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::removeXSS()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::requireFile()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::requireOnce()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::resolveAllSheetsInDS()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::resolveSheetDefInDS()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::slashJS()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::strtolower()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::strtoupper()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::xmlGetHeaderAttribs()`
* :php:`TYPO3\CMS\Frontend\Page\PageGenerator::pagegenInit()`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository::getHash()`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository::storeHash()`

The following PHP class methods that have been previously deprecated for v8 have been removed:

* :php:`TYPO3\CMS\Backend\Clipboard\Clipboard->confirmMsg()`
* :php:`TYPO3\CMS\Backend\Controller\BackendController->addCssFile()`
* :php:`TYPO3\CMS\Backend\Controller\BackendController->addJavascript()`
* :php:`TYPO3\CMS\Backend\Controller\BackendController->addJavascriptFile()`
* :php:`TYPO3\CMS\Backend\Controller\BackendController->includeLegacyBackendItems()`
* :php:`TYPO3\CMS\Backend\Controller\Page\LocalizationController->getRecordUidsToCopy()`
* :php:`TYPO3\CMS\Backend\Controller\Page\PageLayoutController->printContent()`
* :php:`TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository->getAllowedLanguagesForBackendUser()`
* :php:`TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository->getExcludeQueryPart()`
* :php:`TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository->getPreviousLocalizedRecordUid()`
* :php:`TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository->getRecordLocalization()`
* :php:`TYPO3\CMS\Backend\Form\FormDataProvider\AbstractItemProvider->sanitizeMaxItems()`
* :php:`TYPO3\CMS\Backend\Module\AbstractFunctionModule->getBackPath()`
* :php:`[NotScanned] TYPO3\CMS\Backend\Module\AbstractFunctionModule->getDatabaseConnection()`
* :php:`TYPO3\CMS\Backend\Module\AbstractFunctionModule->incLocalLang()`
* :php:`[NotScanned] TYPO3\CMS\Backend\Module\BaseScriptClass->getDatabaseConnection()`
* :php:`TYPO3\CMS\Backend\Form\AbstractFormElement->isWizardsDisabled()`
* :php:`TYPO3\CMS\Backend\Form\AbstractFormElement->renderWizards()`
* :php:`TYPO3\CMS\Backend\Form\AbstractNode->getValidationDataAsDataAttribute()`
* :php:`TYPO3\CMS\Backend\Form\FormResultCompiler->JStop()`
* :php:`TYPO3\CMS\Backend\Routing\UriBuilder->buildUriFromAjaxId()`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->divider()`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->funcMenu()`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->getContextMenuCode()`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->getDragDropCode()`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->getHeader()`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->getResourceHeader()`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->getTabMenu()`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->getTabMenuRaw()`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->header()`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->icons()`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->loadJavascriptLib()`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->section()`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->sectionBegin()`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->sectionEnd()`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->sectionHeader()`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->t3Button()`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->getVersionSelector()`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->viewPageIcon()`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->wrapInCData()`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->wrapScriptTags()`
* :php:`TYPO3\CMS\Backend\Template\ModuleTemplate->getVersionSelector()`
* :php:`TYPO3\CMS\Backend\View\PageLayoutView->pages_getTree()`
* :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->veriCode()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->convCapitalize()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->conv_case()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->euc_char2byte_pos()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->euc_strlen()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->euc_strtrunc()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->euc_substr()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->getPreferredClientLanguage()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->strlen()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->strtrunc()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->substr()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->utf8_byte2char_pos()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->utf8_strlen()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->utf8_strpos()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->utf8_strrpos()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->utf8_strtrunc()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->utf8_substr()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->ensureClassLoadingInformationExists()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->loadExtensionTables()`
* :php:`TYPO3\CMS\Core\Database\RelationHandler->readyForInterface()`
* :php:`TYPO3\CMS\Core\Database\QueryView->tableWrap()`
* :php:`TYPO3\CMS\Core\Imaging\GraphicalFunctions->createTempSubDir()`
* :php:`TYPO3\CMS\Core\Imaging\GraphicalFunctions->prependAbsolutePath()`
* :php:`TYPO3\CMS\Core\Imaging\IconRegistry->getDeprecationSettings()`
* :php:`[NotScanned] TYPO3\CMS\Core\Messaging\FlashMessage->getClass()`
* :php:`TYPO3\CMS\Core\Messaging\FlashMessage->getIconName()`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->splitConfArray()`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->fileContent()`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->removeQueryString()`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->sortedKeyList()`
* :php:`[NotScanned] TYPO3\CMS\Extbase\Domain\Model\Category->getIcon()`
* :php:`[NotScanned] TYPO3\CMS\Extbase\Domain\Model\Category->setIcon()`
* :php:`TYPO3\CMS\Extbase\Persistence\Generic\Qom\Comparison->getParameterIdentifier()`
* :php:`TYPO3\CMS\Extbase\Persistence\Generic\Qom\Comparison->setParameterIdentifier()`
* :php:`TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings->getUsePreparedStatement()`
* :php:`TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings->getUseQueryCache()`
* :php:`TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings->usePreparedStatement()`
* :php:`TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings->useQueryCache()`
* :php:`TYPO3\CMS\Fluid\Core\Rendering\RenderingContext->getObjectManager()`
* :php:`TYPO3\CMS\Fluid\Core\Rendering\RenderingContext->getTemplateVariableContainer()`
* :php:`TYPO3\CMS\Fluid\Core\Rendering\RenderingContext->injectObjectManager()`
* :php:`TYPO3\CMS\Fluid\Core\Rendering\RenderingContext->setLegacyMode()`
* :php:`TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement->onSubmit()`
* :php:`TYPO3\CMS\Form\Domain\Model\FormElements\AbstractSection->onSubmit()`
* :php:`TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload->onBuildingFinished()`
* :php:`TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface->onSubmit()`
* :php:`TYPO3\CMS\Form\Domain\Model\FormElements\UnknownFormElement->onSubmit()`
* :php:`TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable->beforeRendering()`
* :php:`TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable->onBuildingFinished()`
* :php:`TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface->onBuildingFinished()`
* :php:`TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface->beforeRendering()`
* :php:`TYPO3\CMS\Form\Domain\Runtime\FormRuntime->beforeRendering()`
* :php:`TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication->record_registration()`
* :php:`TYPO3\CMS\Frontend\ContentObject\AbstractContentObject->getContentObject()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->URLqMark()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->clearTSProperties()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->fileResource()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->fillInMarkerArray()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->getClosestMPvalueForPage()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->getSubpart()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->getWhere()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->gifBuilderTextBox()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->includeLibs()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->linebreaks()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->processParams()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->removeBadHTML()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->stdWrap_fontTag()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->stdWrap_removeBadHTML()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->substituteMarker()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->substituteMarkerAndSubpartArrayRecursive()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->substituteMarkerArray()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->substituteMarkerArrayCached()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->substituteMarkerInObject()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->substituteSubpart()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->substituteSubpartArray()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->beLoginLinkIPList()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->csConv()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->encryptCharcode()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->encryptEmail()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->generatePage_whichScript()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->includeLibraries()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->setParseTime()`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->getPathFromRootline()`
* :php:`TYPO3\CMS\IndexedSearch\Indexer->includeCrawlerClass()`
* :php:`TYPO3\CMS\Lang\LanguageService->addModuleLabels()`
* :php:`TYPO3\CMS\Lang\LanguageService->getParserFactory()`
* :php:`TYPO3\CMS\Lang\LanguageService->makeEntities()`
* :php:`TYPO3\CMS\Lang\LanguageService->overrideLL()`
* :php:`TYPO3\CMS\Lowlevel\Utility\ArrayBrowser->wrapValue()`
* :php:`TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList->makeQueryArray()`
* :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController->printContent()`

The following methods changed signature according to previous deprecations in v8 at the end of the argument list:

* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->euc_char_mapping()` - Third and fourth argument dropped
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->sb_char_mapping()` - Third and fourth argument dropped
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->utf8_char_mapping()` - Second and third argument dropped
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->extFileFunctions()` - Fourth argument dropped
* :php:`TYPO3\CMS\Core\Localization\LanguageStore->setConfiguration()` - Third argument dropped
* :php:`TYPO3\CMS\Core\Localization\Parser\AbstractXmlParser->getParsedData()` - Third argument dropped
* :php:`TYPO3\CMS\Core\Localization\Parser\LocalizationParserInterface->getParsedData()` - Third argument dropped
* :php:`TYPO3\CMS\Core\Localization\Parser\LocallangXmlParser->getParsedData()` - Third argument dropped
* :php:`TYPO3\CMS\Core\Page\PageRenderer->addInlineLanguageLabelFile()` - Fourth argument dropped
* :php:`TYPO3\CMS\Core\Page\PageRenderer->includeLanguageFileForInline()` - Fourth argument dropped
* :php:`TYPO3\CMS\Extbase\Persistence\Generic\Query->like()` - Third argument dropped
* :php:`TYPO3\CMS\Frontend\Plugin\AbstractPlugin->pi_getLL()` - Third argument dropped
* :php:`TYPO3\CMS\Lang\LanguageService->getLL()` - Second argument dropped
* :php:`TYPO3\CMS\Lang\LanguageService->getLLL()` - Third argument dropped
* :php:`TYPO3\CMS\Lang\LanguageService->sL()` - Second argument dropped

The following static methods changed signature according to previous deprecations in v8 at the end of the argument list:

* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName()` - Second and third argument dropped
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::wrapJS()` - Second argument dropped
* :php:`TYPO3\CMS\Recycler\Utility\RecyclerUtility::getRecordPath()` - Second, third and fourth argument dropped

The following methods changed signature according to previous deprecations in v8 which should be
given as null if further arguments are added after the unused ones:

* :php:`TYPO3\CMS\Core\Html\RteHtmlParser->RTE_transform()` - Second argument unused
* :php:`TYPO3\CMS\Core\Localization\LocalizationFactory->getParsedData()` - Third and fourth argument unused
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->linkData()` - Fourth argument unused
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->whichWorkspace()` - First argument removed

The following constructor arguments are unused and should be given as null if additional arguments are
given after the unused one:

* [NotScanned] :php:`TYPO3\CMS\Frontend\Plugin\AbstractPlugin->__constructor()` - First argument unused

The following methods changed single argument details:

* [NotScanned] :php:`TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule` - Fifth argument ignores [labels][tabs_images][tab]
* [NotScanned] :php:`TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction()` - Persistent or file prefix in first argument removed
* [NotScanned] :php:`TYPO3\CMS\Extbase\Persistence\Generic\Qom\Statement` - support for \TYPO3\CMS\Core\Database\PreparedStatement as argument dropped
* [NotScanned] :php:`TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj()` - File reference prefix in first argument removed
* [NotScanned] :php:`TYPO3\CMS\Extbase\Mvc\Cli\ConsoleOutput->askAndValidate()` - support for boolean as fourth argument removed
* [NotScanned] :php:`TYPO3\CMS\Extbase\Mvc\Cli\ConsoleOutput->select()` - support for boolean as fifth argument removed

The following methods have additional arguments:

* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->searchWhere()` - Third parameter is now mandatory

The following public class properties have been dropped:

* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->localizationMode`
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->edit_record`
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->new_unique_uid`
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->externalTables`
* :php:`TYPO3\CMS\Backend\Module\AbstractFunctionModule->thisPath`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->extJScode`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->form_largeComp`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->charSetArray`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->fourByteSets`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->checkWorkspaceCache`
* :php:`TYPO3\CMS\Core\Imaging\GraphicalFunctions->tempPath`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->parentMenuArr`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->compensateFieldWidth`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->dtdAllowsFrames`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->excludeCHashVars`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->scriptParseTime`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->csConvObj`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->defaultCharSet`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->renderCharset`
* :php:`TYPO3\CMS\Lang\LanguageService->charSet`
* :php:`TYPO3\CMS\Lang\LanguageService->csConvObj`
* :php:`TYPO3\CMS\Lang\LanguageService->moduleLabels`
* :php:`TYPO3\CMS\Lang\LanguageService->parserFactory`

The following class properties have changed visibility:

* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->recUpdateAccessCache` changed from public to protected
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->recInsertAccessCache` changed from public to protected
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->isRecordInWebMount_Cache` changed from public to protected
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->isInWebMount_Cache` changed from public to protected
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->cachedTSconfig` changed from public to protected
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->pageCache` changed from public to protected

The following public class constants have been dropped:

* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate::STATUS_ICON_ERROR`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate::STATUS_ICON_WARNING`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate::STATUS_ICON_NOTIFICATION`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate::STATUS_ICON_OK`

The following configuration options are not evaluated anymore:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL][cliKeys']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['noPHPscriptInclude']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['maxSessionDataSize']`
* :php:`$GLOBALS['TYPO3_CONF_VARS_extensionAdded']`

The following hooks have been removed:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['getFlexFormDSClass']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/div/class.t3lib_utility_client.php']['getDeviceType']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list.inc']['makeQueryArray']`

The following entry points have been removed:

* [NotScanned] :php:`typo3/cli_dispatch.phpsh`

The following functionality has been removed:

* [NotScanned] Support for legacy prepared statements within Extbase Persistence within Qom\Statement

The following TypoScript options have been removed:

* :typoscript:`stdWrap.fontTag`
* :typoscript:`stdWrap.removeBadHTML`
* :typoscript:`config.mainScript`
* :typoscript:`config.frameReloadIfNotInFrameset`
* :typoscript:`config.noScaleUp`
* :typoscript:`config.setJS_mouseOver`
* :typoscript:`config.setJS_openPic`
* :typoscript:`config.doctype = xhtml_frames`
* :typoscript:`config.xhtmlDoctype = xhtml_frames`
* :typoscript:`config.pageGenScript`
* :typoscript:`config.beLoginLinkIPList`
* :typoscript:`config.beLoginLinkIPList_login`
* :typoscript:`config.beLoginLinkIPList_logout`
* :typoscript:`page.frameSet`
* :typoscript:`page.insertClassesFromRTE`
* single slashes are no longer interpreted as comment

The following TCA properties have been removed:

* :code:`type=select` selectedListStyle
* :code:`type=select` itemListStyle
* :code:`type=inline` behaviour['localizationMode']

The following PageTsConfig properties have been removed:

* :typoscript:`TCEFORM.[table].[field].addItems.icon` - with icons not registered in IconRegistry
* :typoscript:`TCEFORM.[table].[flexFormField].PAGE_TSCONFIG_ID`
* :typoscript:`TCEFORM.[table].[flexFormField].PAGE_TSCONFIG_IDLIST`
* :typoscript:`TCEFORM.[table].[flexFormField].PAGE_TSCONFIG_STR`

The following icon identifiers have been removed:

* :code:`actions-document-close`
* :code:`actions-edit-add`

The following Fluid ViewHelper arguments have been removed:

* :php:`f:be.container->enableClickMenu`
* :php:`f:be.container->loadExtJs`
* :php:`f:be.container->loadExtJsTheme`
* :php:`f:be.container->enableExtJsDebug`
* :php:`f:be.container->loadJQuery`
* :php:`f:be.container->jQueryNamespace`
* :php:`f:be.pageRenderer->loadExtJs`
* :php:`f:be.pageRenderer->loadExtJsTheme`
* :php:`f:be.pageRenderer->enableExtJsDebug`
* :php:`f:be.pageRenderer->loadJQuery`
* :php:`f:be.pageRenderer->jQueryNamespace`
* :php:`f:case->default (use f:defaultCase instead)`

The following requireJS modules have been removed:

* :php:`TYPO3/CMS/Core/QueryGenerator`

Further removal notes:

* FormEngine result array ignores key `extJSCODE`
* RTE transformation 'ts_css' dropped
* Invalid flex form data structure wildcard matching `secondFieldValue,*` dropped

The following JavaScript methods and options have been removed:

* :js:`backend/Resources/Public/JavaScript/jsfunc.inline.js escapeSelectorObjectId`
* :js:`TYPO3/CMS/Backend/Modal.getSeverityClass()`
* :js:`TYPO3/CMS/Backend/Severity.information`


Impact
======

Instantiating or requiring the PHP classes, will result in PHP fatal errors.

Calling the entry points via CLI will result in a file not found error.

.. index:: Backend, CLI, FlexForm, Fluid, Frontend, JavaScript, LocalConfiguration, PHP-API, TCA, TSConfig, TypoScript, PartiallyScanned
