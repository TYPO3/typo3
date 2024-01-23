.. include:: /Includes.rst.txt

.. _deprecation-102806-1704876661:

==========================================================
Deprecation: #102806 - Interfaces for PageRepository hooks
==========================================================

See :issue:`102806`

Description
===========

Using the hooks
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Core\Domain\PageRepository::class]['init']`
and :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['getPage']`,
implementations had to implement the :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepositoryInitHookInterface`
respectively :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepositoryGetPageHookInterface`
interface.

Since the mentioned hooks have been :doc:`removed <../13.0/Breaking-102806-HooksInPageRepositoryRemoved>`,
the interfaces are not in use anymore and have been marked as deprecated.

Impact
======

The removed hooks are no longer evaluated, thus the interfaces are not in use
anymore, but are kept for backwards-compatibility. As they are interfaces, they
do not trigger a deprecation warning.

Affected installations
======================

TYPO3 installations with third-party extensions utilizing the hooks and their
interfaces.

The extension scanner in the install tool can find any usages to these
interfaces and their interfaces.

Migration
=========

The PHP interfaces are still available for TYPO3 v13, so extensions can
provide a version which is compatible with TYPO3 v12 (using the hooks)
and TYPO3 v13 (using the new :doc:`PSR-14 event <../13.0/Feature-102806-BeforePageIsRetrievedEventInPageRepository>`),
at the same time. Remove any usage of the PHP interface and use the new PSR-14
event to avoid any further problems in TYPO3 v14+.

.. index:: PHP-API, FullyScanned, ext:core
