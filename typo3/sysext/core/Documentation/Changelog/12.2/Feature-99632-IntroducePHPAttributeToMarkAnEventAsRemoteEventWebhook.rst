.. include:: /Includes.rst.txt

.. _feature-99632-1674121967:

====================================================================================
Feature: #99632 - Introduce PHP attribute to mark an event as remote event (webhook)
====================================================================================

See :issue:`99632`

Description
===========

A new custom PHP attribute :php:`TYPO3\CMS\Core\Attribute\RemoteEvent` has
been added in order to be register an event as remote event.

The attribute must have a description that explains the purpose of the event.

Example
-------

..  code-block:: php

    use TYPO3\CMS\Core\Attribute\RemoteEvent;

    #[RemoteEvent(description: 'Event fired when ...')]
    final class AnyKindOfEvent
    {
        // ...
    }


Impact
======

It's now possible to tag an event as remote event by the PHP attribute
:php:`TYPO3\CMS\Core\Attribute\RemoteEvent`.

.. index:: Backend, Frontend, PHP-API, ext:core