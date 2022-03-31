
.. include:: /Includes.rst.txt

=============================================================
Deprecation: #69877 - Use ModuleTemplate API for ext:filelist
=============================================================

See :issue:`69877`

Description
===========

Method `getButtonsAndOtherMarkers` of class `\TYPO3\CMS\Filelist\FileList` has been marked as deprecated.


Impact
======

The method should not be used any longer and will be removed with TYPO3 CMS 8.


Affected Installations
======================

All third party extensions using the mentioned method.


Migration
=========

Use the ModuleTemplate API instead.


.. index:: PHP-API, Backend
