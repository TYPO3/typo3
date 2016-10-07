
.. include:: ../../Includes.txt

==================================
Deprecation: #75371 - array2xml_cs
==================================

See :issue:`75371`

Description
===========

The method :php:`GeneralUtility::array2xml_cs()` has been marked as deprecated.


Impact
======

Using the method :php:`GeneralUtility::array2xml_cs()` will trigger a deprecation log entry.


Affected Installations
======================

All installations with third party extensions using this method are affected.


Migration
=========

Use :php:`GeneralUtility::array2xml()` instead. The XML declaration must be written manually.

Example code:
.. code-block:: php

   // Deprecated
   $bodyText = GeneralUtility::array2xml_cs($array, 'phparray');

   // Migrated
   $bodyText = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>' . LF . GeneralUtility::array2xml($array, '', 0, 'phparray');

.. index:: PHP-API