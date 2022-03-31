.. include:: /Includes.rst.txt

========================================================================
Breaking: #88574 - 4th parameter of PageRepository->enableFields removed
========================================================================

See :issue:`88574`

Description
===========

The fourth parameter of :php:`TYPO3\CMS\Core\Domain\Repository\PageRepository->enableFields()` was meant to filter out versioned records
which are in Live Workspace (versioning, not workspaces). Although the method has largely been superseded
with Doctrine DBAL's Restrictions, it is still used in some places.

With the introduction of the Context API, new PageRepository instances can be created to fetch multiple variants
of certain aspects, instead of modifying existing public properties. Therefore the fourth argument has been removed.


Impact
======

Calling the method above with the fourth parameter set to true has no effect anymore, and will
trigger a PHP :PHP:`E_NOTICE` error.


Affected Installations
======================

Any TYPO3 installation dealing with non-workspace versioning in Frontend requests with third-party extension
still relying on non-workspace versioning.


Migration
=========

The fourth parameter on any method call can be removed (if set to "false"), or should be replaced with a
separate instance of :php:`TYPO3\CMS\Core\Domain\Repository\PageRepository` with a custom Context.

.. index:: Frontend, PHP-API, FullyScanned
