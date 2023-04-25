.. include:: /Includes.rst.txt

.. _deprecation-100587-1681477405:

=======================================================================================================
Deprecation: #100587 - Deprecate form engine additionalJavaScriptPost and custom eval inline JavaScript
=======================================================================================================

See :issue:`100587`

Description
===========

The result property `additionalJavaScriptPost` of the form engine result array
is deprecated. It was used, for instance, in custom eval definitions, that provided
inline JavaScript (configured via :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']`).


Impact
======

Custom form engine components that assign the result property `additionalJavaScriptPost`,
or custom eval class implementations for method :php:`returnFieldJS()` that return a plain
string (which is used as inline JavaScript), will raise a deprecation level log message.


Affected installations
======================

Installations that use custom form engine components modifying the result array,
or custom eval class implementations for method :php:`returnFieldJS()` returning
a plain string.


Migration
=========

Instead of using inline JavaScript, functionality has to be bundled in a static
JavaScript module. Custom eval class implementations for method :php:`returnFieldJS()`
have to return an instance of :php:`\TYPO3\CMS\Core\Page\JavaScriptModuleInstruction`
instead of a plain string.

Example
-------

Deprecated custom eval implementation:

..  code-block:: php

    <?php
    namespace TYPO3\CMS\Redirects\Evaluation;

    class SourceHost
    {
        public function returnFieldJS(): string
        {
            $jsCode = [];
            $jsCode[] = 'if (value === \'*\') {return value;}';
            $jsCode[] = 'var parser = document.createElement(\'a\');';
            $jsCode[] = 'parser.href = value.indexOf(\'://\') != -1 ? value : \'http://\' + value;';
            $jsCode[] = 'return parser.host;';
            return implode(' ', $jsCode);
        }
    }


Migrated custom eval implementation (JavaScript is now bundled in module
:js:`@typo3/redirects/form-engine-evaluation.js`):

..  code-block:: php

    <?php
    namespace TYPO3\CMS\Redirects\Evaluation;

    class SourceHost
    {
        public function returnFieldJS(): JavaScriptModuleInstruction
        {
            return JavaScriptModuleInstruction::create(
                '@typo3/redirects/form-engine-evaluation.js',
                'FormEngineEvaluation'
            );
        }
    }


.. index:: Backend, NotScanned, ext:backend
