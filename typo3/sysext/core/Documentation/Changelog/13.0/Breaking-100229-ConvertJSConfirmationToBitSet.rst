.. include:: /Includes.rst.txt

.. _breaking-JSConfirmation-1687503100:

======================================================
Breaking: #100229 - Convert JSConfirmation to a BitSet
======================================================

See :issue:`100229`

Description
===========

The class :php:`\TYPO3\CMS\Core\Type\Bitmask\JSConfirmation` is replaced by
:php:`\TYPO3\CMS\Core\Authentication\JsConfirmation`. The new class is
extending the :php:`\TYPO3\CMS\Core\Type\BitSet` class instead of
:php:`\TYPO3\CMS\Core\TypeEnumeration\Enumeration`.

Impact
======

Since :php:`JSConfirmation` is now extending the class :php:`\TYPO3\CMS\Core\Type\BitSet`
it's no longer possible to call the following public methods:

- :php:`matches()`
- :php:`setValue()`
- :php:`isValid()`

The only static method left is:
:php:`compare()`

Affected installations
======================

Custom TYPO3 extensions calling public methods:

- :php:`matches()`
- :php:`setValue()`
- :php:`isValid()`

Custom TYPO3 extensions calling static methods in
:php:`\TYPO3\CMS\Core\Type\Bitmask\JSConfirmation`
except for the method :php:`\TYPO3\CMS\Core\Type\Bitmask\JSConfirmation::compare()`.

Custom TYPO3 extensions calling
:php:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->jsConfirmation()`,
if first argument passed is not an :php:`int`.

Migration
=========

Replace existing usages of :php:`\TYPO3\CMS\Core\Type\Bitmask\JSConfirmation`
with :php:`\TYPO3\CMS\Core\Authentication\JsConfirmation`.

There is no migration for the methods:

- :php:`matches()`
- :php:`setValue()`
- :php:`isValid()`

Remove existing calls to static methods
:php:`\TYPO3\CMS\Core\Type\Bitmask\JSConfirmation::method()`
and where :php:`JSConfirmation::compare()` is used, replace the namespace from
:php:`\TYPO3\CMS\Core\Type\Bitmask\JSConfirmation` to
:php:`\TYPO3\CMS\Core\Authentication\JsConfirmation`.

Ensure an int value is passed to:

- :php:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->jsConfirmation()`

.. index:: Backend, NotScanned, ext:backend, ext:core, ext:filelist
