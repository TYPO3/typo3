.. include:: /Includes.rst.txt

===============================================================
Deprecation: #86338 - Change visibility of PageRepository->init
===============================================================

See :issue:`86338`

Description
===========

The method :php:`TYPO3\CMS\Frontend\Page\PageRepository::init()` is now called implicitly within the constructor.


Impact
======

Calling the method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installation with extensions directly calling :php:`TYPO3\CMS\Frontend\Page\PageRepository::init()`.


Migration
=========

Remove the method call. The constructor is taking care of calling the method.

.. index:: ext:frontend, NotScanned
