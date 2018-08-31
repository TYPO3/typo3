.. include:: ../../Includes.txt

====================================================
Deprecation: #86068 - Deprecate old condition syntax
====================================================

See :issue:`86068`

Description
===========

The Symfony expression language is available for TypoScript since :issue:`85829` has been merged.
Following that the classic TypoScript condition syntax has been deprecated.

For detailed information about the new expression language see `#85829 <Feature-85829-ImplementSymfonyExpressionLanguageForTypoScriptConditions.rst>`_


Impact
======

Using the old condition syntax will trigger a deprecation message.

Also the combination of multiple condition blocks with ``AND``, ``OR``, ``&&`` and ``||`` has been
deprecated and will trigger a deprecation message.

To prevent deprecation messages use the new expression language for conditions. If this is not
possible, the feature flag ``TypoScript.strictSyntax`` can be disabled.


Affected Installations
======================

TYPO3 installations with extensions which define conditions using the old syntax or setups which
make use of the old conditions.


Migration
=========

The old conditions can be replaced with the new expression language.

.. index:: Backend, Frontend, TSConfig, TypoScript, NotScanned, ext:core
