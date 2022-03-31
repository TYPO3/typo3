.. include:: /Includes.rst.txt

=================================================
Deprecation: #83803 - Deprecate EidRequestHandler
=================================================

See :issue:`83803`

Description
===========

The class :php:`\TYPO3\CMS\Frontend\Http\EidRequestHandler` has been marked as deprecated and will be removed in CMS 10.
This class has been replaced by a PSR-15 middleware :php:`\TYPO3\CMS\Frontend\Middleware\EidHandler`.

The eID functionality itself is not deprecated and can be used as before.


Impact
======

Installations that use :php:`\TYPO3\CMS\Frontend\Http\EidRequestHandler` will trigger a deprecation warning.


Affected Installations
======================

All installations that use custom extensions that add classes derived from :php:`\TYPO3\CMS\Frontend\Http\EidRequestHandler`.


Migration
=========

Use :php:`\TYPO3\CMS\Frontend\Middleware\EidHandler` instead.

.. index:: Frontend, PHP-API, FullyScanned
