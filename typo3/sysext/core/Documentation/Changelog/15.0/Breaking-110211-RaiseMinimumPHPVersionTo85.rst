.. include:: /Includes.rst.txt

.. _breaking-110211-1784210220:

====================================================
Breaking: #110211 - Raise minimum PHP version to 8.5
====================================================

See :issue:`110211`

Description
===========

The minimum PHP version required to run TYPO3 has been raised to PHP 8.5.

Impact
======

The TYPO3 Core codebase and extensions tailored for this version can use
features implemented with PHP up to and including 8.5. Running TYPO3 with
older PHP versions will trigger fatal errors.

Affected installations
======================

All installations running a PHP version lower than 8.5.

Migration
=========

Update the PHP platform to PHP 8.5 before upgrading to this TYPO3 version.
Previous TYPO3 versions already support PHP 8.5, which allows upgrading
the PHP platform in a first step and TYPO3 in a second step.

.. index:: PHP-API, NotScanned, ext:core
