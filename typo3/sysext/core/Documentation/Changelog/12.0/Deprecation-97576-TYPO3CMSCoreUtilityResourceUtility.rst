.. include:: /Includes.rst.txt

.. _deprecation-97576-1651949640:

================================================================
Deprecation: #97576 - TYPO3\\CMS\\Core\\Utility\\ResourceUtility
================================================================

See :issue:`97576`

Description
===========

The class :php:`TYPO3\CMS\Core\Utility\ResourceUtility` has no usage in the Core
and is therefore marked as deprecated.

Impact
======

Calling any method of the class :php:`TYPO3\CMS\Core\Utility\ResourceUtility`
will trigger a PHP :php:`E_USER_DEPRECATED` error.

Affected installations
======================

All installations using any method of :php:`TYPO3\CMS\Core\Utility\ResourceUtility`
in their own code.

Migration
=========

There is no direct replacement of this class. Extensions that depend on any of the class'
methods should implement them in their codebase.

.. index:: PHP-API, FullyScanned, ext:core
