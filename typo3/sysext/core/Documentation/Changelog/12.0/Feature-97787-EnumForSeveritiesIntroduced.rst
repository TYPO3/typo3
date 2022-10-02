.. include:: /Includes.rst.txt

.. _feature-97787-1655495723:

================================================
Feature: #97787 - Enum for severities introduced
================================================

See :issue:`97787`

Description
===========

The PHP enum :php:`\TYPO3\CMS\Core\Type\ContextualFeedbackSeverity` has been
introduced, allowing streamlined usage of severities across the codebase. At the
time of writing, this affects flash messages and status reports used in
EXT:reports.

Impact
======

The enum cases in :php:`\TYPO3\CMS\Core\Type\ContextualFeedbackSeverity` are
meant to be a drop-in replacement for the severity constants of
:php:`\TYPO3\CMS\Core\Messaging\FlashMessage` and :php:`\TYPO3\CMS\Reports\Status`.

Example
=======

Example of using the enum in a flash message:

..  code-block:: php

    $flashMessage = GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Messaging\FlashMessage::class,
        'Flash message text',
        'This is fine',
        \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::OK
    );

Example of using the enum in a status report:

..  code-block:: php

    $statusReport = GeneralUtility::makeInstance(
        \TYPO3\CMS\Reports\Status::class,
        'Lemming-o-meter',
        'Oops',
        'Not all lemmings were saved!',
        \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::WARNING
    );

.. index:: PHP-API, ext:core
