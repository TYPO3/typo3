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
- :php:`\TYPO3\CMS\Frontend\Resource\FilePathSanitizer` :ref:`(Deprecation entry) <deprecation-107537-1760305681>`
- :php:`\TYPO3\CMS\Lowlevel\Integrity\DatabaseIntegrityCheck` :ref:`(Deprecation entry) <deprecation-107931-1775647667>`

The following PHP classes have been declared :php:`final`:

- :php:`\TYPO3\CMS\SomeExtension\Some\ClassName`

The following PHP interfaces that have previously been marked as deprecated with v14 have been removed:

- :php:`\TYPO3\CMS\Core\Localization\Parser\LocalizationParserInterface` :ref:`(Deprecation entry) <deprecation-107436-1736639846>`

The following PHP interfaces changed:

- :php:`\TYPO3\CMS\SomeExtension\Some\InterfaceName->someMethod()` added

The following PHP class aliases that have previously been marked as deprecated with v14 have been removed:

- :php:`\TYPO3\CMS\SomeExtension\Some\ClassName`

The following PHP class methods that have previously been marked as deprecated with v14 have been removed:

- :php:`\TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry->add()` :ref:`(Deprecation entry) <deprecation-108557-1768610680>`
- :php:`\TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry->addAllowedRecordTypes()` :ref:`(Deprecation entry) <deprecation-108557-1768610680>`
- :php:`\TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry->doesDoktypeOnlyAllowSpecifiedRecordTypes()` :ref:`(Deprecation entry) <deprecation-108557-1768610680>`

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

- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::isOnCurrentHost()` - argument :php:`$request` is now mandatory :ref:`(Deprecation entry) <deprecation-109523-1775680564>`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl()` - argument :php:`$request` is now mandatory :ref:`(Deprecation entry) <deprecation-109548-1775851081>`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl()` - argument :php:`$request` is now mandatory :ref:`(Deprecation entry) <deprecation-109544-1775761298>`

The following public class properties have been dropped:

- :php:`\TYPO3\CMS\SomeExtension\Some\ClassName->someProperty`

The following class property has changed/enforced type:

- :php:`\TYPO3\CMS\SomeExtension\Some\ClassName->someProperty` (is now :php:`\Some\Type`)

The following class constants have been dropped:

- :php:`\TYPO3\CMS\SomeExtension\Some\ClassName::SOME_CONSTANT`

The following TypoScript options have been dropped or adapted:

- :typoscript:`plugin.tx_form.settings.yamlConfigurations` and :typoscript:`module.tx_form.settings.yamlConfigurations` :ref:`(Deprecation entry) <deprecation-109412-1742000001>`

The following user TSconfig options have been removed:

- :typoscript:`options.some.option`

The following form yaml configurations that have previously been marked as deprecated for v14 have been removed:

- :yaml:`fieldExplanationText` :ref:`(Deprecation entry) <deprecation-107068-1759214357>`
- :yaml:`__inheritances` :ref:`(Deprecation entry) <deprecation-97857-1761224875>`

The following global option handling have been dropped and are ignored:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['SOME']['option']`

The following global variables have been changed:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['SOME']['option']` description of change

The following hooks have been removed:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['some']['hook']`

The following TCA options are not evaluated anymore:

- :php:`$GLOBALS['TCA'][$table]['some']['option']`

The following extbase validator options have been removed:

- :php:`someOption` in :php:`\TYPO3\CMS\Extbase\Validation\Validator\SomeValidator`

The following fallbacks have been removed:

- Description of removed fallback

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

Impact
======

Using above removed functionality will most likely raise PHP fatal level errors,
may change website output or crashes browser JavaScript.

.. index:: Backend, CLI, Database, FlexForm, Fluid, Frontend, JavaScript, LocalConfiguration, PHP-API, RTE, TCA, TSConfig, TypoScript, PartiallyScanned
