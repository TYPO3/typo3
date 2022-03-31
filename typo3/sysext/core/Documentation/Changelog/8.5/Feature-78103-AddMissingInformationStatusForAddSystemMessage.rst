.. include:: /Includes.rst.txt

=====================================================================
Feature: #78103 - Add missing information status for addSystemMessage
=====================================================================

See :issue:`78103`

Description
===========

Adds the possibility to pass and set status parameter `TYPO3\CMS\Backend\Toolbar\Enumeration\InformationStatus`
through `addSystemInformation()` in `SystemInformationToolbarItem`.


Impact
======

All system information added by `addSystemInformation()` will now pass `InformationStatus::STATUS_NOTICE`
as default value.

.. index:: Backend, PHP-API
