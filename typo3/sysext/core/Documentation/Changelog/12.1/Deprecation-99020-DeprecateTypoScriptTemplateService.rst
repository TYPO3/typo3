.. include:: /Includes.rst.txt

.. _deprecation-99020-1667911024:

==========================================================
Deprecation: #99020 - Deprecate TypoScript/TemplateService
==========================================================

See :issue:`99020`

Description
===========

The class :php:`TYPO3\CMS\Core\TypoScript\TemplateService` has been marked as deprecated
in TYPO3 v12 and will be removed in v13. This class is sometimes indirectly accessed
using :php:`TypoScriptFrontendController->tmpl` or :php:`$GLOBALS['TSFE']->tmpl`.


Impact
======

The class :php:`TemplateService` is part of the old TypoScript parser and has been
substituted with a :ref:`new parser approach <breaking-97816-1664800747>`.
Actively calling class methods will trigger a deprecation log level warning.


Affected installations
======================

Instances with extensions directly using :php:`TemplateService` or indirectly
using it by calling :php:`TypoScriptFrontendController->tmpl` or
:php:`$GLOBALS['TSFE']->tmpl` are affected.


Migration
=========

The class :php:`TemplateService` is typically called in TYPO3 frontend scope. Extensions
should avoid using :php:`TypoScriptFrontendController->tmpl` and :php:`$GLOBALS['TSFE']->tmpl`
methods and properties. They can retrieve TypoScript from the PSR-7 request instead
using the attribute :ref:`frontend.typoscript <feature-98914-1666689687>`.
As example, the full frontend TypoScript can be retrieved like this:

..  code-block:: php

    $fullTypoScript = $request()->getAttribute('frontend.typoscript')->getSetupArray();


.. index:: PHP-API, FullyScanned, ext:core
