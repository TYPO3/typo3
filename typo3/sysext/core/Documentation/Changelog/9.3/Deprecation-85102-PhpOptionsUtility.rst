.. include:: /Includes.rst.txt

=======================================
Deprecation: #85102 - PhpOptionsUtility
=======================================

See :issue:`85102`

Description
===========

The PHP class :php:`\TYPO3\CMS\Core\Utility\PhpOptionsUtility` has been marked as deprecated.

The only purpose for this class was to check for available session handling in the installer.


Impact
======

Calling any method in this class will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Installations checking for session handling in custom extensions.


Migration
=========

Implement the :php:`filter_var()` and :php:`ini_get()` used in the PhpOptionsUtility wrapper yourself.

.. index:: PHP-API, FullyScanned, ext:core
