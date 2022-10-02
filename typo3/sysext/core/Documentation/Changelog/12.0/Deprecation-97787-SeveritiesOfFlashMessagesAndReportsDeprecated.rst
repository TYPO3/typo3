.. include:: /Includes.rst.txt

.. _deprecation-97787-1655495192:

=========================================================================
Deprecation: #97787 - Severities of flash messages and reports deprecated
=========================================================================

See :issue:`97787`

Description
===========

With the introduction of :php:`\TYPO3\CMS\Core\Type\ContextualFeedbackSeverity`,
the existing severity constants of :php:`\TYPO3\CMS\Core\Messaging\FlashMessage`
and :php:`\TYPO3\CMS\Reports\Status` have been marked as deprecated.

Impact
======

Passing the constants as listed below to the constructor of
:php:`\TYPO3\CMS\Core\Messaging\FlashMessage` will trigger a PHP :php:`E_USER_DEPRECATED` error:

* :php:`\TYPO3\CMS\Core\Messaging\FlashMessage::NOTICE`
* :php:`\TYPO3\CMS\Core\Messaging\FlashMessage::INFO`
* :php:`\TYPO3\CMS\Core\Messaging\FlashMessage::OK`
* :php:`\TYPO3\CMS\Core\Messaging\FlashMessage::WARNING`
* :php:`\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR`

Passing the constants as listed below to the constructor of
:php:`\TYPO3\CMS\Reports\Status` will trigger a PHP :php:`E_USER_DEPRECATED` error:

* :php:`\TYPO3\CMS\Reports\Status::NOTICE`
* :php:`\TYPO3\CMS\Reports\Status::INFO`
* :php:`\TYPO3\CMS\Reports\Status::OK`
* :php:`\TYPO3\CMS\Reports\Status::WARNING`
* :php:`\TYPO3\CMS\Reports\Status::ERROR`

Affected installations
======================

All installations with 3rd party plugins using the aforementioned constants are
affected.

Migration
=========

Use the cases of the :php:`\TYPO3\CMS\Core\Type\ContextualFeedbackSeverity` enum.
The following cases are available:

* :php:`\TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::NOTICE`
* :php:`\TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::INFO`
* :php:`\TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::OK`
* :php:`\TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::WARNING`
* :php:`\TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR`

.. index:: PHP-API, FullyScanned, ext:core
