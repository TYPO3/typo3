.. include:: /Includes.rst.txt

====================================================================
Deprecation: #87332 - Avoid runtime reflection calls in ObjectAccess
====================================================================

See :issue:`87332`

Description
===========

Class :php:`\TYPO3\CMS\Extbase\Reflection\ObjectAccess` uses reflection to make non public properties gettable and settable.
This behaviour is triggered by setting the argument :php:`$forceDirectAccess` of methods

* :php:`getProperty`
* :php:`getPropertyInternal`
* :php:`setProperty`

to :php:`true`. Triggering this behaviour has been marked as deprecated and will be removed in TYPO3 11.0.

Method :php:`\TYPO3\CMS\Extbase\Reflection\ObjectAccess::buildSetterMethodName` has been marked as deprecated and will be removed in TYPO3 11.0.


Impact
======

1) Accessing non public properties via the mentioned methods will no longer work in TYPO3 11.0.

2) Calling :php:`\TYPO3\CMS\Extbase\Reflection\ObjectAccess::buildSetterMethodName` will no longer work in TYPO3 11.0.


Affected Installations
======================

1) All installations that use the mentioned methods with argument :php:`$forceDirectAccess` set to :php:`true`.

2) All installations that call :php:`\TYPO3\CMS\Extbase\Reflection\ObjectAccess::buildSetterMethodName`.


Migration
=========

1) Make sure the affected property is accessible by either making it public or providing getters/hassers/issers or setters
(:php:`getProperty()`, :php:`hasProperty()`, :php:`isProperty()`, :php:`setProperty()`).

2) Build setter names manually: :php:`$setterMethodName = 'set' . ucfirst($propertyName);`

.. index:: PHP-API, FullyScanned, ext:extbase
