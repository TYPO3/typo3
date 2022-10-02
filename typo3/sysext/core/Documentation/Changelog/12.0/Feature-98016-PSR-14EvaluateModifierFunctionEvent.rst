.. include:: /Includes.rst.txt

.. _feature-98016-1658732423:

======================================================
Feature: #98016 - PSR-14 EvaluateModifierFunctionEvent
======================================================

See :issue:`98016`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Core\TypoScript\AST\Event\EvaluateModifierFunctionEvent`
has been introduced which allows own TypoScript functions using the :typoscript:`:=` operator.

This is a substitution of the old
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsparser.php']['preParseFunc']`
hook as described in :ref:`this Changelog <breaking-98016-1658731955>`.

Impact
======

The TYPO3 Core tests come with test extension
:file:`EXT:core/Tests/Functional/Fixtures/Extensions/test_typoscript_ast_function_event` to functional
test the new event. The extension implements an example listener that can be used as boilerplate.

A simple TypoScript example looks like this:

..  code-block:: typoscript

    someIdentifier = originalValue
    someIdentifier := myModifierFunction(myFunctionArgument)

To implement :typoscript:`myModifierFunction`, an extension needs to register an event listener
in file :file:`Configuration/Services.yaml`:

..  code-block:: yaml

    MyVendor\MyPackage\EventListener\MyTypoScriptModifierFunction:
    tags:
      - name: event.listener
        identifier: 'my-package/typoscript/evaluate-modifier-function'

The corresponding event listener class could look like this:

..  code-block:: php

    use TYPO3\CMS\Core\TypoScript\AST\Event\EvaluateModifierFunctionEvent;

    final class MyTypoScriptModifierFunction
    {
        public function __invoke(EvaluateModifierFunctionEvent $event): void
        {
            if ($event->getFunctionName() === 'myModifierFunction') {
                $originalValue = $event->getOriginalValue();
                $functionArgument = $event->getFunctionArgument();
                // Manipulate values and set new value
                $event->setValue($originalValue . ' example ' . $functionArgument);
            }
        }
    }

.. index:: PHP-API, TSConfig, TypoScript, ext:core
