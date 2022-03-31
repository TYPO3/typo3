
.. include:: /Includes.rst.txt

================================================================
Breaking: #67402 - Extbase AbstractDomainObject initializeObject
================================================================

See :issue:`67402`

Description
===========

Method `initializeObject()` has been removed from `TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject`.


Impact
======

Possible fatal error in Extbase if objects are thawed from persistence.


Affected Installations
======================

Domain objects extending AbstractDomainObject and calling `parent::initializeObject()`.
This is relatively unlikely since the default implementation of `initializeObject()` is empty.


Migration
=========

Remove calls to `parent::initializeObject()` from own `initializeObject()` implementations.


.. index:: PHP-API, ext:extbase
