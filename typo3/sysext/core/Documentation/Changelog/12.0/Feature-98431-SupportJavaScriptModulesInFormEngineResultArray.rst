.. include:: /Includes.rst.txt

.. _feature-98431-1664652179:

=====================================================================
Feature: #98431 - Support javaScriptModules in FormEngine resultArray
=====================================================================

See :issue:`98431`

Description
===========

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

Impact
======

FormEngine components now use the key :php:`'javaScriptModules'` which
expects an instance of :php:`TYPO3\CMS\Core\Page\JavaScriptModuleInstruction`
to be passed as value.

Example JavaScript module registration:

..  code-block:: php

    // use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
    $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create(
        '@my/extension/my-element.js'
    );

.. index:: Backend, JavaScript, ext:backend
