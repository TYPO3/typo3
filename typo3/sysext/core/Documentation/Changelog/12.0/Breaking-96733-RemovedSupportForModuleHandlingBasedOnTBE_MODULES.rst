.. include:: /Includes.rst.txt

.. _breaking-96733:

===========================================================================
Breaking: #96733 - Removed support for module handling based on TBE_MODULES
===========================================================================

See :issue:`96733`

Description
===========

In previous TYPO3 versions, all available Backend modules were stored in the
global array :php:`$TBE_MODULES`.

Next to a very scattered and dated API to work with this array, it was still
possible to modify entries of modules through this global array.

With the introduction of the new Module Registration API, the global array is
not filled anymore since TYPO3 v12.0.

In addition, any previous functionality related to handling of the global array
has been removed.

The main and foremost important previous API piece
:php:`TYPO3\CMS\Backend\Module\ModuleLoader` has been removed completely as it
was usually populated with data of `$TBE_MODULES`.

The PHP classes

* :php:`TYPO3\CMS\Backend\Domain\Model\Module\BackendModule`
* :php:`TYPO3\CMS\Backend\Domain\Repository\Module\BackendModuleRepository`
* :php:`TYPO3\CMS\Backend\Module\ModuleStorage`

which were related to building the Module Menu on the left side
of the TYPO3 Backend have been removed as well. The new API based
on the :php:`ModuleProvider` takes care of permission handling
and returns objects of :php:`ModuleInterface`, the
rendering is now based on a well-defined OOP-based approach, which
is used throughout all places in TYPO3 Backend in a unified way.

As for TYPO3 Backend Modules, based on Extbase, their additional information
(allowed controllers and actions) was previously stored in a different
global array
:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$pluginName]['controllers']`
which has been merged with the Module Registry API and has been removed as well.

Because the registration of modules is now done in the extension's
:file:`Configuration/Backend/Modules.php` file, the following
API methods do no longer have any effect and will be removed in
TYPO3 v13.0:

- :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule()`
- :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addNavigationComponent()`
- :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addCoreNavigationComponent()`
- :php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule()`

The User TSconfig :typoscript:`options.hideModules.[moduleGroup]` has been
removed. All modules are registered with a unique identifier. Therefore, the
TSconfig :typoscript:`options.hideModules` should be used for all modules
directly. This still allows to hide a whole group, e.g. `web`, next to
regular moludes, such as `web_layout`.

Impact
======

Accessing or manipulating the now non-existent :php:`$GLOBALS[TBE_MODULES]`
array will result in a PHP warning.

Referencing any of the removed PHP classes will result in a PHP fatal error.

Using one of the mentioned API methods won't have any effect.

Affected Installations
======================

TYPO3 installations working with hooks or events effectively reading or
manipulating the global array `$TBE_MODULES` or accessing any of the removed
PHP classes / methods by third-party extensions.

Any occurrences can be detected via the Extension Scanner.

Migration
=========

Migrate to the new Module Registration API, and use the :php:`ModuleProvider`
class to get allowed modules and work with the objects. The current module
information (an implementation of :php:`ModuleInterface`) is stored in a
TYPO3 Backend request within the `module` option of a TYPO3 Backend route,
which can be accessed via :php:`$request->getAttribute('route')->getOption('module')`.

As soon as the new TYPO3 :php:`BackendModuleValidator` PSR-15 middleware
has validated the module for the current user, the :php:`ModuleInterface`
object is also added to the current request and can then be accessed
via :php:`$request->getAttribute('module')` in custom middlewares or
components.

.. note::

    With the new module registration, the module identifier is also used
    as the route identifier. Therefore, the `moduleName` option is removed
    from the TYPO3 backend route object.

The registration has to be moved from :file:`ext_tables.php` to the
:file:`Configuration/Backend/Modules.php` file. See the
:doc:`feature changelog <../12.0/Feature-96733-NewBackendModuleRegistrationAPI>`
for more information regarding the new registration.

Instead of :typoscript:`options.hideModules.web = layout`, use
:typoscript:`options.hideModules = web_layout`.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
