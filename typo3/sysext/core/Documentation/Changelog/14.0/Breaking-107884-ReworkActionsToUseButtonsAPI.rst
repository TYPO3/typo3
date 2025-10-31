..  include:: /Includes.rst.txt
..  _breaking-107884-1730135000:

=====================================================================
Breaking: #107884 - Rework actions to use Buttons API with Components
=====================================================================

See :issue:`107884`

Description
===========

The record list and file list action system (the "button bar" in every
row of the table-like display) has been completely reworked to use
the Buttons API, utilizing proper component objects instead of plain HTML strings.

This modernization improves type safety, provides better extensibility, and
enables more structured manipulation of action buttons through PSR-14 events.

The following components have been affected by this change:

- :php:`\TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListRecordActionsEvent`
- :php:`\TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent`
- :php:`\TYPO3\CMS\Backend\RecordList\DatabaseRecordList::makeControl()`

Buttons can now be put into `ActionGroups` which are identified by PHP
enum :php:`TYPO3\CMS\Backend\Template\Components\ActionGroup` and
differentiate groups of buttons into a "primary" and "secondary" group.

Also, :php:`TYPO3\CMS\Backend\Template\Components\ComponentGroup` enhances
the ability to group multiple Button API Components into one data object
and manage its state.

Impact
======

Extensions that listen to the :php:`ModifyRecordListRecordActionsEvent` or
:php:`ProcessFileListActionsEvent` to modify record or file actions need to be
updated. The events no longer work with HTML strings but with
:php:`ComponentInterface` objects (see :issue:`107823`).

Extensions that directly call :php:`DatabaseRecordList::makeControl()` need to
update the method signature as the `$table` parameter has been removed.

ModifyRecordListRecordActionsEvent
----------------------------------

The method :php:`setAction()` now requires a :php:`ComponentInterface` object,
the method :php:`getAction()` now returns null or a :php:`ComponentInterface`
object.

Similar behavior can be for ActionGroup and the :php:`$group` parameter which
requires a :php:`ActionGroup` enum now and affects these methods:

- :php:`hasAction()`
- :php:`getAction()`
- :php:`removeAction()`
- :php:`getActionGroup()`

The method :php:`getRecord()` no longer returns a raw data array but an instance
of the Record API.

A new method :php:`getRequest()` allows to access request context for the event.

**Removed methods:**

- :php:`getActions()`
- :php:`setActions()`
- :php:`getTable()`

ProcessFileListActionsEvent
---------------------------

The :php:`ProcessFileListActionsEvent` has received identical changes to its
API as the :php:`ModifyRecordListRecordActionsEvent`, allowing to modify
items in both supported ActionGroups (primary and secondary). Several new API
methods have been created:

- :php:`setAction()`
- :php:`getAction()`
- :php:`removeAction()`
- :php:`moveActionTo()`
- :php:`getActionGroup()`
- :php:`getRequest()`

Buttons can now also be internally relocated, or placed at specific before/after
positions.

**Removed methods:**

- :php:`getActionItems()`
- :php:`setActionItems()`

Affected Installations
======================

TYPO3 installations with custom PHP code that modifies these actions and buttons,
or utilizes the mentioned PSR-14 events.

Migration
=========

DatabaseRecordList::makeControl()
---------------------------------

..  code-block:: php

    // Before
    public function makeControl($table, RecordInterface $record): string

    // After
    public function makeControl(RecordInterface $record): string

The `$table` parameter has been removed as the table name can be obtained from
the :php:`RecordInterface` via :php:`$record->getMainType()`.

Adjust code that calls this (internal) method to drop the `$table` argument:

..  code-block:: diff
    :caption: EXT:my_extension/Classes/ViewHelper/MyControlViewHelper.php

    // ...
    public function render(): string
    {
        $row = BackendUtility::getRecord($table, $someRowUid);
        $databaseRecordList = GeneralUtility::makeInstance(DatabaseRecordList::class);
    -   return $databaseRecordList->makeControl($table, $row);
    +   return $databaseRecordList->makeControl($row);
    }

ProcessFileListActionsEvent
---------------------------

Due to the changes in those events, event listeners now need to
compose extra actions with the Button API and add each button (:php:`$action`)
via the event's :php:`setAction($button)` method.

Internally, buttons are now put into the new :php:`ActionGroup` container
which can be retrieved via :php:`TYPO3\CMS\Backend\Template\Components\ActionGroup::primary` or
:php:`TYPO3\CMS\Backend\Template\Components\ActionGroup::secondary`.

The replacement for the old event method :php:`getActionItems()` thus needs the
context of which action group to retrieve, and can be done now via :php:`getActionGroup()`.

Instead of retrieving all items and modifying them, distinct event methods
:php:`removeAction()`, :php:`moveActionTo()` and :php:`getAction()` are now available,
identifying each action button with a string like the former array key index.

Action buttons can now longer be submitted as raw HTML markup, but instead need to
utilize either the Button API or the new ComponentFactory() (see :issue:`107823`) for a convenience
layer on top of the Button API.

..  code-block:: php

    // Before
    class ProcessFileListActionsEventListener
    {
        public function __invoke(ProcessFileListActionsEvent $event): void
        {
            $items = $event->getActionItems();
            $items['my-own-action'] = '<a href="..." class="btn btn-default">...</a>';
            unset($items['some-other-action']);
            $event->setActionItems($items);
        }
    }

    // After
    class ProcessFileListActionsEventListener
    {
        public function __construct(
            TYPO3\CMS\Backend\Template\Components\ComponentFactory $componentFactory,
            TYPO3\CMS\Core\Imaging\IconFactory $iconFactory,
        ) {
        }

        public function __invoke(ProcessFileListActionsEvent $event): void
        {
            $viewButton = $this->componentFactory->createGenericButton()
                ->setIcon($this->iconFactory->getIcon('actions-view'))
                ->setTitle('My title')
            $event->setAction($viewButton, 'my-own-action', ActionGroup::primary);

            $event->removeAction('some-other-action', ActionGroup::primary);
        }
    }

ModifyRecordListRecordActionsEvent
----------------------------------

As with the event above, event listeners now need to
compose extra actions with the Button API and add each button (:php:`$action`)
via the event's :php:`setAction($button)` method. Buttons can no longer
contain raw HTML markup.

The signature of the existing event method :php:`setAction()` has changed, so
that :php:`$action` needs to be an instance of :php:`ComponentInterface`,
which is retrieved via the :php:`ComponentFactory`, and no longer a string.

Since (as mentioned above) the action groups are managed via the :php:`ActionGroup`
container, the event methods :php:`hasAction()`, :php:`getAction()`, :php:`removeAction()`,
:php:`getActionGroup()` now need to specify a :php:`$group` identifier like :php:`ActionGroup::primary`
or :php:`ActionGroup::secondary` instead of a string.

The ability to inject multiple items at once with :php:`setActions()` must be replaced
with distinct calls to :php:`setAction()`.

Retrieving all action items can no longer be done with :php:`getActions()` but must specifically
access either the primary or secondary action group with :php:`getActionGroup()`.

The :php:`getRecord()` method no longer returns an array with record data, but an object of the Record API.

The :php:`getTable()` method can be replaced by retrieving the table name via :php:`getRecord()->getMainType()`
thanks to easily accessing the Record API object.

Modifying actions
^^^^^^^^^^^^^^^^^

..  code-block:: php

    // Before
    class ModifyRecordListRecordActionsEventListener
    {
        public function __invoke(ModifyRecordListRecordActionsEvent $event): void
        {
            $items = $event->getActions();
            unset($items['my-own-action']);
            $items['my-own-action'] = '<a href="..." class="btn btn-default">...</a>';
            unset($existing['some-other-action']);
            $event->setActions($items);

            $event->setAction('<button ...></button>', 'my-other-own-action', 'secondary');
        }
    }

    // After
    class ModifyRecordListRecordActionsEventListener
    {
        public function __construct(
            TYPO3\CMS\Backend\Template\Components\ComponentFactory $componentFactory,
            TYPO3\CMS\Core\Imaging\IconFactory $iconFactory,
        ) {
        }

        public function __invoke(ModifyRecordListRecordActionsEventListener $event): void
        {
            $viewButton = $this->componentFactory->createGenericButton()
                ->setIcon($this->iconFactory->getIcon('actions-view'))
                ->setTitle('My title')
            $event->setAction($viewButton, 'my-own-action', ActionGroup::primary);
            $event->removeAction('some-other-action', ActionGroup::primary);

            $inputButton = $this->componentFactory->createInputButton()
                ->setTitle('My Button')
            $event->setAction($inputButton, 'my-other-own-action', ActionGroup::secondary);
        }
    }

Accessing groups
^^^^^^^^^^^^^^^^

..  code-block:: php

    // Before
    $event->getAction('my-button', 'primary');
    $event->hasAction('my-button', 'primary');
    $event->removeAction('my-button', 'primary');
    $event->getActionGroup('primary');

    // After
    $event->getAction('my-button', ActionGroup::primary);
    $event->hasAction('my-button', ActionGroup::primary);
    $event->removeAction('my-button', ActionGroup::primary);
    $event->getActionGroup(ActionGroup::primary);

Accessing record
^^^^^^^^^^^^^^^^

..  code-block:: php

    // Before
    $uid = $event->getRecord()['uid'];
    $title = $event->getRecord()['title'];

    // After
    $uid = $event->getRecord()->getUid();
    $title = $event->getRecord()->getRawRecord()['title'];

Dual-version compatibility
--------------------------

The create extensions or custom code that works in both TYPO3 v13
and v14, a version switch can be added within event listeners:

..  code-block:: php

    class ModifyRecordListRecordActionsEventListener
    {
        public function __invoke(ModifyRecordListRecordActionsEvent $event): void
        {
            if (new TYPO3\CMS\Core\Information\Typo3Version()->getMajorVersion() >= 14) {
                $viewButton = $this->componentFactory->createGenericButton()
                    ->setIcon($this->iconFactory->getIcon('actions-view'))
                    ->setTitle('My title')
                $event->setAction($viewButton, 'my-own-action', ActionGroup::primary);
                $event->removeAction('some-other-action', ActionGroup::primary);
            } else {
                $items = $event->getActions();
                unset($items['my-own-action']);
                $items['my-own-action'] = '<a href="..." class="btn btn-default">...</a>';
                unset($existing['some-other-action']);
                $event->setActions($items);
            }
        }
    }


.. index:: Backend, PHP-API, PartiallyScanned, ext:backend, ext:filelist
