.. include:: /Includes.rst.txt

.. _breaking-98158-1660740286:

======================================
Breaking: #98158 - Update to Symfony 6
======================================

See :issue:`98158`

Description
===========

TYPO3 Core now ships with Symfony 6.1. Previously TYPO3 v11 used Symfony Components
in version 5.4.

Impact
======

Some PHP code now might need to consider other types, especially regarding PHP
classes which might be extended or used directly, where types for arguments are used.

One example is that all CLI Commands (used in custom extensions) now need to define
an :php:`int` as return type of the :php:`execute()` method, otherwise the CLI
command will not be executed anymore.

Affected installations
======================

TYPO3 Installations with extensions making heavy use of Symfony components directly.

Migration
=========

Functionality such as the CLI Commands can already put in place for TYPO3 versions prior to
TYPO3 v12. It is recommended to use tools such as Rector to detect possible problems when
having extensions interacting with Symfony Components directly.

.. index:: PHP-API, NotScanned, ext:core
