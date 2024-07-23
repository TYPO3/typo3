.. include:: /Includes.rst.txt

.. _deprecation-104463-1721754926:

========================================================
Deprecation: #104463 - Fluid standalone overrideArgument
========================================================

See :issue:`104463`

Description
===========

Fluid standalone method :php:`TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper->overrideArgument()`
has been marked as deprecated.


Impact
======

Using :php:`overrideArgument()` in ViewHelpers logs a deprecation level error message in Fluid standalone v4,
and will be removed with Fluid standalone v5. The method continues to work without deprecation level
error message in Fluid standalone v2.

With Fluid standalone v2.14, :php:`registerArgument()` no longer throws an exception if an
argument is already registered. This allows to override existing arguments transparently
without using :php:`overrideArgument()`.


Affected installations
======================

Instances with custom ViewHelpers using :php:`overrideArgument()` are affected.


Migration
=========

Update `typo3fluid/fluid` to at least 2.14 and use :php:`registerArgument()`.


.. index:: PHP-API, FullyScanned, ext:fluid
