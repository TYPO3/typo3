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

The :php:`UserFileInlineLabelService` class has been deprecated, since it was
only used for generating the inline label for file references in TCA type
:php:`inline`. This is now handled by the new TCA type :php:`file` directly.

The :php:`FileExtensionFilter->filterInlineChildren()` method, which was
previously used as :php:`[filter][userFunc]` to filter the available
file extensions in FormEngine as well as :php:`DataHandler` has been
deprecated. This is now done internally.

The :php:`ExtensionManagementUtility::getFileFieldTCAConfig()` method, which
was usually used to simplify configuration of FAL fields in TCA has been
deprecated as well, since the applied configuration is now handled internally.

Impact
======

Instantiating the :php:`UserFileInlineLabelService` class, as well as
calling the :php:`FileExtensionFilter->filterInlineChildren()` and
:php:`ExtensionManagementUtility::getFileFieldTCAConfig()` methods will
trigger a PHP :php:`E_USER_DEPRECATED` level error. The extension scanner
also reports any usage.

Affected installations
======================

All installations with extensions using the :php:`UserFileInlineLabelService`
class or one of the mentioned methods.

Migration
=========

Remove any usage of the :php:`UserFileInlineLabelService` class. There is no
migration available, since this FAL specific functionality is now handled
internally.

Replace any usage of :php:`FileExtensionFilter->filterInlineChildren()` with
:php:`FileExtensionFilter->filter()`. However, usage of this method in custom
extension code should usually not be necessary.

Replace any usage of :php:`ExtensionManagementUtility::getFileFieldTCAConfig()`
by directly using the new TCA type :ref:`file <feature-98479-1664537749>`.

.. index:: Backend, Database, FAL, PHP-API, TCA, PartiallyScanned, ext:backend
