.. include:: /Includes.rst.txt

===========================================
Deprecation: #94619 - Extbase ObjectManager
===========================================

See :issue:`94619`

Description
===========

The Extbase ObjectManager as the legacy core object lifecycle and
dependency injection solution has been marked discouraged with TYPO3 v10 and
its introduction of the Symfony based dependency injection solution already.

TYPO3 v11 no longer uses the Extbase ObjectManager - only in a couple
of places as fallback for third party extensions. The entire construct has now
been marked as deprecated and will be removed with v12:

* :php:`TYPO3\CMS\Extbase\Object\ObjectManagerInterface` - Main interface
* :php:`TYPO3\CMS\Extbase\Object\ObjectManager` - Main implementation
* :php:`TYPO3\CMS\Extbase\Object\Container\Container` - Internal lifecycle management
* :php:`TYPO3\CMS\Extbase\Object\Exception` - Base exception
* :php:`TYPO3\CMS\Extbase\Object\Exception\CannotBuildObjectException` - Detail exception
* :php:`TYPO3\CMS\Extbase\Object\Container\Exception\CannotReconstituteObjectException` - Detail exception
* :php:`TYPO3\CMS\Extbase\Object\Container\Exception\UnknownObjectException` - Detail exception
* :php:`TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException` - Detail exception, obsolete
  by deprecation of Extbase signal slot dispatcher already.
* :php:`TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException`  - Detail exception, obsolete
  by deprecation of Extbase signal slot dispatcher already.


Impact
======

Directly or indirectly calling :php:`\TYPO3\CMS\Extbase\Object\ObjectManager->get()`
will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Extensions that have been properly cleaned up for TYPO3 v10 compatibility are not affected.

Extensions still relying on Extbase ObjectManager are strongly encouraged to
switch to :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance()` and
Symfony based DI instead.

The extension scanner will find usages of the above classes and interfaces and shows
them as deprecated with a strong match.


Migration
=========

Documentation of migration paths have been established with TYPO3 v10
documentation already. The :ref:`TYPO3 explained dependency injection section<t3coreapi:DependencyInjection>`
and the :ref:`ObjectManager->get() v10 changelog entry <changelog-Deprecation-90803-ObjectManagerGet>`
are especially helpful.

.. index:: PHP-API, FullyScanned, ext:extbase
