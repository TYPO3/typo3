
.. include:: /Includes.rst.txt

=========================================
Deprecation: #81201 - EidUtility::initTCA
=========================================

See :issue:`81201`

Description
===========

The static PHP method :php:`EidUtility::initTCA()` has been marked as deprecated, because the full
global TCA array is available at any eID request already.


Impact
======

Calling this method triggers a deprecation log entry.


Affected Installations
======================

Any TYPO3 installation with an extension having a custom eID script registered that uses this method.


Migration
=========

The method call is superfluous and can be removed from the caller script.

.. index:: Frontend, PHP-API, TCA, FullyScanned
