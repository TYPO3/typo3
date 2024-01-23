.. include:: /Includes.rst.txt

.. _deprecation-101133-1687875352:

======================================
Deprecation: #101133 - IconState class
======================================

See :issue:`101133`

Description
===========

The class :php:`\TYPO3\CMS\Core\Type\Icon\IconState` is marked
as deprecated.

Impact
======

The class :php:`\TYPO3\CMS\Core\Type\Icon\IconState` will be removed in
TYPO3 v14.0. Passing an instance of this class to
:php:`\TYPO3\CMS\Core\Imaging\IconFactory->getIcon()` will lead to a deprecation
level log entry.

Affected installations
======================

All installations using the class :php:`\TYPO3\CMS\Core\Type\Icon\IconState`.

Migration
=========

.. code-block:: php

    // Before
    $state = \TYPO3\CMS\Core\Type\Icon\IconState::cast(
        \TYPO3\CMS\Core\Type\Icon\IconState::STATE_DEFAULT
    );

    // After
    $state = \TYPO3\CMS\Core\Imaging\IconState::STATE_DEFAULT;

.. index:: Backend, NotScanned, ext:core
