.. include:: /Includes.rst.txt

.. _psr14events:

=============
PSR-14 events
=============

The following PSR-14 events are available to extend the functionality:


AfterAutoCreateRedirectHasBeenPersistedEvent
============================================

React on persisted auto-created redirects.
:ref:`More details <t3coreapi:AfterAutoCreateRedirectHasBeenPersistedEvent>`


BeforeRedirectMatchDomainEvent
==============================

Implement a custom redirect matching upon the loaded redirects or return a
matched redirect record from other sources.
:ref:`More details <t3coreapi:BeforeRedirectMatchDomainEvent>`


ModifyAutoCreateRedirectRecordBeforePersistingEvent
===================================================

Modify the redirect record before it is persisted to the database.
:ref:`More details <t3coreapi:ModifyAutoCreateRedirectRecordBeforePersistingEvent>`


ModifyRedirectManagementControllerViewDataEvent
===============================================

Modify or enrich view data for the
:php:`\TYPO3\CMS\Redirects\Controller\ManagementController`.
:ref:`More details <t3coreapi:ModifyRedirectManagementControllerViewDataEvent>`


RedirectWasHitEvent
===================

Process the matched redirect further and adjust the PSR-7 response.
:ref:`More details <t3coreapi:RedirectWasHitEvent>`


SlugRedirectChangeItemCreatedEvent
==================================

Manage the redirect sources for which redirects should be created.
:ref:`More details <t3coreapi:SlugRedirectChangeItemCreatedEvent>`
