.. include:: /Includes.rst.txt

====================================================================
Breaking: #92060 - Dropped class TYPO3\CMS\Backend\View\PageTreeView
====================================================================

See :issue:`92060`

Description
===========

Class :php:`TYPO3\CMS\Backend\View\PageTreeView` has been dropped without substitution.


Impact
======

Extensions using or extending this class will throw fatal PHP errors.


Affected Installations
======================

This core internal class has been unused for a while. There is little
chance some extension depends on it. The extension scanner finds affected
extensions with a strong match.


Migration
=========

If still needed, copy the class code from an older core version to the affected extension,
adapt namespace and usages.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
