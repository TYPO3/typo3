.. include:: /Includes.rst.txt

.. _breaking-101133-1687875355:

============================================
Breaking: #101133 - Icon->state changed type
============================================

See :issue:`101133`

Description
===========

The protected property :php:`\TYPO3\CMS\Core\Imaging\Icon->state` holds now a native
enum :php:`\TYPO3\CMS\Core\Imaging\IconState` instead of an instance of
:php:`\TYPO3\CMS\Core\Type\Icon\IconState`.

Impact
======

Custom extensions calling :php:`\TYPO3\CMS\Core\Imaging\Icon->getState()` will
receive an enum now, which will most probably lead to PHP errors in the runtime.

Custom extensions calling :php:`\TYPO3\CMS\Core\Imaging\Icon->setState()` with an
instance of :php:`\TYPO3\CMS\Core\Type\Icon\IconState` will receive a PHP
TypeError.

Affected installations
======================

Custom extensions calling :php:`\TYPO3\CMS\Core\Imaging\Icon->getState()` or
:php:`\TYPO3\CMS\Core\Imaging\Icon->setState()`.

Migration
=========

Adapt your code to handle the native enum :php:`\TYPO3\CMS\Core\Imaging\IconState`.

.. code-block:: php

    use TYPO3\CMS\Core\Imaging\Icon;
    use TYPO3\CMS\Core\Type\Icon\IconState;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    // Before
    $icon = GeneralUtility::makeInstance(Icon::class);
    $icon->setState(IconState::cast(IconState::STATE_DEFAULT));
    $state = $icon->getState();
    $stateValue = (string)$state;

    // After
    $icon = GeneralUtility::makeInstance(Icon::class);
    $icon->setState(IconState::STATE_DEFAULT);

    $state = $icon->getState();
    $stateValue = $state->value;

.. index:: Backend, NotScanned, ext:core
