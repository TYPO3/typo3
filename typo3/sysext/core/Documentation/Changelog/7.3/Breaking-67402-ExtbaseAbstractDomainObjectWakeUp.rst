
.. include:: ../../Includes.txt

==========================================================
Breaking: #67402 - Extbase AbstractDomainObject __wakeup()
==========================================================

See :issue:`67402`

Description
===========

Method `__wakeup()` in classes extending `TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject`
is no longer called if objects are created when fetched from persistence.


Affected Installations
======================

An instance is affected if own domain objects extending AbstractDomainObject
implement own `__wakeup()` methods. Those methods are no longer called.


Migration
=========

Move initialization code from `__wakeup()` to `initializeObject()`. As a bonus, dependencies have been injection at
this point already.


.. index:: PHP-API, ext:extbase
