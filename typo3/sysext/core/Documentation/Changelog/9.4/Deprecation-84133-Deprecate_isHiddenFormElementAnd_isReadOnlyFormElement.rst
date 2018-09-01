.. include:: ../../Includes.txt

===============================================================================
Deprecation: #84133 - Deprecate _isHiddenFormElement and _isReadOnlyFormElement
===============================================================================

See :issue:`84133`

Description
===========

The following properties have been marked as deprecated and should not be used any longer:

* :yaml:`renderingOptions._isHiddenFormElement`
* :yaml:`renderingOptions._isReadOnlyFormElement`

Those properties are available for the following form elements of the form framework:

* ContentElement
* Hidden
* Honeypot


Impact
======

The properties mentioned are still available in TYPO3 v9, but they will be dropped in TYPO3 v10.


Affected Installations
======================

Any form built with the form framework is affected as soon as those properties have been manually
added to the form definition.


Migration
=========

Usages of the above mentioned properties should be switched to the variants feature instead.

.. index:: Frontend, NotScanned, ext:form
