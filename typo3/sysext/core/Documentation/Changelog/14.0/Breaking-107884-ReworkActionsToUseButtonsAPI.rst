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

-   :php:`\TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListRecordActionsEvent`
-   :php:`\TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent`
-   :php:`\TYPO3\CMS\Backend\RecordList\DatabaseRecordList::makeControl()`

Buttons can now be placed into *ActionGroups*, which are identified by the
PHP enum :php:`\TYPO3\CMS\Backend\Template\Components\ActionGroup` and
distinguish between a "primary" and a "secondary" group.

In addition, :php:`\TYPO3\CMS\Backend\Template\Components\ComponentGroup`
enhances the ability to group multiple Button API Components into a single data
object and manage their state.

Impact
======

Extensions that listen to the
:php-short:`\TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListRecordActionsEvent`
or :php-short:`\TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent` to modify
record or file actions need to be updated.

The events no longer work with HTML strings but with
:php-short:`\TYPO3\CMS\Backend\Template\Components\ComponentInterface` objects
(see :issue:`107823`).

Extensions that directly call :php:`DatabaseRecordList::makeControl()` must
update their code, as the :php:`$table` parameter has been removed.

ModifyRecordListRecordActionsEvent
----------------------------------

The method :php:`setAction()` now requires a
:php-short:`\TYPO3\CMS\Backend\Template\Components\ComponentInterface` object, and
:php:`getAction()` now returns either `null` or a
:php-short:`\TYPO3\CMS\Backend\Template\Components\ComponentInterface` instance.

The following methods now expect an
:php-short:`\TYPO3\CMS\Backend\Template\Components\ActionGroup` enum value as the
:php:`$group` parameter:

-   :php:`hasAction()`
-   :php:`getAction()`
-   :php:`removeAction()`
-   :php:`getActionGroup()`

The method :php:`getRecord()` no longer returns a raw array but an instance of
the Record API.

A new method :php:`getRequest()` has been added to access the current PSR-7
request context.

**Removed methods:**

-   :php:`getActions()`
-   :php:`setActions()`
-   :php:`getTable()`

ProcessFileListActionsEvent
---------------------------

The :php:`\TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent` has received
identical API changes to
:php-short:`\TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListRecordActionsEvent`,
allowing manipulation of items in both supported
:php-short:`\TYPO3\CMS\Backend\Template\Components\ActionGroup` contexts
(*primary* and *secondary*).

**New methods:**

-   :php:`setAction()`
-   :php:`getAction()`
-   :php:`removeAction()`
-   :php:`moveActionTo()`
-   :php:`getActionGroup()`
-   :php:`getRequest()`

Buttons can now also be repositioned or inserted at specific before/after
locations within an action group.

**Removed methods:**

-   :php:`getActionItems()`
-   :php:`setActionItems()`

Affected installations
======================

TYPO3 installations with custom PHP code that modifies record or file list
actions, or utilizes the mentioned PSR-14 events, are affected.

Migration
=========

DatabaseRecordList::makeControl()
---------------------------------

..  code-block:: php

    // Before
    public function makeControl($table, RecordInterface $record): string

    // After
    public function makeControl(RecordInterface $record): string

The :php:`$table` parameter has been removed, as the table name can now be
retrieved from the :php:`RecordInterface` via
:php:`$record->getMainType()`.

Adjust calls accordingly:

..  code-block:: diff
    :caption: EXT:my_extension/Classes/ViewHelper/MyControlViewHelper.php

     // ...
     public function render(): string
     {
         $row = BackendUtility::getRecord($table, $someRowUid);
         $databaseRecordList = GeneralUtility::makeInstance(DatabaseRecordList::class);
    -    return $databaseRecordList->makeControl($table, $row);
    +    return $databaseRecordList->makeControl($row);
     }

ProcessFileListActionsEvent
---------------------------

Event listeners must now compose buttons via the Button API and add each
component using the event’s :php:`setAction()` method.

Internally, buttons are placed into an
:php-short:`\TYPO3\CMS\Backend\Template\Components\ActionGroup` container,
retrieved via :php:`ActionGroup::primary` or :php:`ActionGroup::secondary`.

The previous :php:`getActionItems()` logic is replaced with
:php:`getActionGroup()` to fetch the corresponding button group.

Instead of manipulating raw HTML, you must now create components using the
:php-short:`\TYPO3\CMS\Backend\Template\Components\ComponentFactory`.

..  code-block:: php

    // Before
    use TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent;

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

..  code-block:: php

    // After
    use TYPO3\CMS\Backend\Template\Components\ActionGroup;
    use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
    use TYPO3\CMS\Core\Imaging\IconFactory;
    use TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent;

    class ProcessFileListActionsEventListener
    {
        public function __construct(
            private readonly ComponentFactory $componentFactory,
            private readonly IconFactory $iconFactory,
        ) {}

        public function __invoke(ProcessFileListActionsEvent $event): void
        {
            $viewButton = $this->componentFactory->createGenericButton()
                ->setIcon($this->iconFactory->getIcon('actions-view'))
                ->setTitle('My title');

            $event->setAction($viewButton, 'my-own-action', ActionGroup::primary);
            $event->removeAction('some-other-action', ActionGroup::primary);
        }
    }

ModifyRecordListRecordActionsEvent
----------------------------------

This event now behaves identically to the file list event:
actions must be created via the Button API and added as
:php:`ComponentInterface` instances using :php:`setAction()`.

The :php:`setActions()` and :php:`getActions()` methods are removed and must be
replaced by distinct calls to :php:`setAction()` or use
:php:`getActionGroup()` to access existing actions.

The :php:`getRecord()` method now returns a Record API object instead of an
array.
:php:`getTable()` can be replaced with
:php:`getRecord()->getMainType()`.

**Modifying actions example:**

..  code-block:: php

    // Before
    use TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListRecordActionsEvent;

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

..  code-block:: php

    // After
    use TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListRecordActionsEvent;
    use TYPO3\CMS\Backend\Template\Components\ActionGroup;
    use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
    use TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListRecordActionsEvent;
    use TYPO3\CMS\Core\Imaging\IconFactory;

    class ModifyRecordListRecordActionsEventListener
    {
        public function __construct(
            private readonly ComponentFactory $componentFactory,
            private readonly IconFactory $iconFactory,
        ) {}

        public function __invoke(ModifyRecordListRecordActionsEvent $event): void
        {
            $viewButton = $this->componentFactory->createGenericButton()
                ->setIcon($this->iconFactory->getIcon('actions-view'))
                ->setTitle('My title');

            $event->setAction($viewButton, 'my-own-action', ActionGroup::primary);
            $event->removeAction('some-other-action', ActionGroup::primary);

            $inputButton = $this->componentFactory->createInputButton()
                ->setTitle('My Button');

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

..  code-block:: php

    // After
    use TYPO3\CMS\Backend\Template\Components\ActionGroup;

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

..  code-block:: php

    // After
    $uid = $event->getRecord()->getUid();
    $title = $event->getRecord()->getRawRecord()['title'];

Dual-version compatibility
--------------------------

To support both TYPO3 v13 and v14, extensions can use a version check within
event listeners:

..  code-block:: php

    use TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListRecordActionsEvent;
    use TYPO3\CMS\Backend\Template\Components\ActionGroup;
    use TYPO3\CMS\Core\Information\Typo3Version;

    class ModifyRecordListRecordActionsEventListener
    {
        public function __invoke(ModifyRecordListRecordActionsEvent $event): void
        {
            if ((new Typo3Version())->getMajorVersion() >= 14) {
                $viewButton = $this->componentFactory->createGenericButton()
                    ->setIcon($this->iconFactory->getIcon('actions-view'))
                    ->setTitle('My title');
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

..  index:: Backend, PHP-API, PartiallyScanned, ext:backend, ext:filelist
