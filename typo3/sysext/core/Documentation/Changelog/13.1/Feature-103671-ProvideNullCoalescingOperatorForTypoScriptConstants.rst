.. include:: /Includes.rst.txt

.. _feature-103671-1713511090:

============================================================================
Feature: #103671 - Provide null coalescing operator for TypoScript constants
============================================================================

See :issue:`103671`

Description
===========

TypoScript constants expressions have been extended to support a null coalescing
operator (`??`) as a way for providing a migration path from a legacy constant
name to a newer name, while providing full backwards compatibility for the
legacy constant name, if still defined.

Example that evaluates to `$config.oldThing` if set, otherwise the newer setting
`$myext.thing` would be used:

..  code-block:: typoscript

    plugin.tx_myext.settings.example = {$config.oldThing ?? $myext.thing}


Impact
======

Since :ref:`feature-103439-1712321631` it is suggested to define site settings
via :file:`settings.definitions.yaml` in site sets instead of TypoScript
constants. Migration of TYPO3 Core extensions revealed that such migration is a
good time to revisit constant names and the null coalescing operator helps to
switch to a new setting identifier without breaking backwards-compatibility with
previous constant names.


.. index:: TypoScript, ext:core
