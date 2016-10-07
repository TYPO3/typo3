
.. include:: ../../Includes.txt

==================================================================================
Breaking: #73719 - Unused JavaScript configuration options for the Backend removed
==================================================================================

See :issue:`73719`

Description
===========

The following JavaScript options from TYPO3.configuration and the global JavaScript property `TS` have been removed:

.. code-block:: javascript

	TYPO3.configuration.PATH_typo3
	TYPO3.configuration.PATH_typo3_enc
	TYPO3.configuration.userUid
	TYPO3.configuration.securityLevel
	TYPO3.configuration.TYPO3_mainDir
	TYPO3.configuration.veriCode
	TYPO3.configuration.denyFileTypes
	TS.PATH_typo3
	TS.PATH_typo3_enc
	TS.securityLevel
	TS.veriCode
	TS.denyFileTypes
	TS.decimalSign


Impact
======

Calling any of the JavaScript options above from within JavaScript will result in a undefined JavaScript error.


Affected Installations
======================

Any TYPO3 installation using a legacy TYPO3 extension that uses these options within JavaScript.


Migration
=========

Remove the values from the JavaScript code or provide a PHP alternative to make the options available again for
JavaScript, if needed.

.. index:: JavaScript, Backend
