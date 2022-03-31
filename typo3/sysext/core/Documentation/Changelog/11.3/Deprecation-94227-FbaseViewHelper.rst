.. include:: /Includes.rst.txt

=======================================
Deprecation: #94227 - f:base ViewHelper
=======================================

See :issue:`94227`

Description
===========

The :html:`<f:base>`  ViewHelper isn't suitable in almost all use cases
and has been deprecated: In most cases the :php:`PageRenderer` takes care of the
main :html:`<head>` markup, directly, or indirectly via TypoScript :html:`config.baseURL`.


Impact
======

Using the ViewHelper in Fluid templates will log a deprecation warning
and the ViewHelper will be dropped with v12.


Affected Installations
======================

The limited use of the ViewHelper likely leads to little usage numbers.
Searching extensions for the string html:`<f:base>` should
reveal any usages.


Migration
=========

The easiest solution is to simply copy PHP class
:php:`TYPO3\CMS\Fluid\ViewHelpers\BaseViewHelper` to the consuming extension,
giving the ViewHelper a happy life in an extension specific namespace.


.. index:: Fluid, NotScanned, ext:fluid
