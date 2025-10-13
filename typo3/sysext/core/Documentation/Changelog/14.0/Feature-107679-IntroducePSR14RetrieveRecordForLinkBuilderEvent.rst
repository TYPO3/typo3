.. include:: /Includes.rst.txt

.. _feature-107679-1760353131:

==========================================================================
Feature: #107679 - PSR-14 event for custom record retrieval in LinkBuilder
==========================================================================

See :issue:`107679`

Description
===========

A new PSR-14 event :php:`TYPO3\CMS\Frontend\Event\BeforeDatabaseRecordLinkResolvedEvent`
has been introduced to retrieve a record using custom code in the
:php:`TYPO3\CMS\Frontend\Typolink\DatabaseRecordLinkBuilder`.

The event is dispatched with :php:`$record` set to :php:`null`. If an event
listener retrieves a record from the database, it should set the :php:`$record`
property to the record as an array. This will stop the event propagation and
cause the default record retrieval logic in
:php:`TYPO3\CMS\Frontend\Typolink\DatabaseRecordLinkBuilder` to be skipped.

..  important::

    The event is stoppable: Setting the :php:`$record` property to a non-null
    value stops event propagation and skips the default record retrieval logic.

Note that the custom code needs to take care - if relevant - of all aspects
normally handled by :php:`TYPO3\CMS\Frontend\Typolink\DatabaseRecordLinkBuilder`,
such as record visibility, language overlay or version overlay.

The event provides the following public properties (all read-only,
except for :php:`$record`):

* :php:`$linkDetails`: Information about the link being processed
* :php:`$databaseTable`: The name of the database the record belongs to
* :php:`$typoscriptConfiguration`: The full TypoScript link handler configuration
* :php:`$tsConfig`: The full TSconfig link handler configuration
* :php:`$request`: The current request object
* :php:`$record`: The database record as an array (initially :php:`null`)

Example
=======

An example event listener could look like:

..  code-block:: php

    use TYPO3\CMS\Frontend\Event\BeforeDatabaseRecordLinkResolvedEvent;

    class MyEventListener {

        #[AsEventListener(
            identifier: 'my-extension/before-database-record-link-resolved',
        )]
        public function __invoke(BeforeDatabaseRecordLinkResolvedEvent $event): void
        {
            // Retrieve the record from the database as an array
            $result = ...
            if ($result !== false) {
                // Setting the record stops event propagation and
                // skips the default record retrieval logic
                $event->record = $result;
            }
        }
    }

Impact
======

The new event makes it possible to implement custom logic, for example,
specific frontend access issues when retrieving a record linked to in a typolink.

.. index:: Frontend
