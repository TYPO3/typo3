.. include:: /Includes.rst.txt

.. _breaking-98016-1658731955:

===================================================
Breaking: #98016 - Removed TypoScript function hook
===================================================

See :issue:`98016`

Description
===========

With the transition to the :ref:`new TypoScript parser <feature-97816-1656350667>`,
the hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsparser.php']['preParseFunc']`
is no longer called.

This hook has been used to implement own functions for the TypoScript "function" operator :typoscript:`:=`.

Additional functions can now be implemented using the
:php:`\TYPO3\CMS\Core\TypoScript\AST\Event\EvaluateModifierFunctionEvent`
as described in :ref:`this Changelog <feature-98016-1658732423>`.

Impact
======

With the continued implementation of the new TypoScript parser in TYPO3 v12,
registered hook implementations are not executed anymore. The extension scanner
will report possible usages.

Affected installations
======================

Extensions registering own TypoScript function implementations like this:

..  code-block:: typoscript

    myValue := myCustomFunction(modifierArgument)

Migration
=========

Implement the :ref:`new event <feature-98016-1658732423>`. Extensions that want to keep
compatibility with both TYPO3 v11 and v12 can keep the old hook implementation without
further deprecations.

.. index:: PHP-API, TSConfig, TypoScript, FullyScanned, ext:core
