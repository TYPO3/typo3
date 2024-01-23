.. include:: /Includes.rst.txt

.. _feature-101133-1687875353:

========================================
Feature: #101133 - Native enum IconState
========================================

See :issue:`101133`

Description
===========

A new native backed enum :php:`\TYPO3\CMS\Core\Imaging\IconState` has been
introduced for streamlined usage within :php:`\TYPO3\CMS\Core\Imaging\Icon` and
:php:`\TYPO3\CMS\Core\Imaging\IconFactory`.

Impact
======

The new :php:`\TYPO3\CMS\Core\Imaging\IconState` native backed enum is meant
to be a drop-in replacement for the former
:php:`\TYPO3\CMS\Core\Type\Icon\IconState` class.


Example
=======

.. code-block:: php

    <?php

    use TYPO3\CMS\Core\Imaging\IconFactory;
    use TYPO3\CMS\Core\Imaging\IconState;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    $icon = $iconFactory->getIcon(
        'my-icon',
        TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL,
        null,
        IconState::STATE_DISABLED
    );

.. index:: Backend, Frontend, ext:core
