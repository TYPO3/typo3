.. include:: /Includes.rst.txt

.. _deprecation-101151-1688113521:

================================================
Deprecation: #101151 - DuplicationBehavior class
================================================

See :issue:`101151`

Description
===========

The class :php:`\TYPO3\CMS\Core\Resource\DuplicationBehavior` is marked
as deprecated.

Impact
======

The class :php:`\TYPO3\CMS\Core\Resource\DuplicationBehavior` will be removed in
TYPO3 v14.0.

Affected installations
======================

All installations using the class :php:`\TYPO3\CMS\Core\Resource\DuplicationBehavior`.

Migration
=========

.. code-block:: php

    // Before
    $behaviour = \TYPO3\CMS\Core\Resource\DuplicationBehavior::cast(
        \TYPO3\CMS\Core\Resource\DuplicationBehavior::RENAME
    );

    // After
    $behaviour = \TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior::RENAME;

.. index:: Backend, FullyScanned, ext:backend, ext:core, ext:filelist, ext:impexp
