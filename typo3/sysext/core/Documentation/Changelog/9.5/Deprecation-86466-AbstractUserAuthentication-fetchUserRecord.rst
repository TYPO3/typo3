.. include:: ../../Includes.txt

=================================================================
Deprecation: #86466 - AbstractUserAuthentication->fetchUserRecord
=================================================================

See :issue:`86466`

Description
===========

Method :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->fetchUserRecord()`
has been marked as deprecated.


Impact
======

Calling the method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

The methods has been called indirectly via
:php:`TYPO3\CMS\Core\Authentication\AbstractAuthenticationService->fetchUserRecord()` as
:php:`$this->pObj->fetchUserRecord()`. It has usually not been called directly through
custom authentication services. Instances are usually not affected by the change, the
extension scanner will find possible usages.


Migration
=========

If used within authentication services, use :php:`$this->fetchUserRecord()` instead, otherwise
copy the method around.

.. index:: PHP-API, FullyScanned