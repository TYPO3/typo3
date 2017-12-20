
.. include:: ../../Includes.txt

========================================================
Breaking: #68814 - Remove of base constant TYPO3_URL_ORG
========================================================

See :issue:`68814`

Description
===========

Base constant TYPO3_URL_ORG defined in SystemEnvironmentBuilder::defineBaseConstants() has been removed.
It was for internal usage only and defined at 2 places in the core.


Impact
======

Constant TYPO3_URL_ORG no longer exists.


Migration
=========

Use TYPO3_URL_GENERAL instead.


.. index:: PHP-API
