.. include:: ../../Includes.txt

=======================================================
Deprecation: #80491 - BackendController inclusion hooks
=======================================================

See :issue:`80491`

Description
===========

The hook within BackendController :php:`$TYPO3_CONF_VARS["typo3/backend.php"]["additionalBackendItems"]`
has been marked as deprecated.

Loading ExtJS module JS/CSS files via :php:`ExtensionManagementUtility::addExtJSModule()` inside
the module configuration has been deprecated.

Calling :php:`BackendController->addJavascriptFile()`, :php:`BackendController->addJavascript()`
and :php:`BackendController->addCssFile()` will trigger a deprecation log entry.


Impact
======

Registering a hook via :php:`$TYPO3_CONF_VARS["typo3/backend.php"]["additionalBackendItems"]` and then
calling the Backend main page will trigger a deprecation log warning.

Registering any backend module which should load a global CSS/JS file within a module configuration
will trigger a deprecation log warning.

Calling any of the methods above will trigger a deprecation log warning.


Affected Installations
======================

Any installation using the hook or PHP methods directly in a custom extension, or using any of
the public methods above in a custom PHP script.


Migration
=========

Use the "constructPostProcess" hook within BackendController to load additional resources to achieve
the same functionality.

.. index:: Backend, PHP-API
