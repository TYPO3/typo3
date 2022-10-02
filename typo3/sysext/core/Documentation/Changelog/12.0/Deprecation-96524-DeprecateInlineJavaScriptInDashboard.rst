.. include:: /Includes.rst.txt

.. _deprecation-96524:

==============================================================
Deprecation: #96524 - Deprecate inline JavaScript in Dashboard
==============================================================

See :issue:`96524`

Description
===========

Using inline JavaScript when initializing RequireJS modules in individual
dashboard widgets has been deprecated. Widget implementations have to be
adjusted accordingly.

Impact
======

Usages will trigger PHP :php:`E_USER_DEPRECATED` errors.

Affected Installations
======================

Installations having individual widget implementations which are

* implementing :php:`\TYPO3\CMS\Dashboard\Widgets\RequireJsModuleInterface`
* invoking :php:`\TYPO3\CMS\Dashboard\DashboardInitializationService->getRequireJsModules`

Migration
=========

Affected widget have to implement :php:`\TYPO3\CMS\Dashboard\Widgets\JavaScriptInterface`
instead of deprecated :php:`\TYPO3\CMS\Dashboard\Widgets\RequireJsModuleInterface`.
Instead of using inline JavaScript for initializing RequireJS modules,
:php:`\TYPO3\CMS\Core\Page\JavaScriptModuleInstruction` have to be declared.

..  code-block:: php

    class ExampleChartWidget implements RequireJsModuleInterface
    {
        // ...
        public function getJavaScriptModuleInstructions(): array
        {
            return [
                'TYPO3/CMS/Dashboard/ChartInitializer' =>
                    'function(ChartInitializer) { ChartInitializer.initialize(); }',
            ];
        }
    }

Deprecated example widget above would look like the following when using
`JavaScriptInterface` and `JavaScriptModuleInstruction`:

..  code-block:: php

    class ExampleChartWidget implements JavaScriptInterface
    {
        // ...
        public function getJavaScriptModuleInstructions(): array
        {
            return [
                JavaScriptModuleInstruction::forRequireJS(
                    'TYPO3/CMS/Dashboard/ChartInitializer'
                )->invoke('initialize'),
            ];
        }
    }

.. index:: Frontend, TypoScript, FullyScanned, ext:core
