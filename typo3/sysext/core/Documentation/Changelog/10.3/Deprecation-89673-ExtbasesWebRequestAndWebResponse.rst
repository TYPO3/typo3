.. include:: /Includes.rst.txt

==========================================================
Deprecation: #89673 - Extbase's WebRequest and WebResponse
==========================================================

See :issue:`89673`

Description
===========

Both classes :php:`\TYPO3\CMS\Extbase\Mvc\Web\Request` and :php:`\TYPO3\CMS\Extbase\Mvc\Web\Response`
have been marked as deprecated. Along with their deprecation, all relevant logic has been moved into their parent
classes :php:`\TYPO3\CMS\Extbase\Mvc\Request` and :php:`\TYPO3\CMS\Extbase\Mvc\Response`.

This is done to simplify the request/response handling of Extbase and to ease the transition towards
a PSR-7 compatible handling.


Impact
======

There is no impact yet as the "web" versions of the request and response are still used by Extbase.
The only thing that is worth mentioning is that those who implement custom requests and/or responses
should derive from the non "web" versions now.


Affected Installations
======================

All installations that implement custom request/response objects that derive from
:php:`\TYPO3\CMS\Extbase\Mvc\Web\Request` and :php:`\TYPO3\CMS\Extbase\Mvc\Web\Response`.

Those who don't change the request/response handling, will not realize this change.


Migration
=========

All installations that implement custom request/response objects that derive from
:php:`\TYPO3\CMS\Extbase\Mvc\Web\Request` and :php:`\TYPO3\CMS\Extbase\Mvc\Web\Response` should now
derive from :php:`\TYPO3\CMS\Extbase\Mvc\Request` (and override the :php:`$format` property) and
:php:`\TYPO3\CMS\Extbase\Mvc\Response` (and override the :php:`shutdown` method).

.. index:: PHP-API, NotScanned, ext:extbase
