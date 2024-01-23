.. include:: /Includes.rst.txt

.. _deprecation-101475-1690546218:

====================================================
Deprecation: #101475 - Icon::SIZE_* string constants
====================================================

See :issue:`101475`

Description
===========

The string constants representing icon sizes have been marked as deprecated:

* :php:`\TYPO3\CMS\Core\Imaging\Icon::SIZE_DEFAULT`
* :php:`\TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL`
* :php:`\TYPO3\CMS\Core\Imaging\Icon::SIZE_MEDIUM`
* :php:`\TYPO3\CMS\Core\Imaging\Icon::SIZE_LARGE`
* :php:`\TYPO3\CMS\Core\Imaging\Icon::SIZE_MEGA`

The following methods have been adapted to still accept the deprecated string
constants and the new :php:`\TYPO3\CMS\Core\Imaging\IconSize` enum:

* :php:`\TYPO3\CMS\Core\Imaging\Icon->setSize()`
* :php:`\TYPO3\CMS\Core\Imaging\IconFactory->getIcon()`
* :php:`\TYPO3\CMS\Core\Imaging\IconFactory->getIconForFileExtension()`
* :php:`\TYPO3\CMS\Core\Imaging\IconFactory->getIconForRecord()`
* :php:`\TYPO3\CMS\Core\Imaging\IconFactory->getIconForResource()`

The following method returns the string value of an :php:`IconSize` enum, but
will be removed in TYPO3 v14:

* :php:`\TYPO3\CMS\Core\Imaging\Event\ModifyIconForResourcePropertiesEvent->getSize()`

Impact
======

Passing the size as a string in the above mentioned methods will trigger a
deprecation log entry.


Affected installations
======================

All installations with third-party extensions using the Icon API are affected.


Migration
=========

Migrate all usages of the aforementioned string constants to the :php:`IconSize`
as follows:

* :php:`\TYPO3\CMS\Core\Imaging\Icon::SIZE_DEFAULT` -> :php:`\TYPO3\CMS\Core\Imaging\IconSize::DEFAULT`
* :php:`\TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL` -> :php:`\TYPO3\CMS\Core\Imaging\IconSize::SMALL`
* :php:`\TYPO3\CMS\Core\Imaging\Icon::SIZE_MEDIUM` -> :php:`\TYPO3\CMS\Core\Imaging\IconSize::MEDIUM`
* :php:`\TYPO3\CMS\Core\Imaging\Icon::SIZE_LARGE` -> :php:`\TYPO3\CMS\Core\Imaging\IconSize::LARGE`
* :php:`\TYPO3\CMS\Core\Imaging\Icon::SIZE_MEGA` -> :php:`\TYPO3\CMS\Core\Imaging\IconSize::MEGA`

Also migrate from :php:`\TYPO3\CMS\Core\Imaging\Event\ModifyIconForResourcePropertiesEvent->getSize()`
to :php:`\TYPO3\CMS\Core\Imaging\Event\ModifyIconForResourcePropertiesEvent->getIconSize()`.

.. index:: Backend, PHP-API, PartiallyScanned, ext:core
