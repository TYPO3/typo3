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
- :php:`\TYPO3\CMS\Extbase\Security\Cryptography\HashService`
- :php:`\TYPO3\CMS\Core\Type\Icon\IconState`

The following PHP classes have been declared :php:`final`:

- :php:``

The following PHP interfaces that have previously been marked as deprecated with v13 have been removed:

- :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepositoryInitHookInterface`
- :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepositoryGetPageHookInterface`

The following PHP interfaces changed:

- :php:`` method :php:`` added

The following PHP class aliases that have previously been marked as deprecated with v13 have been removed:

- :php:``

The following PHP class methods that have previously been marked as deprecated with v13 have been removed:

* :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->getData()`
* :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->getFieldName()`
* :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->getPageId()`
* :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->getPageTsConfig()`
* :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->getTableName()`
* :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->setData()`
* :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->setFieldName()`
* :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->setPageId()`
* :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->setPageTsConfig()`
* :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->setTableName()`
- :php:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->returnWebmounts()`
- :php:`\TYPO3\CMS\Core\Imaging\Event\ModifyIconForResourcePropertiesEvent->getSize()`

The following PHP static class methods that have previously been marked as deprecated for v13 have been removed:

- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::thumbCode()`

The following methods changed signature according to previous deprecations in v13 at the end of the argument list:

- :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->__construct()` - All arguments are now mandatory
- :php:`\TYPO3\CMS\Core\Imaging\IconFactory->getIcon()` (argument 4 is now of type :php:`\TYPO3\CMS\Core\Imaging\IconState|null`)

The following public class properties have been dropped:

- :php:``

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

- :php:`` (is now string)

The following eID entry point has been removed:

- :php:``

The following ViewHelpers have been changed or removed:

- :html:`<f:>` removed

The following TypoScript options have been dropped or adapted:

- :typoscript:``

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

- :php:`$GLOBALS['TYPO3_CONF_VARS']['X']['Y']`

The following global variables have been removed:

- :php:`$GLOBALS['X']`

The following hooks have been removed:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['X']['Y']`

The following single field configuration has been removed from TCA:

- :php:`MM_foo` (for TCA fields with `X` configuration)

The following event has been removed:

- :php:``

The following fallbacks have been removed:

- Usage of the X

The following upgrade wizards have been removed:

- Wizard for X

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

- :js:`@typo3/t3editor/*`

The following JavaScript method behaviours have changed:

- :js:`FormEngineValidation.markFieldAsChanged()` always requires :js:`HTMLInputElement|HTMLTextAreaElement|HTMLSelectElement` to be passed as first argument
- :js:`FormEngineValidation.validateField()` always requires :js:`HTMLInputElement|HTMLTextAreaElement|HTMLSelectElement` to be passed as first argument

The following JavaScript method has been removed:

- :js:`X()` of :js:`@typo3/x/y`

The following CKEditor plugin has been removed:

- :js:``

The following dependency injection service alias has been removed:

- :yaml:`@x.y`

Impact
======

Using above removed functionality will most likely raise PHP fatal level errors,
may change website output or crashes browser JavaScript.

.. index:: Backend, CLI, Database, FlexForm, Fluid, Frontend, JavaScript, LocalConfiguration, PHP-API, RTE, TCA, TSConfig, TypoScript, PartiallyScanned
