.. include:: /Includes.rst.txt

.. _important-102786-1704811367:

==================================================
Important: #102786 - Updated Dependency: Symfony 7
==================================================

See :issue:`102786`

Description
===========

TYPO3 v13 ships with Symfony components with at least version 7.0. Next to new
features and bugfixes to be released in future Symfony 7 versions, the security
support for Symfony 7 LTS reaches end-of-life in November 2028, allowing TYPO3
Core to run a stable and secure Symfony component library underneath.

Custom extensions relying on older versions of Symfony components need to adapt,
see upgrade guides for Symfony on how to migrate.

.. index:: PHP-API, ext:core
