.. include:: ../../Includes.txt

=======================================================
Deprecation: #85408 - TemplateService init() deprecated
=======================================================

See :issue:`85408`

Description
===========

Method :php:`TYPO3\CMS\Core\TypoScript\TemplateService->init()` has been marked as deprecated
and should not be used any longer.

Impact
======

Calling above method triggers a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Instances with extensions calling the above method. However, the extension scanner
is not configured to find this too generic method name.


Migration
=========

The business code of the method is done within :php:`__construct()`, an explicit call
to :php:`init()` is no longer needed and can be removed.

.. index:: PHP-API, NotScanned
