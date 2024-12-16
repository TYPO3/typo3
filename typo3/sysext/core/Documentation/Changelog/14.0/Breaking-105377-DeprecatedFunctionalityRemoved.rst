.. include:: /Includes.rst.txt

.. _breaking-105377-1729513863:

====================================================
Breaking: #105377 - Deprecated functionality removed
====================================================

See :issue:`105377`

Description
===========

The following PHP classes that have previously been marked as deprecated with v13 have been removed:

- :php:`\TYPO3\CMS\Backend\Toolbar\Enumeration\InformationStatus` :ref:`(Deprecation entry) <deprecation-101174-1688128234>`
- :php:`\TYPO3\CMS\Core\DataHandling\SlugEnricher` :ref:`(Deprecation entry) <deprecation-103244-1709376790>`
- :php:`\TYPO3\CMS\Core\Resource\DuplicationBehavior` :ref:`(Deprecation entry) <deprecation-101151-1688113521>`
- :php:`\TYPO3\CMS\Core\Type\Enumeration` :ref:`(Deprecation entry) <deprecation-101163-1681741493>`
- :php:`\TYPO3\CMS\Core\Type\Icon\IconState` :ref:`(Deprecation entry) <deprecation-101133-1687875352>`
- :php:`\TYPO3\CMS\Extbase\Mvc\View\GenericViewResolver` :ref:`(Deprecation entry) <deprecation-104773-1724942036>`
- :php:`\TYPO3\CMS\Extbase\Security\Cryptography\HashService` :ref:`(Deprecation entry) <deprecation-102763-1706358913>`
- :php:`\TYPO3\CMS\Fluid\View\AbstractTemplateView` :ref:`(Deprecation entry) <deprecation-104773-1724942036>`
- :php:`\TYPO3\CMS\Fluid\View\StandaloneView` :ref:`(Deprecation entry) <deprecation-104773-1724942036>`
- :php:`\TYPO3\CMS\Fluid\View\TemplateView` :ref:`(Deprecation entry) <deprecation-104773-1724942036>`

The following PHP classes have been declared :php:`final`:

- :php:`\TYPO3\CMS\Extensionmanager\Updates\ExtensionModel` :ref:`(Deprecation entry) <deprecation-102943-1706271208>`

The following PHP interfaces that have previously been marked as deprecated with v13 have been removed:

- :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepositoryInitHookInterface` :ref:`(Deprecation entry) <deprecation-102806-1704876661>`
- :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepositoryGetPageHookInterface` :ref:`(Deprecation entry) <deprecation-102806-1704876661>`
- :php:`\TYPO3\CMS\Extbase\Mvc\View\ViewResolverInterface` :ref:`(Deprecation entry) <deprecation-104773-1724942036>`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectGetDataHookInterface` :ref:`(Deprecation entry) <deprecation-102614-1701869807>`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectGetImageResourceHookInterface` :ref:`(Deprecation entry) <deprecation-102755-1704449836>`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectOneSourceCollectionHookInterface` :ref:`(Deprecation entry) <deprecation-102624-1701943829>`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectPostInitHookInterface` :ref:`(Deprecation entry) <deprecation-102581-1701449501>`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectStdWrapHookInterface` :ref:`(Deprecation entry) <deprecation-102745-1705054271>`

The following PHP interfaces changed:

- :php:`\TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface->modifyView()` added  :ref:`(Deprecation entry) <deprecation-104773-1724940753>`
- :php:`\TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface->render()` removed :ref:`(Deprecation entry) <deprecation-104773-1724940753>`
- :php:`\TYPO3\CMS\Core\PageTitle\PageTitleProviderInterface->setRequest()` added :issue:`102817`

The following PHP class aliases that have previously been marked as deprecated with v13 have been removed:

- :php:`\TYPO3\CMS\Backend\Attribute\Controller` :ref:`(Deprecation entry) <deprecation-102631-1702031387>`
- :php:`\TYPO3\CMS\Backend\FrontendBackendUserAuthentication` :ref:`(Deprecation entry) <important-105175-1727799093>`
- :php:`\TYPO3\CMS\Core\Database\Schema\Types\EnumType` :ref:`(Deprecation entry) <deprecation-105279-1728669356>`
- :php:`\TYPO3\CMS\Core\View\FluidViewAdapter` :issue:`105086`
- :php:`\TYPO3\CMS\Core\View\FluidViewFactory` :issue:`105086`
- :php:`\TYPO3\CMS\Install\Updates\AbstractDownloadExtensionUpdate` :ref:`(Deprecation entry) <deprecation-102943-1706271208>`
- :php:`\TYPO3\CMS\Install\Updates\ExtensionModel` :ref:`(Deprecation entry) <deprecation-102943-1706271208>`

The following PHP class methods that have previously been marked as deprecated with v13 have been removed:

- :php:`\TYPO3\CMS\Backend\Controller\LoginController->getCurrentRequest()` :ref:`(Deprecation entry) <deprecation-104773-1724940753>`
- :php:`\TYPO3\CMS\Backend\Controller\LoginController->getLoginProviderIdentifier()` :ref:`(Deprecation entry) <deprecation-104773-1724940753>`
- :php:`\TYPO3\CMS\Backend\LoginProvider\Event\ModifyPageLayoutOnLoginProviderSelectionEvent->getController()` :ref:`(Deprecation entry) <deprecation-104773-1724940753>`
- :php:`\TYPO3\CMS\Backend\LoginProvider\Event\ModifyPageLayoutOnLoginProviderSelectionEvent->getPageRenderer()` :ref:`(Deprecation entry) <deprecation-104773-1724940753>`
- :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->getData()` :ref:`(Deprecation entry) <deprecation-105252-1728471144>`
- :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->getFieldName()` :ref:`(Deprecation entry) <deprecation-105252-1728471144>`
- :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->getPageId()` :ref:`(Deprecation entry) <deprecation-105252-1728471144>`
- :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->getPageTsConfig()` :ref:`(Deprecation entry) <deprecation-105252-1728471144>`
- :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->getTableName()` :ref:`(Deprecation entry) <deprecation-105252-1728471144>`
- :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->setData()` :ref:`(Deprecation entry) <deprecation-105252-1728471144>`
- :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->setFieldName()` :ref:`(Deprecation entry) <deprecation-105252-1728471144>`
- :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->setPageId()` :ref:`(Deprecation entry) <deprecation-105252-1728471144>`
- :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->setPageTsConfig()` :ref:`(Deprecation entry) <deprecation-105252-1728471144>`
- :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->setTableName()` :ref:`(Deprecation entry) <deprecation-105252-1728471144>`
- :php:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->returnWebmounts()` :ref:`(Deprecation entry) <deprecation-104607-1723556132>`
- :php:`\TYPO3\CMS\Core\Imaging\Event\ModifyIconForResourcePropertiesEvent->getSize()` :ref:`(Deprecation entry) <deprecation-101475-1690546218>`
- :php:`\TYPO3\CMS\Core\Utility\DiffUtility->makeDiffDisplay()` :ref:`(Deprecation entry) <deprecation-104325-1720298173>`
- :php:`\TYPO3\CMS\Core\Versioning\VersionState->equals()` :ref:`(Deprecation entry) <deprecation-101175-1687941546>`
- :php:`\TYPO3\CMS\Extbase\Mvc\View\JsonView->renderSection()` :ref:`(Deprecation entry) <deprecation-101559-1721761906>`
- :php:`\TYPO3\CMS\Extbase\Mvc\View\JsonView->renderPartial()` :ref:`(Deprecation entry) <deprecation-101559-1721761906>`
- :php:`\TYPO3\CMS\Extbase\Service\CacheService->getPageIdStack()` :ref:`(Deprecation entry) <feature-104990-1726495719>`
- :php:`\TYPO3\CMS\Fluid\Core\Rendering\RenderingContext->setRequest()` :ref:`(Deprecation entry) <deprecation-104684-1724258020>`
- :php:`\TYPO3\CMS\Fluid\Core\Rendering\RenderingContext->getRequest()` :ref:`(Deprecation entry) <deprecation-104684-1724258020>`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->getLayoutRootPaths()` :ref:`(Deprecation entry) <deprecation-101559-1721761906>`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->getPartialRootPaths()` :ref:`(Deprecation entry) <deprecation-101559-1721761906>`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->getTemplatePaths()` :ref:`(Deprecation entry) <deprecation-101559-1721761906>`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->getTemplateRootPaths()` :ref:`(Deprecation entry) <deprecation-101559-1721761906>`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->getViewHelperResolver()` :ref:`(Deprecation entry) <deprecation-101559-1721761906>`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->hasTemplate()` :ref:`(Deprecation entry) <deprecation-101559-1721761906>`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->initializeRenderingContext()` :ref:`(Deprecation entry) <deprecation-101559-1721761906>`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->setCache()` :ref:`(Deprecation entry) <deprecation-101559-1721761906>`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->setFormat()` :ref:`(Deprecation entry) <deprecation-101559-1721761906>`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->setLayoutPathAndFilename()` :ref:`(Deprecation entry) <deprecation-101559-1721761906>`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->setLayoutRootPaths()` :ref:`(Deprecation entry) <deprecation-101559-1721761906>`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->setPartialRootPaths()` :ref:`(Deprecation entry) <deprecation-101559-1721761906>`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->setRequest()` :ref:`(Deprecation entry) <deprecation-101559-1721761906>`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->setTemplate()` :ref:`(Deprecation entry) <deprecation-101559-1721761906>`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->setTemplatePathAndFilename()` :ref:`(Deprecation entry) <deprecation-101559-1721761906>`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->setTemplateRootPaths()` :ref:`(Deprecation entry) <deprecation-101559-1721761906>`
- :php:`\TYPO3\CMS\Fluid\View\FluidViewAdapter->setTemplateSource()` :ref:`(Deprecation entry) <deprecation-101559-1721761906>`
- :php:`\TYPO3\CMS\Fluid\View\TemplatePaths->fillDefaultsByPackageName()` :ref:`(Deprecation entry) <deprecation-104764-1724851918>`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->addCacheTags()` :ref:`(Deprecation entry) <deprecation-102422-1700563266>`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getPageCacheTags()` :ref:`(Deprecation entry) <deprecation-102422-1700563266>`
- :php:`\TYPO3\CMS\Frontend\Page\PageRepository->enableFields()` :ref:`(Deprecation entry) <deprecation-102793-1704798252>`

The following PHP static class methods that have previously been marked as deprecated for v13 have been removed:

- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getTcaFieldConfiguration()` :ref:`(Deprecation entry) <deprecation-104304-1720084447>`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::thumbCode()` :ref:`(Deprecation entry) <deprecation-104662-1724058079>`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::hmac()` :ref:`(Deprecation entry) <deprecation-102762-1710402828>`
- :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43()` :ref:`(Deprecation entry) <deprecation-102821-1709843835>`
- :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig()` :ref:`(Deprecation entry) <deprecation-101799-1693397542>`
- :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig()` :ref:`(Deprecation entry) <deprecation-101807-1693474000>`
- :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionIcon()` :ref:`(Deprecation entry) <deprecation-102895-1706003502>`
- :php:`\TYPO3\CMS\Core\Utility\MathUtility::convertToPositiveInteger()` :ref:`(Deprecation entry) <deprecation-103785-1714720280>`
- :php:`\TYPO3\CMS\Core\Versioning\VersionState::cast()` :ref:`(Deprecation entry) <deprecation-101175-1687941546>`

The following methods changed signature according to previous deprecations in v13 at the end of the argument list:

- :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->__construct()` - All arguments are now mandatory :ref:`(Deprecation entry) <deprecation-105252-1728471144>`
- :php:`\TYPO3\CMS\Core\Imaging\IconFactory->getIcon()` (argument 4 is now of type :php:`\TYPO3\CMS\Core\Imaging\IconState|null`) :ref:`(Deprecation entry) <deprecation-101133-1687875352>`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile->copyTo()` (argument 3 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`) :ref:`(Deprecation entry) <deprecation-101151-1688113521>`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile->moveTo()` (argument 3 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`) :ref:`(Deprecation entry) <deprecation-101151-1688113521>`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile->rename()` (argument 2 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`) :ref:`(Deprecation entry) <deprecation-101151-1688113521>`
- :php:`\TYPO3\CMS\Core\Resource\FileInterface->rename()` (argument 2 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`) :ref:`(Deprecation entry) <deprecation-101151-1688113521>`
- :php:`\TYPO3\CMS\Core\Resource\FileReference->rename()` (argument 2 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`) :ref:`(Deprecation entry) <deprecation-101151-1688113521>`
- :php:`\TYPO3\CMS\Core\Resource\Folder->addFile()` (argument 3 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`) :ref:`(Deprecation entry) <deprecation-101151-1688113521>`
- :php:`\TYPO3\CMS\Core\Resource\Folder->addUploadedFile()` (argument 2 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`) :ref:`(Deprecation entry) <deprecation-101151-1688113521>`
- :php:`\TYPO3\CMS\Core\Resource\Folder->copyTo()` (argument 3 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`) :ref:`(Deprecation entry) <deprecation-101151-1688113521>`
- :php:`\TYPO3\CMS\Core\Resource\Folder->moveTo()` (argument 3 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`) :ref:`(Deprecation entry) <deprecation-101151-1688113521>`
- :php:`\TYPO3\CMS\Core\Resource\InaccessibleFolder->addFile()` (argument 3 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`) :ref:`(Deprecation entry) <deprecation-101151-1688113521>`
- :php:`\TYPO3\CMS\Core\Resource\InaccessibleFolder->addUploadedFile()` (argument 2 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`) :ref:`(Deprecation entry) <deprecation-101151-1688113521>`
- :php:`\TYPO3\CMS\Core\Resource\InaccessibleFolder->copyTo()` (argument 3 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`) :ref:`(Deprecation entry) <deprecation-101151-1688113521>`
- :php:`\TYPO3\CMS\Core\Resource\InaccessibleFolder->moveTo()` (argument 3 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`) :ref:`(Deprecation entry) <deprecation-101151-1688113521>`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorage->addFile()` (argument 4 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`) :ref:`(Deprecation entry) <deprecation-101151-1688113521>`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorage->addUploadedFile()` (argument 4 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`) :ref:`(Deprecation entry) <deprecation-101151-1688113521>`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorage->copyFile()` (argument 4 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`) :ref:`(Deprecation entry) <deprecation-101151-1688113521>`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorage->copyFolder()` (argument 4 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`) :ref:`(Deprecation entry) <deprecation-101151-1688113521>`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorage->moveFile()` (argument 4 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`) :ref:`(Deprecation entry) <deprecation-101151-1688113521>`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorage->moveFolder()` (argument 4 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`) :ref:`(Deprecation entry) <deprecation-101151-1688113521>`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorage->renameFile()` (argument 3 is now of type :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`) :ref:`(Deprecation entry) <deprecation-101151-1688113521>`
- :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin()` (argument 2 :php:`$type` and 3 :php:`$extensionKey` have been dropped) :ref:`(Deprecation entry) <deprecation-105076-1726923626>`

The following public class properties have been dropped:

- :php:`\TYPO3\CMS\Core\DataHandling->checkStoredRecords` :ref:`(Deprecation entry) <deprecation-101793-1693356502>`
- :php:`\TYPO3\CMS\Core\DataHandling->checkStoredRecords_loose` :ref:`(Deprecation entry) <deprecation-101793-1693356502>`
- :php:`\TYPO3\CMS\Core\Utility\DiffUtility->stripTags` :ref:`(Deprecation entry) <deprecation-104325-1720298173>`

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

- :php:`\TYPO3\CMS\Extbase\Mvc\Controller\ActionController->view` (is now :php:`\TYPO3\CMS\Core\View\ViewInterface`) :ref:`(Deprecation entry) <deprecation-101559-1721761906>`

The following eID entry point has been removed:

- :php:``

The following ViewHelpers have been changed or removed:

- :html:`<f:>` removed

The following TypoScript options have been dropped or adapted:

- :typoscript:`<INCLUDE_TYPOSCRIPT: ...>` language construct :ref:`(Deprecation entry) <deprecation-105171-1727785626>`

The following user TSconfig options have been removed:

- :typoscript:`options.pageTree.backgroundColor` :ref:`(Deprecation entry) <deprecation-103211-1709038752>`

The following constant has been dropped:

- :php:``

The following class constants have been dropped:

- :php:`\TYPO3\CMS\Core\Imaging\Icon::SIZE_DEFAULT` :ref:`(Deprecation entry) <deprecation-101475-1690546218>`
- :php:`\TYPO3\CMS\Core\Imaging\Icon::SIZE_LARGE` :ref:`(Deprecation entry) <deprecation-101475-1690546218>`
- :php:`\TYPO3\CMS\Core\Imaging\Icon::SIZE_MEDIUM` :ref:`(Deprecation entry) <deprecation-101475-1690546218>`
- :php:`\TYPO3\CMS\Core\Imaging\Icon::SIZE_MEGA` :ref:`(Deprecation entry) <deprecation-101475-1690546218>`
- :php:`\TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL` :ref:`(Deprecation entry) <deprecation-101475-1690546218>`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_APPLICATION` :ref:`(Deprecation entry) <deprecation-102032-1695805007>`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_AUDIO` :ref:`(Deprecation entry) <deprecation-102032-1695805007>`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_IMAGE` :ref:`(Deprecation entry) <deprecation-102032-1695805007>`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_TEXT` :ref:`(Deprecation entry) <deprecation-102032-1695805007>`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_UNKNOWN` :ref:`(Deprecation entry) <deprecation-102032-1695805007>`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_VIDEO` :ref:`(Deprecation entry) <deprecation-102032-1695805007>`
- :php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_PLUGIN` :ref:`(Deprecation entry) <deprecation-105076-1726923626>`

The following global option handling have been dropped and are ignored:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig']` :ref:`(Deprecation entry) <deprecation-101799-1693397542>`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig']` :ref:`(Deprecation entry) <deprecation-101807-1693474000>`

The following global variables have been changed:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['SecondDatabase']['driverMiddlewares']['driver-middleware-identifier']`
  must be an array, not a class string :ref:`(Deprecation entry) <deprecation-102586-1701536568>`

The following hooks have been removed:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList']['customizeCsvHeader']` :ref:`(Deprecation entry) <deprecation-102337-1715591179>`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList']['customizeCsvRow']` :ref:`(Deprecation entry) <deprecation-102337-1715591179>`

The following single field configuration has been removed from TCA:

- :php:`MM_foo` (for TCA fields with `X` configuration)

The following event has been removed:

- :php:``

The following extbase validator options have been removed:

- :php:`errorMessage` in :php:`TYPO3\CMS\Extbase\Validation\Validator\RegularExpressionValidator` :ref:`(Deprecation entry) <deprecation-102326-1699703964>`
- Shorthand support of :php`TYPO3.CMS.Extbase` usage :ref:`(Deprecation entry) <deprecation-103965-1717335369>`

The following fallbacks have been removed:

- Accepting arrays returned by :php:`readFileContent()` in Indexed Search external parsers :ref:`(Deprecation entry) <deprecation-102908-1706081017>`
- Allowing instantiation of :php:`\TYPO3\CMS\Core\Imaging\IconRegistry` in ext_localconf.php :ref:`(Deprecation entry) <deprecation-104778-1724953249>`
- Accepting a comma-separated list of fields as value for the `columnsOnly` parameter :ref:`(Deprecation entry) <deprecation-104108-1718354448>`
- Support for extbase repository magic :php:`findByX()`, :php:`findOneByX()` and :php:`countByX()` methods :ref:`(Deprecation entry) <deprecation-100071-1677853787>`
- Fluid view helpers that extend :php:`\TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper`
  should no longer register :html:`class` attribute and should rely on attribute auto registration
  for the error class to be added correctly.  :ref:`(Deprecation entry) <deprecation-104223-1721383576>`
- The legacy backend entry point :file:`typo3/index.php` has been removed along with handling of :file:`composer.json`
  setting `extra.typo3/cms.install-deprecated-typo3-index-php` :ref:`(Deprecation entry) <deprecation-87889-1705928143>`

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

- :sql:`tt_content.list_type` :ref:`(Deprecation entry) <deprecation-105076-1726923626>`

The following backend route identifier has been removed:

- ``

The following global JavaScript variable has been removed:

- :js:`TYPO3.X`

The following global JavaScript function has been removed:

- :js:``

The following JavaScript modules have been removed:

- :js:`@typo3/backend/document-save-actions.js` :ref:`(Deprecation entry) <deprecation-103528-1712153304>`
- :js:`@typo3/backend/wizard.js` :ref:`(Deprecation entry) <deprecation-103230-1709202638>`
- :js:`@typo3/t3editor/*` :ref:`(Deprecation entry) <deprecation-102440-1700638677>`

The following JavaScript method behaviours have changed:

- :js:`FormEngineValidation.markFieldAsChanged()` always requires :js:`HTMLInputElement|HTMLTextAreaElement|HTMLSelectElement` to be passed as first argument :ref:`(Deprecation entry) <deprecation-101912-1694611003>`
- :js:`FormEngineValidation.validateField()` always requires :js:`HTMLInputElement|HTMLTextAreaElement|HTMLSelectElement` to be passed as first argument :ref:`(Deprecation entry) <deprecation-101912-1694611003>`

The following JavaScript method has been removed:

- :js:`updateQueryStringParameter()` of :js:`@typo3/backend/utility.js` :ref:`(Deprecation entry) <deprecation-104154-1718802119>`

The following smooth migration for JavaScript modules have been removed:

- :js:`@typo3/backend/page-tree/page-tree-element` to :js:`@typo3/backend/tree/page-tree-element` :ref:`(Deprecation entry) <deprecation-103850-1715873982>`

The following CKEditor plugin has been removed:

- :js:``

The following dependency injection service alias has been removed:

- :yaml:`@x.y`

The following localization XLIFF files have been removed:

- :file:`EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf` :ref:`(see referenced change) <breaking-102834-1705491713>`
- :file:`EXT:frontend/Resources/Private/Language/Database.xlf` :ref:`(see referenced change) <breaking-102834-1705491713>`

The following template files have been removed:

- :file:`EXT:fluid_styled_content/Resources/Private/Templates/List.html` :ref:`(Deprecation entry) <deprecation-105076-1726923626>`

The following content element definitions have been removed:

- :typoscript:`tt_content.list` :ref:`(Deprecation entry) <deprecation-105076-1726923626>`

Impact
======

Using above removed functionality will most likely raise PHP fatal level errors,
may change website output or crashes browser JavaScript.

.. index:: Backend, CLI, Database, FlexForm, Fluid, Frontend, JavaScript, LocalConfiguration, PHP-API, RTE, TCA, TSConfig, TypoScript, PartiallyScanned
