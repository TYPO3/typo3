.. include:: /Includes.rst.txt

===========================================
Deprecation: #85761 - SaltedPasswordService
===========================================

See :issue:`85761`

Description
===========

Class :php:`TYPO3\CMS\Saltedpasswords\SaltedPasswordService` has been deprecated and
should not be used any longer.


Impact
======

Instantiating :php:`SaltedPasswordService` will log a deprecation message.


Affected Installations
======================

This class is usually not called by extensions, it is unlikely instances are affected by this.


Migration
=========

The service has been migrated into the the basic core authentication service chain for
frontend and backend. Usually no migration is needed.


.. index:: PHP-API, FullyScanned, ext:saltedpasswords
