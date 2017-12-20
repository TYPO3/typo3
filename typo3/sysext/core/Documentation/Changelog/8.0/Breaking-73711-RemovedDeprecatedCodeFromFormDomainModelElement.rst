
.. include:: ../../Includes.txt

=========================================================================
Breaking: #73711 - Removed deprecated code from Form Domain Model Element
=========================================================================

See :issue:`73711`

Description
===========

The protected variable `$layout` is deprecated and has been removed together with
their getter and setter.


Impact
======

Using the methods `getLayout()` and `setLayout()` directly in any third party extension will result in a fatal error.


Affected Installations
======================

Instances which use the methods above to access the protected `$layout` variable.

.. index:: PHP-API, ext:form
