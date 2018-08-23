.. include:: ../../Includes.txt

==============================================
Deprecation: #85960 - compareUident deprecated
==============================================

See :issue:`85960`

Description
===========

Two methods related to old plain text or simple md5 related password checking have
been deprecated after those have been unused or overriden for a while already:

* php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->compareUident()`
* php:`TYPO3\CMS\Core\Authentication\AbstractAuthenticationService->compareUident()`


Impact
======

Calling the above methods will log deprecation level errors.


Affected Installations
======================

Instances using special authentication extensions are unlikely to be, but might be
affected. The extension scanner should find usages.


Migration
=========

Do not use plain text or simple md5 based password comparison in authentication services.

.. index:: PHP-API, FullyScanned