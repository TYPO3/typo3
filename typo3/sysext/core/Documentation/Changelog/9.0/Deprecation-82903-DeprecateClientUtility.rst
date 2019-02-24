.. include:: ../../Includes.txt

=============================================
Deprecation: #82903 - Deprecate ClientUtility
=============================================

See :issue:`82903`

Description
===========

Class :php:`\TYPO3\CMS\Core\Utility\ClientUtility` has been marked as deprecated and should not be
used any longer.


Impact
======

Using this class will throw a deprecation warning.


Affected Installations
======================

Instances with extensions using the methods of the class:

- :php:`getBrowserInfo`
- :php:`getVersion`


Migration
=========

Use a 3rd party API like https://github.com/piwik/device-detector

.. index:: PHP-API, FullyScanned
