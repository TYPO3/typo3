.. include:: ../../Includes.txt

===============================================================
Deprecation: #86338 - Change visibility of PageRepository->init
===============================================================

See :issue:`86338`

Description
===========

The :php:`PageRepository::init()` method is now called implicitly within the constructor.


Impact
======

Calling the method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installation with extensions directly calling the :php:`PageRepository::init()` method.


Migration
=========

Remove the call to the :php:`PageRepository::init()` function. The constructor is taking care of calling the method.

.. index:: NotScanned, ext:frontend
