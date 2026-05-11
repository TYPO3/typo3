.. include:: /Includes.rst.txt

.. _breaking-109783-1776735296:

====================================================
Breaking: #109783 - Deprecated functionality removed
====================================================

See :issue:`109783`

Description
===========

The following PHP classes that have previously been marked as deprecated with v14 have been removed:

- :php:`\TYPO3\CMS\Core\Console\CommandNameAlreadyInUseException` :ref:`(Deprecation entry) <deprecation-108667-1768743166>`
- :php:`\TYPO3\CMS\Core\Localization\Parser\AbstractXmlParser` :ref:`(Deprecation entry) <deprecation-107436-1736639846>`
- :php:`\TYPO3\CMS\Core\Localization\Parser\XliffParser` :ref:`(Deprecation entry) <deprecation-107436-1736639846>`
- :php:`\TYPO3\CMS\Form\Mvc\Configuration\InheritancesResolverService` :ref:`(Deprecation entry) <deprecation-97857-1761224875>`
- :php:`\TYPO3\CMS\Form\Storage\FileMountStorageAdapter` :ref:`(Deprecation entry) <deprecation-108653-1741600000>`
- :php:`\TYPO3\CMS\Frontend\Resource\FilePathSanitizer` :ref:`(Deprecation entry) <deprecation-107537-1760305681>`
- :php:`\TYPO3\CMS\Form\Domain\Model\FormElements\DatePicker` :ref:`(Deprecation entry) <deprecation-109152-1741600000>`
- :php:`\TYPO3\CMS\Form\ViewHelpers\Form\DatePickerViewHelper` :ref:`(Deprecation entry) <deprecation-109152-1741600000>`
- :php:`\TYPO3\CMS\Form\ViewHelpers\Form\TimePickerViewHelper` :ref:`(Deprecation entry) <deprecation-109152-1741600000>`
- :php:`\TYPO3\CMS\Lowlevel\Integrity\DatabaseIntegrityCheck` :ref:`(Deprecation entry) <deprecation-107931-1775647667>`
- :php:`\TYPO3\CMS\Fluid\ViewHelpers\Debug\RenderViewHelper` :ref:`(Deprecation entry) <deprecation-107208-1754387701>`
- :php:`\TYPO3\CMS\Install\Attribute\UpgradeWizard` :ref:`(Deprecation entry) <deprecation-106947-1750759241>`
- :php:`\TYPO3\CMS\Install\Updates\AbstractListTypeToCTypeUpdate` :ref:`(Deprecation entry) <deprecation-106947-1750759241>`
- :php:`\TYPO3\CMS\Install\Updates\Confirmation\DatabaseUpdatedPrerequisite` :ref:`(Deprecation entry) <deprecation-106947-1750759241>`
- :php:`\TYPO3\CMS\Install\Updates\ReferenceIndexUpdatedPrerequisite` :ref:`(Deprecation entry) <deprecation-106947-1750759241>`
- :php:`\TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider` :ref:`(Deprecation entry) <deprecation-98453-1738408355>`

The following PHP classes have been declared :php:`final`:

- :php:`\TYPO3\CMS\SomeExtension\Some\ClassName`

The following PHP interfaces that have previously been marked as deprecated with v14 have been removed:

- :php:`\TYPO3\CMS\Core\Localization\Parser\LocalizationParserInterface` :ref:`(Deprecation entry) <deprecation-107436-1736639846>`
- :php:`\TYPO3\CMS\Install\Updates\ChattyInterface` :ref:`(Deprecation entry) <deprecation-106947-1750759241>`
- :php:`\TYPO3\CMS\Install\Updates\ConfirmableInterface` :ref:`(Deprecation entry) <deprecation-106947-1750759241>`
- :php:`\TYPO3\CMS\Install\Updates\PrerequisiteInterface` :ref:`(Deprecation entry) <deprecation-106947-1750759241>`
- :php:`\TYPO3\CMS\Install\Updates\RepeatableInterface` :ref:`(Deprecation entry) <deprecation-106947-1750759241>`
- :php:`\TYPO3\CMS\Install\Updates\UpgradeWizardInterface` :ref:`(Deprecation entry) <deprecation-106947-1750759241>`
- :php:`\TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface` :ref:`(Deprecation entry) <deprecation-98453-1738408355>`

The following PHP interfaces changed:

- :php:`\TYPO3\CMS\SomeExtension\Some\InterfaceName->someMethod()` added

The following PHP class aliases that have previously been marked as deprecated with v14 have been removed:

- :php:`\TYPO3\CMS\Core\Service\FlexFormService` :ref:`(Deprecation entry) <breaking-107945-1761875852>`
- :php:`\TYPO3\CMS\Extbase\Annotation\FileUpload` :ref:`(Deprecation entry) <deprecation-107229-1760116732>`
- :php:`\TYPO3\CMS\Extbase\Annotation\IgnoreValidation` :ref:`(Deprecation entry) <deprecation-107229-1760116732>`
- :php:`\TYPO3\CMS\Extbase\Annotation\ORM\Cascade` :ref:`(Deprecation entry) <deprecation-107229-1760116732>`
- :php:`\TYPO3\CMS\Extbase\Annotation\ORM\Lazy` :ref:`(Deprecation entry) <deprecation-107229-1760116732>`
- :php:`\TYPO3\CMS\Extbase\Annotation\ORM\Transient` :ref:`(Deprecation entry) <deprecation-107229-1760116732>`
- :php:`\TYPO3\CMS\Extbase\Annotation\Validate` :ref:`(Deprecation entry) <deprecation-107229-1760116732>`
- :php:`\TYPO3\CMS\Install\Command\LanguagePackCommand` :ref:`(Deprecation entry) <deprecation-109027-1771514240>`
- :php:`\TYPO3\CMS\Install\Service\Event\ModifyLanguagePackRemoteBaseUrlEvent` :ref:`(Deprecation entry) <deprecation-109027-1771514240>`
- :php:`\TYPO3\CMS\Install\Service\Event\ModifyLanguagePacksEvent` :ref:`(Deprecation entry) <deprecation-109027-1771514240>`

The following PHP class methods that have previously been marked as deprecated with v14 have been removed:

- :php:`\TYPO3\CMS\Backend\Form\FormResultCollection->getHiddenFieldsHtml()` :ref:`(Deprecation entry) <deprecation-109102-1740480000>`
- :php:`\TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry->add()` :ref:`(Deprecation entry) <deprecation-108557-1768610680>`
- :php:`\TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry->addAllowedRecordTypes()` :ref:`(Deprecation entry) <deprecation-108557-1768610680>`
- :php:`\TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry->doesDoktypeOnlyAllowSpecifiedRecordTypes()` :ref:`(Deprecation entry) <deprecation-108557-1768610680>`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->addInlineLanguageDomain()` :ref:`(Deprecation entry) <deprecation-108963-1770907005>`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->getBodyContent()` :ref:`(Deprecation entry) <deprecation-109329-1774349266>`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->getDocType()` :ref:`(Deprecation entry) <deprecation-109329-1774349266>`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->getFavIcon()` :ref:`(Deprecation entry) <deprecation-109329-1774349266>`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->getHeadTag()` :ref:`(Deprecation entry) <deprecation-109329-1774349266>`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->getHtmlTag()` :ref:`(Deprecation entry) <deprecation-109329-1774349266>`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->getIconMimeType()` :ref:`(Deprecation entry) <deprecation-109329-1774349266>`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->getInlineLanguageLabelFiles()` :ref:`(Deprecation entry) <deprecation-109329-1774349266>`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->getInlineLanguageLabels()` :ref:`(Deprecation entry) <deprecation-109329-1774349266>`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->getLanguage()` :ref:`(Deprecation entry) <deprecation-109329-1774349266>`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->getMetaTag()` :ref:`(Deprecation entry) <deprecation-109329-1774349266>`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->getMoveJsFromHeaderToFooter()` :ref:`(Deprecation entry) <deprecation-109329-1774349266>`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->getTemplateFile()` :ref:`(Deprecation entry) <deprecation-109329-1774349266>`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->getTitle()` :ref:`(Deprecation entry) <deprecation-109329-1774349266>`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->removeMetaTag()` :ref:`(Deprecation entry) <deprecation-109329-1774349266>`
- :php:`\TYPO3\CMS\Frontend\ContentObject\AbstractContentObject->getPageRenderer()` :ref:`(Deprecation entry) <deprecation-109329-1774349266>`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->readFlexformIntoConf()` :ref:`(Deprecation entry) <deprecation-109575>`
- :php:`\TYPO3\CMS\Scheduler\Task\AbstractTask->getTaskClassName()` :ref:`(Deprecation entry) <deprecation-98453-1738408355>`
- :php:`\TYPO3\CMS\Scheduler\Task\AbstractTask->getTaskDescription()` :ref:`(Deprecation entry) <deprecation-98453-1738408355>`
- :php:`\TYPO3\CMS\Scheduler\Task\AbstractTask->getTaskTitle()` :ref:`(Deprecation entry) <deprecation-98453-1738408355>`

The following PHP static class methods that have previously been marked as deprecated for v14 have been removed:

- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getCommonSelectFields()` :ref:`(Deprecation entry) <deprecation-106393-1742454612>`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getExistingPageTranslations()` :ref:`(Deprecation entry) <deprecation-108810-1738253894>`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getItemLabel()` :ref:`(Deprecation entry) <deprecation-106393-1742454612>`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getLabelFromItemlist()` :ref:`(Deprecation entry) <deprecation-109519-1775665165>`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getLabelFromItemListMerged()` :ref:`(Deprecation entry) <deprecation-109519-1775665165>`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getLabelsFromItemsList()` :ref:`(Deprecation entry) <deprecation-109519-1775665165>`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordLocalization()` :ref:`(Deprecation entry) <deprecation-108810-1738253894>`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getTCEFORM_TSconfig()` :ref:`(Deprecation entry) <deprecation-108761-1769281290>`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getTSCpid()` :ref:`(Deprecation entry) <deprecation-108761-1769281290>`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getTSCpidCached()` :ref:`(Deprecation entry) <deprecation-108761-1769281290>`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::isRootLevelRestrictionIgnored()` :ref:`(Deprecation entry) <deprecation-106393-1742454612>`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::isTableLocalizable()` :ref:`(Deprecation entry) <deprecation-106393-1742454612>`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::isTableWorkspaceEnabled()` :ref:`(Deprecation entry) <deprecation-106393-1742454612>`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::isWebMountRestrictionIgnored()` :ref:`(Deprecation entry) <deprecation-106393-1742454612>`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::resolveFileReferences()` :ref:`(Deprecation entry) <deprecation-106393-1742454612>`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::translationCount()` :ref:`(Deprecation entry) <deprecation-108810-1738253894>`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::createVersionNumberedFilename()` :ref:`(Deprecation entry) <deprecation-107537-1760337101>`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv()` :ref:`(Deprecation entry) <deprecation-109551-1775924599>`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::setIndpEnv()` :ref:`(Deprecation entry) <deprecation-109551-1775924599>`
- :php:`\TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath()` :ref:`(Deprecation entry) <deprecation-107537-1761162068>`

The following methods changed signature according to previous deprecations in v14 at the end of the argument list:

- :php:`\TYPO3\CMS\Core\Page\PageRenderer->render()` - argument :php:`$request` is now mandatory :ref:`(Deprecation entry) <deprecation-109286-1773844395>`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->renderResponse()` - argument :php:`$request` is now mandatory and the first argument. The transitional :php:`ServerRequestInterface|int $requestOrCode` union has been removed :ref:`(Deprecation entry) <deprecation-109286-1773844395>`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->setDocType()` - argument :php:`$request` is now mandatory :ref:`(Deprecation entry) <deprecation-109286-1773844395>`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->setLanguage()` - argument :php:`$request` is now mandatory :ref:`(Deprecation entry) <deprecation-109286-1773844395>`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::isOnCurrentHost()` - argument :php:`$request` is now mandatory :ref:`(Deprecation entry) <deprecation-109523-1775680564>`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl()` - argument :php:`$request` is now mandatory :ref:`(Deprecation entry) <deprecation-109548-1775851081>`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl()` - argument :php:`$request` is now mandatory :ref:`(Deprecation entry) <deprecation-109544-1775761298>`
- :php:`\TYPO3\CMS\Extbase\Attribute\ORM\Cascade->__construct()` - argument :php:`$value` is now a :php:`?string` :ref:`(Deprecation entry) <deprecation-97559-1760453281>`
- :php:`\TYPO3\CMS\Extbase\Attribute\IgnoreValidation->__construct()` - accepts no arguments any more :ref:`(Deprecation entry) <deprecation-97559-1760453281>`
- :php:`\TYPO3\CMS\Extbase\Attribute\Validate->__construct()` - argument :php:`$validator` is not a :php:`string`, argument :php:`$param` has been removed :ref:`(Deprecation entry) <deprecation-97559-1760453281>`


The following public class properties have been dropped:

- :php:`\TYPO3\CMS\Backend\Form\FormResult->hiddenFieldsHtml` :ref:`(Deprecation entry) <deprecation-109102-1740480000>`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->checkPid_badDoktypeList` :ref:`(Deprecation entry) <deprecation-109575>`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->currentRecordNumber` :ref:`(Deprecation entry) <deprecation-109575>`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->lastTypoLinkResult` :ref:`(Deprecation entry) <deprecation-109575>`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->parentRecordNumber` :ref:`(Deprecation entry) <deprecation-109575>`

The following class property has changed/enforced type:

- :php:`\TYPO3\CMS\SomeExtension\Some\ClassName->someProperty` (is now :php:`\Some\Type`)

The following class constants have been dropped:

- :php:`\TYPO3\CMS\SomeExtension\Some\ClassName::SOME_CONSTANT`

The following TypoScript options have been dropped or adapted:

- :typoscript:`plugin.tx_form.settings.yamlConfigurations` and :typoscript:`module.tx_form.settings.yamlConfigurations` :ref:`(Deprecation entry) <deprecation-109412-1742000001>`
- :typoscript:`getData` type :typoscript:`cobj:parentRecordNumber` :ref:`(Deprecation entry) <deprecation-109575>`

The following user TSconfig options have been removed:

- :typoscript:`options.pageTree.doktypesToShowInNewPageDragArea` :ref:`(Deprecation entry) <deprecation-109196-1742122800>`

The following form yaml configurations that have previously been marked as deprecated for v14 have been removed:

- :yaml:`fieldExplanationText` :ref:`(Deprecation entry) <deprecation-107068-1759214357>`
- :yaml:`__inheritances` :ref:`(Deprecation entry) <deprecation-97857-1761224875>`
- :yaml:`persistenceManager.allowedFileMounts` :ref:`(Deprecation entry) <deprecation-108653-1741600000>`

The following global option handling have been dropped and are ignored:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']` :ref:`(Deprecation entry) <deprecation-108524-1766073657>`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][/*...*/]['tableoptions']` :ref:`(Deprecation entry) <deprecation-105297-1728836814>`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][/*...*/]['defaultTableOptions']['collate']` :ref:`(Deprecation entry) <deprecation-105297-1728836814>`

The following global variables have been changed:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['SOME']['option']` description of change

The following hooks have been removed:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['some']['hook']`

The following extension file loading has been removed:

- :file:`ext_tables.php` files in extensions are no longer considered during bootstrap :ref:`(Deprecation entry) <deprecation-109438-1774951763>`

The following TCA options are not evaluated anymore:

- :php:`$GLOBALS['TCA'][$table]['some']['option']`

The following extbase validator options have been removed:

- :php:`someOption` in :php:`\TYPO3\CMS\Extbase\Validation\Validator\SomeValidator`

The following extbase attribute usages have been removed:

- :php:`#[IgnoreValidation]` for parameters at method level :ref:`(Deprecation entry) <deprecation-108227-1763668119>`
- :php:`#[Validate]` for parameters at method level :ref:`(Deprecation entry) <deprecation-108227-1763668119>`

The following fallbacks have been removed:

- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->getRequest()` no longer falls back to :php:`$GLOBALS['TYPO3_REQUEST']`; code must call :php:`setRequest()` after instantiation :ref:`(Deprecation entry) <deprecation-109575>`

The following upgrade wizards have been removed:

- Description of removed upgrade wizard

The following row updater has been removed:

- :php:`\TYPO3\CMS\Install\Updates\RowUpdater\SomeMigration`

The following database table fields have been removed:

- :sql:`some_table.some_field`

The following JavaScript modules have been removed:

- :js:`@typo3/some-extension/some-module.js`

The following JavaScript method behaviours have changed:

- :js:`SomeModule.someMethod()` description of change

The following JavaScript methods have been removed:

- :js:`createAbstractViewFormElementToolbar()`,
  :js:`wireAbstractViewFormElementToolbarEventListeners()`,
  :js:`eachTemplateProperty()`, :js:`renderCheckboxTemplate()`,
  :js:`renderSimpleTemplate()`, :js:`renderSimpleTemplateWithValidators()`,
  :js:`renderSelectTemplates()`, :js:`renderFileUploadTemplates()` of
  :js:`@typo3/form/backend/form-editor/stage-component`
  :ref:`(Deprecation entry) <deprecation-109306-1774010043>`

The following smooth migration for JavaScript modules have been removed:

- :js:`@typo3/some-extension/old-module` to :js:`@typo3/some-extension/new-module`

The following localization XLIFF files have been removed:

- :file:`EXT:some_extension/Resources/Private/Language/some_file.xlf`

The following template files have been removed:

- :file:`EXT:form/Resources/Private/Backend/Partials/FormEditor/Stage/SimpleTemplate.fluid.html` :ref:`(Deprecation entry) <deprecation-109306-1774010043>`
- :file:`EXT:form/Resources/Private/Backend/Partials/FormEditor/Stage/SelectTemplate.fluid.html` :ref:`(Deprecation entry) <deprecation-109306-1774010043>`
- :file:`EXT:form/Resources/Private/Backend/Partials/FormEditor/Stage/FileUploadTemplate.fluid.html` :ref:`(Deprecation entry) <deprecation-109306-1774010043>`
- :file:`EXT:form/Resources/Private/Backend/Partials/FormEditor/Stage/ContentElement.fluid.html` :ref:`(Deprecation entry) <deprecation-109306-1774010043>`
- :file:`EXT:form/Resources/Private/Backend/Partials/FormEditor/Stage/Fieldset.fluid.html` :ref:`(Deprecation entry) <deprecation-109306-1774010043>`
- :file:`EXT:form/Resources/Private/Backend/Partials/FormEditor/Stage/StaticText.fluid.html` :ref:`(Deprecation entry) <deprecation-109306-1774010043>`
- :file:`EXT:form/Resources/Private/Backend/Partials/FormEditor/Stage/Page.fluid.html` :ref:`(Deprecation entry) <deprecation-109306-1774010043>`
- :file:`EXT:form/Resources/Private/Backend/Partials/FormEditor/Stage/SummaryPage.fluid.html` :ref:`(Deprecation entry) <deprecation-109306-1774010043>`
- :file:`EXT:form/Resources/Private/Backend/Partials/FormEditor/Stage/_ElementToolbar.fluid.html` :ref:`(Deprecation entry) <deprecation-109306-1774010043>`
- :file:`EXT:form/Resources/Private/Backend/Partials/FormEditor/Stage/_UnknownElement.fluid.html` :ref:`(Deprecation entry) <deprecation-109306-1774010043>`

The following content element definitions have been removed:

- :typoscript:`tt_content.some_element`

The following Fluid rendering mechanisms have been removed:

- :php:`HeaderAssets` and :php:`FooterAssets` Fluid template sections are no longer auto-rendered  :ref:`(Deprecation entry) <deprecation-107057-1756471326>`

The following FormEngine result array keys have been removed:

- :php:`additionalHiddenFields`, hidden fields are now added to the :php:`html` key directly :ref:`(Deprecation entry) <deprecation-109102-1740480000>`

Impact
======

Using above removed functionality will most likely raise PHP fatal level errors,
may change website output or crashes browser JavaScript.

.. index:: Backend, CLI, Database, FlexForm, Fluid, Frontend, JavaScript, LocalConfiguration, PHP-API, RTE, TCA, TSConfig, TypoScript, PartiallyScanned
