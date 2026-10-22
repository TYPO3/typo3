.. include:: /Includes.rst.txt

.. _deprecation-98479-1664622350:

=====================================================================
Deprecation: #98479 - Deprecated file reference related functionality
=====================================================================

See :issue:`98479`

Description
===========

With the introduction of the new TCA type :php:`file`, a couple of cross
dependencies have been deprecated, mainly related to FormEngine.

The :php-short:`\TYPO3\CMS\Core\Resource\Service\UserFileInlineLabelService` class has been deprecated, since it was
only used for generating the inline label for file references in TCA type
:php:`inline`. This is now handled by the new TCA type :php:`file` directly.

The :php-short:`\TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter->filterInlineChildren()` method, which was
previously used as :php:`[filter][userFunc]` to filter the available
file extensions in FormEngine as well as :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler` has been
deprecated. This is now done internally.

The :php-short:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig()` method, which
was usually used to simplify configuration of FAL fields in TCA has been
deprecated as well, since the applied configuration is now handled internally.

Impact
======

Instantiating the :php-short:`\TYPO3\CMS\Core\Resource\Service\UserFileInlineLabelService` class, as well as
calling the :php-short:`\TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter->filterInlineChildren()` and
:php-short:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig()` methods will
trigger a PHP :php:`E_USER_DEPRECATED` level error. The extension scanner
also reports any usage.

Affected installations
======================

All installations with extensions using the :php-short:`\TYPO3\CMS\Core\Resource\Service\UserFileInlineLabelService`
class or one of the mentioned methods.

Migration
=========

Remove any usage of the :php-short:`\TYPO3\CMS\Core\Resource\Service\UserFileInlineLabelService` class. There is no
migration available, since this FAL specific functionality is now handled
internally.

Replace any usage of :php-short:`\TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter->filterInlineChildren()` with
:php-short:`\TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter->filter()`. However, usage of this method in custom
extension code should usually not be necessary.

Replace any usage of :php-short:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig()`
by directly using the new TCA type :ref:`file <feature-98479-1664537749>`.

.. index:: Backend, Database, FAL, PHP-API, TCA, PartiallyScanned, ext:backend
