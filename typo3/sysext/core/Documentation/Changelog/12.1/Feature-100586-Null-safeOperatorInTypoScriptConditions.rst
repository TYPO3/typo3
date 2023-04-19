.. include:: /Includes.rst.txt

.. _feature-100586-1681464016:

==============================================================
Feature: #100586 - Null-safe operator in TypoScript conditions
==============================================================

See :issue:`100586`

Description
===========

By raising TYPO3's Symfony dependencies to `6.2` in :issue:`99239`, a couple
of new features were made available for the `expression language`_, which is
used by TYPO3 for its :ref:`TypoScript conditions <t3tsref:conditions>`.

One of those new features is the `null-safe operator`_. This operator is
especially useful when accessing properties on objects, which however might
not be available in some context, e.g. "TSFE" in the backend.

TYPO3 designed its custom expression functions in a way that they support the
usage of the null-safe operator by default. This is done by returning
:php:`NULL` in case the requested object is not available.

Therefore, instead of :typoscript:`[getTSFE() && getTSFE().id == 123]`,
integrators can simplify the condition to :typoscript:`[getTSFE()?.id == 123]`.

Impact
======

It's now possible to simplify TypoScript conditions using the new expression
language features, especially the null-safe operator.

.. _expression language: https://symfony.com/doc/current/reference/formats/expression_language.html
.. _null-safe operator: https://symfony.com/doc/current/reference/formats/expression_language.html#null-safe-operator

.. index:: Backend, Frontend, TypoScript, ext:core
