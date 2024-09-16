.. include:: /Includes.rst.txt

.. _feature-104846-1725631434:

===============================================================
Feature: #104846 - Custom field transformations for new records
===============================================================

See :issue:`104846`

Description
===========

With :issue:`103783` the new :php:`\TYPO3\CMS\Core\Domain\Record` object has been introduced, which
is an object representing a raw database record based on TCA and is usually
used in the frontend (via Fluid Templates).

Since :ref:`feature-103581-1723209131` the properties of those
:php-short:`\TYPO3\CMS\Core\Domain\Record`
objects are transformed / expanded from their raw database value into
"rich-flavored" values. Those values might be relations to e.g.
:php-short:`\TYPO3\CMS\Core\Domain\Record`,
:php-short:`\TYPO3\CMS\Core\Resource\FileReference`,
:php-short:`\TYPO3\CMS\Core\Resource\Folder` or :php:`\DateTimeImmutable` objects.

However, TYPO3 does not know about custom field meanings, e.g. latitude and
longitude information, stored in an input field or user settings stored as
JSON in an TCA type `json` field. For such custom needs, the new
PSR-14 :php:`\TYPO3\CMS\Core\Domain\Event\RecordCreationEvent` has been
introduced. It is dispatched right before a
:php-short:`\TYPO3\CMS\Core\Domain\Record` is created and
therefore allows to fully manipulate any property, even the ones already
transformed by TYPO3.

The new event is stoppable (implementing :php-short:`\Psr\EventDispatcher\StoppableEventInterface`), which
allows listeners to actually create a :php-short:`\TYPO3\CMS\Core\Domain\Record` object completely on their
own.

.. important::

    The event operates on the :php-short:`\TYPO3\CMS\Core\Domain\RecordInterface` instead of an actual
    implementation. This way, extension authors are able to set custom records,
    implementing the interface.


The new event features the following methods:

*   :php:`setRecord()` - Manually adds a :php-short:`\TYPO3\CMS\Core\Domain\RecordInterface`
    object (stops the event propagation)
*   :php:`hasProperty()` - Whether a property exists
*   :php:`setProperty()` - Add or overwrite a property
*   :php:`setProperties()` - Set properties for the :php-short:`\TYPO3\CMS\Core\Domain\RecordInterface`
*   :php:`unsetProperty()` - Unset a single property
*   :php:`getProperty()` - Get the value for a single property
*   :php:`getProperties()` - Get all properties
*   :php:`getRawRecord()` - Get the :php:`RawRecord` object
*   :php:`getSystemProperties()` - Get the calculated :php:`SystemProperties`
*   :php:`getContext()` - Get the current :php:`Context` (used to fetch the raw database row)
*   :php:`isPropagationStopped()` - Whether the event propagation is stopped

Example
=======

The event listener class, using the PHP attribute :php:`#[AsEventListener]` for
registration, creates a :php:`Coordinates` object based on the field value of
the :php:`coordinates` field for the custom :php:`maps` content type.

..  code-block:: php

    final class RecordCreationEventListener
    {
        #[AsEventListener]
        public function __invoke(\TYPO3\CMS\Core\Domain\Event\RecordCreationEvent $event): void
        {
            $rawRecord = $event->getRawRecord();

            if ($rawRecord->getMainType() === 'tt_content' && $rawRecord->getRecordType() === 'maps' && $event->hasProperty('coordinates')) {
                $event->setProperty(
                    'coordinates',
                    new Coordinates($event->getProperty('coordinates'))
                );
            }
        }
    }


Impact
======

Using the new PSR-14 :php-short:`\TYPO3\CMS\Core\Domain\Event\RecordCreationEvent`,
extension authors are able to apply any field transformation to any property before a
:php-short:`\TYPO3\CMS\Core\Domain\Record` is created.

It is even possible to completely create a new
:php-short:`\TYPO3\CMS\Core\Domain\RecordInterface` object on their own.

.. index:: PHP-API, ext:core
