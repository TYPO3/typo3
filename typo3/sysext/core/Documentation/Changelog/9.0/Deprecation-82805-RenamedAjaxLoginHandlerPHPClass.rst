.. include:: ../../Includes.txt

========================================================
Deprecation: #82805 - Renamed AjaxLoginHandler PHP class
========================================================

See :issue:`82805`

Description
===========

The PHP class :php:`TYPO3\CMS\Backend\AjaxLoginHandler` is renamed to the proper class name
:php:`TYPO3\CMS\Backend\Controller\AjaxLoginController` as its a controller class with actions.


Impact
======

Using the old PHP class is possible but any usages should be moved to the new class name.


Affected Installations
======================

Any TYPO3 instances using the PHP class directly in an extension.


Migration
=========

A extension scanner already checks for the old class name. A simple renaming of the class name
to the new class :php:`TYPO3\CMS\Backend\Controller\AjaxLoginController` is sufficient.

.. index:: PHP-API, Backend, FullyScanned