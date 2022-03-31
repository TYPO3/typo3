.. include:: /Includes.rst.txt

======================================================
Feature: #95105 - New PSR-14 DatabaseRecordList events
======================================================

See :issue:`95105`

Description
===========

A couple of new PSR-14 events for the :php:`\TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList` class
have been added to TYPO3 Core. They are mainly a direct replacement for
the hook methods, defined in the
:php:`\TYPO3\CMS\Recordlist\RecordList\RecordListHookInterface`, while
their functionality is improved and extended.

The new events can be used to modify the behaviour of each table listing,
which means they can be used to either add, change or even remove columns
and actions.

Following events have been added:

- :php:`\TYPO3\CMS\Recordlist\Event\ModifyRecordListTableActionsEvent`
- :php:`\TYPO3\CMS\Recordlist\Event\ModifyRecordListHeaderColumnsEvent`
- :php:`\TYPO3\CMS\Recordlist\Event\ModifyRecordListRecordActionsEvent`

They all behave in the same way. There is always the subject, e.g. the
record actions or the header columns, together with information like the
current table, the current :php:`DatabaseRecordList` instance and the
current record or the record uids. The subject is therefore equipped
with the usual CRUD methods like :php:`set`, :php:`get` or :php:`remove`. This makes
working with those values much more pleasant. See the below code examples
on how those can be used. Some events also feature additional methods
to influence e.g. the table header attributes or the label, which is
being displayed in case no actions are available for the current user.

An example registration of the events in your extensions' :file:`Services.yaml`:

.. code-block:: yaml

  MyVendor\MyPackage\RecordList\MyEventListener:
    tags:
      - name: event.listener
        identifier: 'my-package/recordlist/my-event-listener'
        method: 'modifyRecordActions'
      - name: event.listener
        identifier: 'my-package/recordlist/my-event-listener'
        method: 'modifyHeaderColumns'
      - name: event.listener
        identifier: 'my-package/recordlist/my-event-listener'
        method: 'modifyTableActions'

The corresponding event listener class:

.. code-block:: php

    use Psr\Log\LoggerInterface;
    use TYPO3\CMS\Recordlist\Event\ModifyRecordListHeaderColumnsEvent;
    use TYPO3\CMS\Recordlist\Event\ModifyRecordListRecordActionsEvent;
    use TYPO3\CMS\Recordlist\Event\ModifyRecordListTableActionsEvent;

    class MyEventListener {

        protected LoggerInterface $logger;

        public function __construct(LoggerInterface $logger)
        {
            $this->logger = $logger;
        }

        public function modifyRecordActions(ModifyRecordListRecordActionsEvent $event): void
        {
            $currentTable = $event->getTable();

            // Add a custom action for a custom table in the secondary action bar, before the "move" action
            if ($currentTable === 'my_custom_table' && !$event->hasAction('myAction')) {
                $event->setAction(
                    '<button>My Action</button>',
                    'myAction',
                    'secondary',
                    'move'
                );
            }

            // Remove the "viewBig" action in case more than 4 actions exist in the group
            if (count($event->getActionGroup('secondary')) > 4 && $event->hasAction('viewBig')) {
                $event->removeAction('viewBig');
            }

            // Move the "delete" action after the "edit" action
            $event->setAction('', 'delete', 'primary', '', 'edit');
        }

        public function modifyHeaderColumns(ModifyRecordListHeaderColumnsEvent $event): void
        {
            // Change label of "control" column
            $event->setColumn('Custom Controls', '_CONTROL_');

            // Add a custom class for the table header row
            $event->setHeaderAttributes(['class' => 'my-custom-class']);
        }

        public function modifyTableActions(ModifyRecordListTableActionsEvent $event): void
        {
            // Remove "edit" action and log, if this failed
            $actionRemoved = $event->removeAction('unknown');
            if (!$actionRemoved) {
                $this->logger->warning('Action "unknown" could not be removed');
            }

            // Add a custom clipboard action after "copyMarked"
            $event->setAction('<button>My action</button>', 'myAction', '', 'copyMarked');

            // Set a custom label for the case, no actions are available for the user
            $event->setNoActionLabel('No actions available due to missing permissions.');
        }

    }


Please have a look at the concrete implementation for a list of all
available methods and their functionalities.

Impact
======

The new PSR-14 events can be used to modify various parts within the
RecordList module in an object-oriented way.

.. index:: PHP-API, ext:core
