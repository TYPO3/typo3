.. include:: /Includes.rst.txt

.. _deprecation-98431-1664652773:

========================================================================
Deprecation: #98431 - Replace requireJsModules in FormEngine resultArray
========================================================================

See :issue:`98431`

Description
===========

Loading JavaScript modules via :php:`$resultArray['requireJsModules']` has been
deprecated in favor of a new generic key named :php:`'javaScriptModules'`.

The ability for custom :php:`FormEngine` components to load JavaScript modules
via instances of :php:`TYPO3\CMS\Core\Page\JavaScriptModuleInstruction` is now
streamlined to use a new, generic :php:`$resultArray` key named
:php:`'javaScriptModules'`. The behaviour is otherwise identical to the
functionality that has been available via :php:`'requireJsModules'`,
but the new name reflects that not just RequireJS modules may be loaded,
but also newer, native ECMAScript v6 JavaScript modules.

Using :php:`'javaScriptModules'` is now the suggested to be used over
:php:`'requireJsModules'`, as this latter is deprecated from now on
and will be removed in TYPO3 v13.

The ability for custom :php:`FormEngine` components to load JavaScript modules
via instances of :php:`TYPO3\CMS\Core\Page\JavaScriptModuleInstruction` is now
streamlined to use a new, generic :php:`$resultArray` key named
:php:`'javaScriptModules'`. The behaviour is otherwise identical to the
functionality that has been available via :php:`'requireJsModules'`,
but the new name reflects that not just RequireJS modules may be loaded,
but also newer, native ECMAScript v6 JavaScript modules.

The :php:`'requireJsModules'` key is deprecated.

Impact
======

Registering modules via :`'requireJsModules'` will still work.
These modules will be loaded after modules registered via `'javaScriptModules'`.
Extensions that use :php:`'requireJsModules` will work as before but trigger a
PHP :php:`E_USER_DEPRECATED` error.

Affected installations
======================

Installations that register custom FormEngine components with JavaScript modules.

Migration
=========

Use the key :php:`'javaScriptModules'` and assign an instance of
:php:`TYPO3\CMS\Core\Page\JavaScriptModuleInstruction`:

..  code-block:: php

    // use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
    $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create(
        '@my/extension/my-element.js'
    );

.. index:: Backend, JavaScript, NotScanned, ext:backend
