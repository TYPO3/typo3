.. include:: /Includes.rst.txt

==============================================================
Deprecation: #95200 - RequireJS callbacks as inline JavaScript
==============================================================

See :issue:`95200`

Description
===========

Custom :php:`FormEngine` components allowed to load RequireJS modules
with arbitrary inline JavaScript to initialize those modules. In favor
of introducing content security policy headers, the amount of inline
JavaScript shall be reduced and replaced by corresponding declarations.

Using callback functions has been marked as deprecated and shall be replaced by new
:php:`TYPO3\CMS\Core\Page\JavaScriptModuleInstruction` declarations. In
:php:`FormEngine`, loading RequireJS module via arrays has been marked as deprecated and
has to be migrated as well.


Impact
======

Using :php:`$resultArray['requireJsModules']` with scalar :php:`string` values will
trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Installations implementing custom :php:`FormEngine` components and loading
RequireJS modules via :php:`$resultArray['requireJsModules']` are affected.


Migration
=========

New :php:`JavaScriptModuleInstruction` allows to declare the following
aspects when loading RequireJS modules:

*   :php:`$instruction = JavaScriptModuleInstruction::forRequireJS('TYPO3/CMS/Module')`
    creates corresponding loading instruction that can be enriched with
    following declarations
*   :php:`$instruction->assign(['key' => 'value'])` allows to assign key-value pairs
    directly to the loaded RequireJS module object or instance
*   :php:`$instruction->invoke('method', 'value-a', 'value-b')` allows to invoke
    a particular method of the loaded RequireJS instance with given argument values
*   :php:`$instruction->instance('value-a', 'value-b')` allows to invoke the
    constructor of the loaded RequireJS class with given argument values

Initializations other than the provided aspects have to be implemented in
custom module implementations, for example triggered by corresponding on-ready handlers.

Example in :php:`FormEngine` component
--------------------------------------

.. code-block:: php

    $resultArray['requireJsModules'][] = ['TYPO3/CMS/Backend/FormEngine/Element/InputDateTimeElement' => '
        function(InputDateTimeElement) {
            new InputDateTimeElement(' . GeneralUtility::quoteJSvalue($fieldId) . ');
        }'
    ];

... has to be migrated to the following ...

.. code-block:: php

    // use use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
    $resultArray['requireJsModules'][] = JavaScriptModuleInstruction::forRequireJS(
        'TYPO3/CMS/Backend/FormEngine/Element/InputDateTimeElement'
    )->instance($fieldId);

:php:`JavaScriptModuleInstruction` forwards arguments as `JSON` data - and thus
handles proper context-aware encoding implicitly (:php:`GeneralUtility::quoteJSvalue`
and similar custom encoding can be omitted in this case).


.. index:: Backend, JavaScript, NotScanned, ext:backend
