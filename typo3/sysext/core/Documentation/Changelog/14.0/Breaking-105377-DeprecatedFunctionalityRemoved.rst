.. include:: /Includes.rst.txt

.. _breaking-105377-1729513863:

====================================================
Breaking: #105377 - Deprecated functionality removed
====================================================

See :issue:`105377`

Description
===========

The following PHP classes that have previously been marked as deprecated with v13 have been removed:

- :php:`\TYPO3\CMS\Backend\Toolbar\Enumeration\InformationStatus`
- :php:`\TYPO3\CMS\Core\DataHandling\SlugEnricher`
- :php:`\TYPO3\CMS\Core\Resource\DuplicationBehavior`
- :php:`\TYPO3\CMS\Core\Type\Enumeration`
- :php:`\TYPO3\CMS\Core\Type\Icon\IconState`
- :php:`\TYPO3\CMS\Extbase\Mvc\View\GenericViewResolver`
- :php:`\TYPO3\CMS\Extbase\Security\Cryptography\HashService`
- :php:`\TYPO3\CMS\Fluid\View\AbstractTemplateView`
- :php:`\TYPO3\CMS\Fluid\View\StandaloneView`
- :php:`\TYPO3\CMS\Fluid\View\TemplateView`

The following PHP classes have been declared :php:`final`:

- :php:`\TYPO3\CMS\Extensionmanager\Updates\ExtensionModel`

The following PHP interfaces that have previously been marked as deprecated with v13 have been removed:

- :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepositoryInitHookInterface`
- :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepositoryGetPageHookInterface`
- :php:`\TYPO3\CMS\Extbase\Mvc\View\ViewResolverInterface`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectGetDataHookInterface`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectGetImageResourceHookInterface`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectOneSourceCollectionHookInterface`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectPostInitHookInterface`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectStdWrapHookInterface`

The following PHP interfaces changed:

- :php:`\TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface->modifyView()` added
- :php:`\TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface->render()` removed
- :php:`\TYPO3\CMS\Core\PageTitle\PageTitleProviderInterface->setRequest()` added

The following PHP class aliases that have previously been marked as deprecated with v13 have been removed:

- :php:`\TYPO3\CMS\Backend\Attribute\AsController`
- :php:`\TYPO3\CMS\Backend\FrontendBackendUserAuthentication`
- :php:`\TYPO3\CMS\Core\Database\Schema\Types\EnumType`
- :php:`\TYPO3\CMS\Core\View\FluidViewAdapter`
- :php:`\TYPO3\CMS\Core\View\FluidViewFactory`
- :php:`\TYPO3\CMS\Install\Updates\AbstractDownloadExtensionUpdate`
- :php:`\TYPO3\CMS\Install\Updates\ExtensionModel`

The following PHP class methods that have previously been marked as deprecated with v13 have been removed:

- :php:`\TYPO3\CMS\Backend\Controller\LoginController->getCurrentRequest()`
- :php:`\TYPO3\CMS\Backend\Controller\LoginController->getLoginProviderIdentifier()`
- :php:`\TYPO3\CMS\Backend\LoginProvider\Event\ModifyPageLayoutOnLoginProviderSelectionEvent->getController()`
- :php:`\TYPO3\CMS\Backend\LoginProvider\Event\ModifyPageLayoutOnLoginProviderSelectionEvent->getPageRenderer()`
- :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->getData()`
- :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->getFieldName()`
- :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->getPageId()`
- :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->getPageTsConfig()`
- :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->getTableName()`
- :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->setData()`
- :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->setFieldName()`
- :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->setPageId()`
- :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->setPageTsConfig()`
- :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->setTableName()`
- :php:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->returnWebmounts()`
- :php:`\TYPO3\CMS\Core\Imaging\Event\ModifyIconForResourcePropertiesEvent->getSize()`
- :php:`\TYPO3\CMS\Core\Utility\DiffUtility->makeDiffDisplay()`
- :php:`\TYPO3\CMS\Core\Versioning\VersionState->equals()`
- :php:`\TYPO3\CMS\Extbase\Mvc\View\JsonView->renderSection()`
- :php:`\TYPO3\CMS\Extbase\Mvc\View\JsonView->renderPartial()`
- :php:`\TYPO3\CMS\Extbase\Service\CacheService->getPageIdStack()`
- :php:`\TYPO3\CMS\Fluid\Core\Rendering\RenderingContext->setRequest()`
- :php:`\TYPO3\CMS\Fluid\Core\Rendering\RenderingContext->getRequest()`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->getLayoutRootPaths()`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->getPartialRootPaths()`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->getTemplatePaths()`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->getTemplateRootPaths()`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->getViewHelperResolver()`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->hasTemplate()`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->initializeRenderingContext()`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->setCache()`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->setFormat()`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->setLayoutPathAndFilename()`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->setLayoutRootPaths()`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->setPartialRootPaths()`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->setRequest()`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->setTemplate()`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->setTemplatePathAndFilename()`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->setTemplateRootPaths()`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->setTemplateSource()`
- :php:`\TYPO3\CMS\Fluid\View\TemplatePaths->fillDefaultsByPackageName()`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->addCacheTags()`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getPageCacheTags()`
- :php:`\TYPO3\CMS\Frontend\Page\PageRepository->enableFields()`

The following PHP static class methods that have previously been marked as deprecated for v13 have been removed:

- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getTcaFieldConfiguration()`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::thumbCode()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::hmac()`
- :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43()`
- :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig()`
- :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig()`
- :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionIcon()`
- :php:`\TYPO3\CMS\Core\Utility\MathUtility::convertToPositiveInteger()`
- :php:`\TYPO3\CMS\Core\Versioning\VersionState::cast()`

The following methods changed signature according to previous deprecations in v13 at the end of the argument list:

- :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->__construct()` - All arguments are now mandatory
- :php:`\TYPO3\CMS\Core\Imaging\IconFactory->getIcon()` (argument 4 is now of type :php:`\TYPO3\CMS\Core\Imaging\IconState|null`)
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile->copyTo()` (argument 3 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`)
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile->moveTo()` (argument 3 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`)
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile->rename()` (argument 2 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`)
- :php:`\TYPO3\CMS\Core\Resource\FileInterface->rename()` (argument 2 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`)
- :php:`\TYPO3\CMS\Core\Resource\FileReference->rename()` (argument 2 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`)
- :php:`\TYPO3\CMS\Core\Resource\Folder->addFile()` (argument 3 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`)
- :php:`\TYPO3\CMS\Core\Resource\Folder->addUploadedFile()` (argument 2 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`)
- :php:`\TYPO3\CMS\Core\Resource\Folder->copyTo()` (argument 3 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`)
- :php:`\TYPO3\CMS\Core\Resource\Folder->moveTo()` (argument 3 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`)
- :php:`\TYPO3\CMS\Core\Resource\InaccessibleFolder->addFile()` (argument 3 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`)
- :php:`\TYPO3\CMS\Core\Resource\InaccessibleFolder->addUploadedFile()` (argument 2 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`)
- :php:`\TYPO3\CMS\Core\Resource\InaccessibleFolder->copyTo()` (argument 3 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`)
- :php:`\TYPO3\CMS\Core\Resource\InaccessibleFolder->moveTo()` (argument 3 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`)
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorage->addFile()` (argument 4 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`)
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorage->addUploadedFile()` (argument 4 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`)
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorage->copyFile()` (argument 4 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`)
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorage->copyFolder()` (argument 4 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`)
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorage->moveFile()` (argument 4 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`)
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorage->moveFolder()` (argument 4 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`)
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorage->renameFile()` (argument 3 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`)

The following public class properties have been dropped:

- :php:`\TYPO3\CMS\Core\DataHandling->checkStoredRecords`
- :php:`\TYPO3\CMS\Core\DataHandling->checkStoredRecords_loose`
- :php:`\TYPO3\CMS\Core\Utility\DiffUtility->stripTags`

The following class method visibility has been changed to protected:

- :php:``

The following class methods are now marked as internal:

- :php:``

The following class methods now have a native return type and removed the
:php:`#[\ReturnTypeWillChange]` attribute:

- :php:``

The following class properties visibility have been changed to protected:

- :php:``

The following class property visibility has been changed to private:

- :php:``

The following class properties have been marked as internal:

- :php:``

The following class property has changed/enforced type:

- :php:`\TYPO3\CMS\Extbase\Mvc\Controller\ActionController->view` (is now :php:`\TYPO3\CMS\Core\View\ViewInterface`)

The following eID entry point has been removed:

- :php:``

The following ViewHelpers have been changed or removed:

- :html:`<f:>` removed

The following TypoScript options have been dropped or adapted:

- :typoscript:`<INCLUDE_TYPOSCRIPT: ...>` language construct

The following constant has been dropped:

- :php:``

The following class constants have been dropped:

- :php:`\TYPO3\CMS\Core\Imaging\Icon::SIZE_DEFAULT`
- :php:`\TYPO3\CMS\Core\Imaging\Icon::SIZE_LARGE`
- :php:`\TYPO3\CMS\Core\Imaging\Icon::SIZE_MEDIUM`
- :php:`\TYPO3\CMS\Core\Imaging\Icon::SIZE_MEGA`
- :php:`\TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_APPLICATION`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_AUDIO`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_IMAGE`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_TEXT`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_UNKNOWN`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_VIDEO`

The following global option handling have been dropped and are ignored:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig']`

The following global variables have been removed:

- :php:`$GLOBALS['X']`

The following hooks have been removed:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList']['customizeCsvHeader']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList']['customizeCsvRow']`

The following single field configuration has been removed from TCA:

- :php:`MM_foo` (for TCA fields with `X` configuration)

The following event has been removed:

- :php:``

The following extbase validator options have been removed:

- :php:`errorMessage` in :php:`TYPO3\CMS\Extbase\Validation\Validator\RegularExpressionValidator`

The following fallbacks have been removed:

- Accepting arrays returned by :php:`readFileContent()` in Indexed Search external parsers
- Allowing instantiation of :php:`\TYPO3\CMS\Core\Imaging\IconRegistry` in ext_localconf.php
- Accepting a comma-separated list of fields as value for the `columnsOnly` parameter
- Support for extbase repository magic :php:`findByX()`, :php:`findOneByX()` and :php:`countByX()` methods
- Fluid view helpers that extend :php:`\TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper`
  should no longer register :html:`class` attribute and should rely on attribute auto registration
  for the error class to be added correctly.

The following upgrade wizards have been removed:

- Install extension "fe_login_mode" from TER
- Migrate base and path to the new identifier property of the "sys_filemounts" table
- Migrate site settings to separate file
- Set workspace records in table "sys_template" to deleted
- Migrate backend user and groups to new module names
- Migrate backend groups "explicit_allowdeny" field to simplified format
- Migrate sys_log entries to a JSON formatted value
- Migrate storage and folder to the new folder_identifier property of the "sys_file_collection" table

The following features are now always enabled:

- `foo.bar`

The following feature has been removed:

- X

The following database table fields have been removed:

- :sql:`table.x`

The following backend route identifier has been removed:

- ``

The following global JavaScript variable has been removed:

- :js:`TYPO3.X`

The following global JavaScript function has been removed:

- :js:``

The following JavaScript modules have been removed:

- :js:`@typo3/backend/document-save-actions.js`
- :js:`@typo3/backend/wizard.js`
- :js:`@typo3/t3editor/*`

The following JavaScript method behaviours have changed:

- :js:`FormEngineValidation.markFieldAsChanged()` always requires :js:`HTMLInputElement|HTMLTextAreaElement|HTMLSelectElement` to be passed as first argument
- :js:`FormEngineValidation.validateField()` always requires :js:`HTMLInputElement|HTMLTextAreaElement|HTMLSelectElement` to be passed as first argument

The following JavaScript method has been removed:

- :js:`updateQueryStringParameter()` of :js:`@typo3/backend/utility.js`

The following smooth migration for JavaScript modules have been removed:

- :js:`@typo3/backend/page-tree/page-tree-element` to :js:`@typo3/backend/tree/page-tree-element`

The following CKEditor plugin has been removed:

- :js:``

The following dependency injection service alias has been removed:

- :yaml:`@x.y`

The following localization XLIFF files have been removed:

- :file:`EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf`
- :file:`EXT:frontend/Resources/Private/Language/Database.xlf`

Impact
======

Using above removed functionality will most likely raise PHP fatal level errors,
may change website output or crashes browser JavaScript.

.. index:: Backend, CLI, Database, FlexForm, Fluid, Frontend, JavaScript, LocalConfiguration, PHP-API, RTE, TCA, TSConfig, TypoScript, PartiallyScanned
