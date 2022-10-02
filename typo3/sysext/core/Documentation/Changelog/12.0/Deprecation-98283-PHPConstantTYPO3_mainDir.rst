.. include:: /Includes.rst.txt

.. _deprecation-98283-1662557018:

================================================
Deprecation: #98283 - PHP Constant TYPO3_mainDir
================================================

See :issue:`98283`

Description
===========

The PHP Constant :php:`TYPO3_mainDir` which is defined as :file:`typo3/` has been marked as deprecated.

Impact
======

Accessing the constant will stop working in TYPO3 v13. No deprecation warning is thrown,
but the Extension Scanner will detect any usage in your installation.

Affected installations
======================

TYPO3 installations with custom third-party extensions using the constant directly, which is
highly unlikely.

Migration
=========

It is recommended to use the :php:`BackendEntryPointResolver` class when needing
to direct to the TYPO3 Backend. All other common APIs such as the Backend Router already
calculate the path automatically anyway.

.. index:: PHP-API, FullyScanned, ext:core
