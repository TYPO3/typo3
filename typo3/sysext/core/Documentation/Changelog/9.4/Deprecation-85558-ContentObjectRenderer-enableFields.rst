.. include:: ../../Includes.txt

=========================================================
Deprecation: #85558 - ContentObjectRenderer->enableFields
=========================================================

See :issue:`85558`

Description
===========

The public method :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->enableFields()` has been marked as
deprecated.


Impact
======

Calling the method directly will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with custom extensions calling this method directly.


Migration
=========

As :php:`enableFields()` acts as a simple wrapper around :php:`PageRepository->enableFields()`, it is recommended
to instantiate PageRepository directly.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
