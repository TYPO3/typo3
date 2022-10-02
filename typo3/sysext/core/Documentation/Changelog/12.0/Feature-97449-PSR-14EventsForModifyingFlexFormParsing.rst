.. include:: /Includes.rst.txt

.. _feature-97449:

==============================================================
Feature: #97449 - PSR-14 events for modifying FlexForm parsing
==============================================================

See :issue:`97449`

Description
===========

Four new PSR-14 events have been introduced which serve as a more powerful
and flexible alternative for the now removed :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FlexFormTools::class]['flexParsing']`
hooks. Corresponding docblocks describe how and when the new PSR-14 events

- :php:`TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureParsedEvent`
- :php:`TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent`
- :php:`TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureIdentifierInitializedEvent`
- :php:`TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureIdentifierInitializedEvent`

should be used.

Example
=======

Registration of the events in your extension's :file:`Services.yaml`:

..  code-block:: yaml

    MyVendor\MyPackage\Backend\FlexFormParsingModifyEventListener:
      tags:
          - name: event.listener
            identifier: 'form-framework/set-data-structure'
            method: 'setDataStructure'
          - name: event.listener
            identifier: 'form-framework/modify-data-structure'
            method: 'modifyDataStructure'
          - name: event.listener
            identifier: 'form-framework/set-data-structure-identifier'
            method: 'setDataStructureIdentifier'
          - name: event.listener
            identifier: 'form-framework/modify-data-structure-identifier'
            method: 'modifyDataStructureIdentifier'

The corresponding event listener class:

..  code-block:: php

    use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureIdentifierInitializedEvent;
    use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;
    use TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureIdentifierInitializedEvent;
    use TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureParsedEvent;

    final class FlexFormParsingModifyEventListener
    {
        public function setDataStructure(BeforeFlexFormDataStructureParsedEvent $event): void
        {
            $identifier = $event->getIdentifier();
            if (($identifier['type'] ?? '') === 'my_custom_type') {
                $event->setDataStructure('FILE:EXT:myext/Configuration/FlexForms/MyFlexform.xml');
            }
        }

        public function modifyDataStructure(AfterFlexFormDataStructureParsedEvent $event): void
        {
            $identifier = $event->getIdentifier();
            if (($identifier['type'] ?? '') === 'my_custom_type') {
                $parsedDataStructure = $event->getDataStructure();
                $parsedDataStructure['sheets']['sDEF']['ROOT']['TCEforms']['sheetTitle'] = 'Some dynamic custom sheet title';
                $event->setDataStructure($parsedDataStructure);
            }
        }

        public function setDataStructureIdentifier(BeforeFlexFormDataStructureIdentifierInitializedEvent $event): void
        {
            if ($event->getTableName() === 'tx_myext_sometable') {
                $event->setIdentifier([
                    'type' => 'my_custom_type',
                ]);
            }
        }

        public function modifyDataStructureIdentifier(AfterFlexFormDataStructureIdentifierInitializedEvent $event): void
        {
            $identifier = $event->getIdentifier();
            if (($identifier['type'] ?? '') !== 'my_custom_type') {
                $identifier['type'] = 'my_custom_type';
            }
            $event->setIdentifier($identifier);
        }
    }

Available Methods
=================

The list below describes all available methods for :php:`BeforeFlexFormDataStructureParsedEvent`:

+-------------------------+-----------------------+----------------------------------------------------+
| Method                  | Parameters            | Description                                        |
+=========================+=======================+====================================================+
| getIdentifier()         |                       | Returns the resolved data structure identifier.    |
+-------------------------+-----------------------+----------------------------------------------------+
| setDataStructure()      | :php:`$dataStructure` | Allows to either set an already parsed data        |
|                         |                       | structure as :php:`array`, a file reference or the |
|                         |                       | XML structure as :php:`string`. Setting a data     |
|                         |                       | structure will immediately stop propagation.       |
+-------------------------+-----------------------+----------------------------------------------------+
| getDataStructure()      |                       | Returns the current data structure, which will     |
|                         |                       | always be :php:`null` for listeners, since the     |
|                         |                       | event propagation is stopped as soon as a listener |
|                         |                       | sets a data structure.                             |
+-------------------------+-----------------------+----------------------------------------------------+
| isPropagationStopped()  |                       | Returns whether propagation has been stopped.      |
+-------------------------+-----------------------+----------------------------------------------------+

.. note::

    Using the now-removed hook method :php:`parseDataStructureByIdentifierPreProcess()` previously required
    implementations to always return an :php:`array` or :php:`string`. This means, implementations returned
    an empty :php:`array` or empty :php:`string` in case they did not want to set a data structure, allowing
    further implementations to be called. This has now changed. As soon as a listener sets a data structure
    using the :php:`setDataStructure()` method, the event propagation is stopped immediately and no further
    listeners are being called. Therefore, listeners should avoid setting an empty :php:`array` or an empty
    :php:`string`, but should just "return" without any change to the :php:`$event` object in such a case.

The list below describes all available methods for :php:`AfterFlexFormDataStructureParsedEvent`:

+-------------------------+-----------------------+----------------------------------------------------+
| Method                  | Parameters            | Description                                        |
+=========================+=======================+====================================================+
| getIdentifier()         |                       | Returns the resolved data structure identifier.    |
+-------------------------+-----------------------+----------------------------------------------------+
| setDataStructure()      | :php:`$dataStructure` | Allows to modify or completely replace the parsed  |
|                         |                       | data structure.                                    |
+-------------------------+-----------------------+----------------------------------------------------+
| getDataStructure()      |                       | Returns the current data structure, which has been |
|                         |                       | processed and parsed by the :php:`FlexFormTools`   |
|                         |                       | component. Might contain additional data from      |
|                         |                       | previously called listeners.                       |
+-------------------------+-----------------------+----------------------------------------------------+

The list below describes all available methods for :php:`BeforeFlexFormDataStructureIdentifierInitializedEvent`:

+-------------------------+-----------------------+----------------------------------------------------+
| Method                  | Parameters            | Description                                        |
+=========================+=======================+====================================================+
| getFieldTca()           |                       | Returns the full TCA of the currently handled      |
|                         |                       | field, having `type=flex` set.                     |
+-------------------------+-----------------------+----------------------------------------------------+
| getTableName()          |                       | Returns the table name of the TCA field.           |
+-------------------------+-----------------------+----------------------------------------------------+
| getFieldName()          |                       | Returns the TCA field name.                        |
+-------------------------+-----------------------+----------------------------------------------------+
| getRow()                |                       | Returns the whole database row of the record.      |
+-------------------------+-----------------------+----------------------------------------------------+
| setIdentifier()         | :php:`$identifier`    | Allows to define the data structure identifier for |
|                         |                       | the TCA field. Setting an identifier will          |
|                         |                       | immediately stop propagation.                      |
+-------------------------+-----------------------+----------------------------------------------------+
| getIdentifier()         |                       | Returns the current data structure identifier,     |
|                         |                       | which will always be :php:`null` for listeners,    |
|                         |                       | since the event propagation is stopped as soon     |
|                         |                       | as a listener defines an identifier.               |
+-------------------------+-----------------------+----------------------------------------------------+
| isPropagationStopped()  |                       | Returns whether propagation has been stopped.      |
+-------------------------+-----------------------+----------------------------------------------------+

.. note::

    Using the now removed hook method :php:`getDataStructureIdentifierPreProcess()` previously required
    implementations to always return an :php:`array`. This means, implementations returned an empty
    :php:`array` in case they did not want to set an identifier, allowing further implementations to be
    called. This has now changed. As soon as a listener sets the identifier using the :php:`setIdentifier()`
    method, the event propagation is stopped immediately and no further listeners are being called.
    Therefore, listeners should avoid setting an empty :php:`array`, but should just "return" without
    any change to the :php:`$event` object in such a case.

The list below describes all available methods for :php:`AfterFlexFormDataStructureIdentifierInitializedEvent`:

+-------------------------+-----------------------+----------------------------------------------------+
| Method                  | Parameters            | Description                                        |
+=========================+=======================+====================================================+
| getFieldTca()           |                       | Returns the full TCA of the currently handled      |
|                         |                       | field, having `type=flex` set.                     |
+-------------------------+-----------------------+----------------------------------------------------+
| getTableName()          |                       | Returns the table name of the TCA field.           |
+-------------------------+-----------------------+----------------------------------------------------+
| getFieldName()          |                       | Returns the TCA field name.                        |
+-------------------------+-----------------------+----------------------------------------------------+
| getRow()                |                       | Returns the whole database row of the record.      |
+-------------------------+-----------------------+----------------------------------------------------+
| setIdentifier()         | :php:`$identifier`    | Allows to modify or completely replace the         |
|                         |                       | initialized data structure identifier.             |
+-------------------------+-----------------------+----------------------------------------------------+
| getIdentifier()         |                       | Returns the initialized data structure identifier, |
|                         |                       | which has either been defined by an event listener |
|                         |                       | or set to the default by the :php:`FlexFormTools`  |
|                         |                       | component.                                         |
+-------------------------+-----------------------+----------------------------------------------------+

Impact
======

It's now possible to fully control the FlexForm parsing using an
object oriented approach with four new PSR-14 events.

.. index:: Backend, PHP-API, ext:backend
