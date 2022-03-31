.. include:: /Includes.rst.txt

==========================================
Deprecation: #86068 - old condition syntax
==========================================

See :issue:`86068`

Description
===========

The Symfony expression language is available for TypoScript since :issue:`85829` has been merged.
Accordingly the classic TypoScript condition syntax has been marked as deprecated.

For detailed information about the new expression language see :doc:`#85829 <../9.4/Feature-85829-ImplementSymfonyExpressionLanguageForTypoScriptConditions>`.


Impact
======

Using the old condition syntax will trigger a PHP :php:`E_USER_DEPRECATED` error.

Also the combination of multiple condition blocks with :typoscript:`AND`, :typoscript:`OR`, :typoscript:`&&` and :typoscript:`||` has been
marked as deprecated and will trigger a PHP :php:`E_USER_DEPRECATED` error.

If it is not possible yet to fully migrate to Symfony expression language, the feature flag :php:`[SYS][features][TypoScript.strictSyntax]`
can be disabled via Settings -> Configure Installation-Wide Options or directly in :file:`LocalConfiguration.php`.


Affected Installations
======================

TYPO3 installations with extensions which define conditions using the old syntax or setups which
make use of the old condition syntax.


Migration
=========

The old conditions can be replaced with the new expression language.

.. index:: Backend, Frontend, TSConfig, TypoScript, NotScanned, ext:core
