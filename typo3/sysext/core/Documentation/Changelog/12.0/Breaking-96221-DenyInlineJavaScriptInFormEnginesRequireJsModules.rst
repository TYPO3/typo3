.. include:: /Includes.rst.txt

.. _breaking-96221:

==========================================================================
Breaking: #96221 - Deny inline JavaScript in FormEngine's requireJsModules
==========================================================================

See :issue:`96221`

Description
===========

Custom :php:`FormEngine` components allowed to load RequireJS modules
with arbitrary inline JavaScript to initialize those modules. In favor
of introducing content security policy headers, the amount of inline
JavaScript shall be reduced and replaced by corresponding declarations.

Using callback functions as inline JavaScript is not possible anymore,
initializations have to be declared using an instance of
:php:`TYPO3\CMS\Core\Page\JavaScriptModuleInstruction`.

Impact
======

Using inline JavaScript to initialize RequireJS modules in `FormEngine`,
like shown in the the example below, will throw a corresponding
:php:`\LogicException`.

..  code-block:: php

    $resultArray['requireJsModules'][] = ['TYPO3/CMS/Backend/FormEngine/Element/InputDateTimeElement' => '
        // inline JavaScript code to initialize `InputDateTimeElement`
        function(InputDateTimeElement) {
            new InputDateTimeElement(' . GeneralUtility::quoteJSvalue($fieldId) . ');
        }'
    ];

Affected Installations
======================

All instances that are using RequireJS modules with custom initializations
as inline JavaScript in `FormEngine`.

Migration
=========

:doc:`Previous deprecation ChangeLog documentation <../11.5/Deprecation-95200-DeprecateRequireJSCallbacksAsInlineJavaScript>`
provided migration details already.

The following snippet shows the migrated source code of shown above - using
:php:`TYPO3\CMS\Core\Page\JavaScriptModuleInstruction` instead of inline JavaScript.

..  code-block:: php

    // use use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
    $resultArray['requireJsModules'][] = JavaScriptModuleInstruction::forRequireJS(
        'TYPO3/CMS/Backend/FormEngine/Element/InputDateTimeElement'
    )->instance($fieldId);

:php:`JavaScriptModuleInstruction` forwards arguments as `JSON` data - and thus
handles proper context-aware encoding implicitly (:php:`GeneralUtility::quoteJSvalue`
and similar custom encoding can be omitted in this case).

.. index:: Backend, JavaScript, NotScanned, ext:backend
