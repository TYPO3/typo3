.. include:: /Includes.rst.txt

.. _feature-102337-1715591177:

==========================================================================
Feature: #102337 - PSR-14 event for modifying record list download presets
==========================================================================

See :issue:`102337`

Description
===========

A new PSR-14 event :php:`TYPO3\CMS\Backend\RecordList\Event\BeforeRecordDownloadPresetsAreDisplayedEvent`
has been introduced to manipulate the list of available download presets in
the :guilabel:`Web > List` module.

See :ref:`feature-102337-1712597691` for a detailed description of how to
utilize presets when downloading a set of records from the backend in CSV
or JSON format.

The event class offers the following methods:

- :php:`getPresets()`: Returns a list of presets set via TSconfig
- :php:`setPresets()`: Sets a modified list of presets.
- :php:`getDatabaseTable()`: Returns the database table name that a preset applies to.
- :php:`getRequest()`: Returns the PSR Request object for the context of the request.
- :php:`getId()`: Returns the page ID of the originating page.

Note that the event is dispatched for one specific database table. If
an event listener is created to attach presets to different tables, the
listener method must check for the table name, as shown in the example below.

If no download presets exist for a given table, the PSR-14 event can still
be used to modify and add presets to it via the :php:`setPresets()` method.

The array passed from :php:`getPresets()` to :php:`setPresets()` can contain
an array collection of :php:`TYPO3\CMS\Backend\RecordList\DownloadPreset`
objects with the array key using the preset label.
The existing presets can be retrieved with these getters:

- :php:`$preset->getLabel()`: Name of the preset (can utilize LLL translations), optional.
- :php:`$preset->getColumns()`: Array of database table column names.
- :php:`$preset->getIdentifier()`: Identifier of the preset (manually set or calculated based on label and columns)

The event listener can also remove array indexes or columns of existing
array entries by passing a newly constructed :php:`DownloadPreset` object with the
changed `label` and `columns` constructor properties.

Example
=======

The corresponding event listener class:

..  code-block:: php

    <?php

    declare(strict_types=1);

    namespace Vendor\MyPackage\RecordList\EventListener;

    use TYPO3\CMS\Backend\RecordList\Event\BeforeRecordDownloadPresetsAreDisplayedEvent;
    use TYPO3\CMS\Backend\RecordList\DownloadPreset;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    #[AsEventListener(identifier: 'my-package/modify-record-list-preset')]
    final readonly class PresetListener
    {
        public function __invoke(BeforeRecordDownloadPresetsAreDisplayedEvent $event): void
        {
            $presets = $event->getPresets();

            switch ($event->getDatabaseTable()) {
                case 'be_users':
                    $presets[] = new DownloadPreset('PSR-14 preset', ['uid','email']);
                    break;

                case 'pages':
                    $presets[] = new DownloadPreset('PSR-14 preset', ['title']);
                    $presets[] = new DownloadPreset('Another PSR-14 preset', ['title', 'doktype']);
                    break;

                case 'tx_myvendor_myextension':
                    $presets[] = new DownloadPreset('PSR-14 preset', ['uid', 'something']);
                    break;
            }

            $presets[] = new DownloadPreset('Available everywhere, simple UID list', ['uid']);

            $presets['some-identifier'] = new DownloadPreset('Overwrite preset', ['uid, pid'], 'some-identifier');

            $event->setPresets($presets);
        }
    }

Impact
======

Using the PSR-14 event :php:`BeforeRecordDownloadPresetsAreDisplayedEvent`
it is now possible to modify the presets of each table for
downloading / exporting a list of such records via the :guilabel:`Web > List`
module.

.. index:: Backend, PHP-API, ext:core
