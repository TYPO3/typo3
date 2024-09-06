.. include:: /Includes.rst.txt

.. _deprecation-104789-1725196704:

========================================================
Deprecation: #104789 - Fluid variables true, false, null
========================================================

See :issue:`104789`

Description
===========

Fluid standalone will add proper language syntax for booleans and `null`
with Fluid v4, which will be used in TYPO3 v13. Thus, user-defined variables
named `true`, `false` and `null` are no longer allowed.


Impact
======

Usage of `true`, `false` or `null` as variable name will throw
an exception in Fluid v4. In preparation of this change, Fluid v2.15 logs a
deprecation level error message if any of these variable names are used.


Affected installations
======================

Instances with Fluid templates using `true`, `false` or `null` as user-defined variable names.
This should rarely happen, as it would involve using :php:`$view->assign('true', $someVar)`.


Migration
=========

Template code using these variables should be adjusted to use different variable names.
In Fluid v4, the variables will contain their matching PHP counterparts.

.. index:: Fluid, NotScanned, ext:fluid
