.. include:: /Includes.rst.txt

.. _breaking-JSConfirmation-1687503100:

======================================================
Breaking: #100229 - Convert JSConfirmation to a BitSet
======================================================

See :issue:`100229`

Description
===========

:php:`\TYPO3\CMS\Core\Type\Bitmask\JSConfirmation` is now
extending the :php:`TYPO3\CMS\Core\Type\BitSet` class instead of
:php:`TYPO3\CMS\Core\TypeEnumeration\Enumeration`.

Impact
======

Since :php:`JSConfirmation` is now extending the class :php:`TYPO3\CMS\Core\Type\BitSet`
it's no longer possible to call the following public methods:
- :php:`matches`
- :php:`setValue`
- :php:`isValid`

The only static method left is:
:php:`compare`

Affected installations
======================

Custom TYPO3 extensions calling public methods methods:
- :php:`matches`
- :php:`setValue`
- :php:`isValid`

Custom TYPO3 extensions calling static methods in
:php:`\TYPO3\CMS\Core\Type\Bitmask\JSConfirmation::method`
except for the method :php:`\TYPO3\CMS\Core\Type\Bitmask\JSConfirmation::compare`

The following method has changed first argument to an :php:`int`
- :php:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->jsConfirmation()`

Migration
=========

There is no migration for the methods:
- :php:`matches`
- :php:`setValue`
- :php:`isValid`

Remove existing calls to static methods
:php:`\TYPO3\CMS\Core\Type\Bitmask\JSConfirmation::method`
except for the method :php:`\TYPO3\CMS\Core\Type\Bitmask\JSConfirmation::compare`

Ensure an int value is passed to:
- :php:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->jsConfirmation()`

.. index:: Backend, NotScanned, ext:backend, ext:core, ext:filelist
