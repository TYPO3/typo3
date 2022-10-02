.. include:: /Includes.rst.txt

.. _breaking-96351:

============================================================================
Breaking: #96351 - Unused TemplateService->updateRootlineData method removed
============================================================================

See :issue:`96351`

Description
===========

The PHP method :php:`TemplateService->updateRootlineData()` has been removed.

It was used as a workaround to update the fetched rootline with
translated pages until TYPO3 v10. This was necessary because the
TypoScript information contained the language information, and
then the page translations were loaded accordingly.

Since TYPO3 v11 the language is resolved earlier, at the same
time as the page ID, and the mechanism became obsolete.

Impact
======

Calling the method in PHP will throw a fatal PHP error, as the method does not exist anymore.

Affected Installations
======================

TYPO3 installations, mainly legacy installations with legacy
extensions using this method to boot up their own TypoScript
parsing.

Migration
=========

Calling this method is not needed anymore and can be removed
from the affected code.

.. index:: Frontend, PHP-API, FullyScanned, ext:core
