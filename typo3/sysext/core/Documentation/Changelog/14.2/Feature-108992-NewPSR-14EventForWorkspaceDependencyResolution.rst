..  include:: /Includes.rst.txt

..  _feature-108992-1739706000:

=======================================================================
Feature: #108992 - New PSR-14 event for workspace dependency resolution
=======================================================================

See :issue:`108992`

Description
===========

A new PSR-14 event
:php:`\TYPO3\CMS\Workspaces\Event\IsReferenceConsideredForDependencyEvent`
has been added. It is dispatched for each :sql:`sys_refindex` row when the
workspace dependency resolver evaluates which references constitute structural
dependencies during publish, stage, discard, and display operations.

Listeners decide whether a particular reference should be treated as a workspace
dependency. References are opt-in: the default is "not a dependency", and
listeners must explicitly mark relevant references.

The event has the following methods:

*   :php:`getTableName()`: The table owning the field
    (:sql:`sys_refindex.tablename`).
*   :php:`getRecordId()`: The record owning the field
    (:sql:`sys_refindex.recuid`).
*   :php:`getFieldName()`: The TCA field name (:sql:`sys_refindex.field`).
*   :php:`getReferenceTable()`: The referenced table
    (:sql:`sys_refindex.ref_table`).
*   :php:`getReferenceId()`: The referenced record ID
    (:sql:`sys_refindex.ref_uid`).
*   :php:`getAction()`: The
    :php:`\TYPO3\CMS\Workspaces\Dependency\DependencyCollectionAction` enum
    value (:php:`Publish`, :php:`StageChange`, :php:`Discard`, or
    :php:`Display`).
*   :php:`getWorkspaceId()`: The current workspace ID.
*   :php:`isDependency()` / :php:`setDependency()`: Read or change whether this
    reference is a structural dependency.

TYPO3 Core registers a listener that marks :php:`type=inline`,
:php:`type=file` (with :php:`foreign_field`), and :php:`type=flex` fields as
dependencies.

A new enum
:php:`\TYPO3\CMS\Workspaces\Dependency\DependencyCollectionAction`
has been added to represent the action context.

Example
=======

A third-party extension that stores parent-child relationships in a custom
field can register a listener to include those references as workspace
dependencies:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/WorkspaceDependencyListener.php

    namespace Vendor\MyPackage\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Workspaces\Event\
        IsReferenceConsideredForDependencyEvent;

    #[AsEventListener('my-package/workspace-dependency')]
    final class WorkspaceDependencyListener
    {
        public function __invoke(
            IsReferenceConsideredForDependencyEvent $event
        ): void {
            if ($event->getFieldName() === 'tx_mypackage_parent') {
                $event->setDependency(true);
            }
        }
    }

Impact
======

Extensions can now register custom parent-child relationships as workspace
dependencies via this PSR-14 event. This ensures that structurally dependent
records are published, staged, or discarded together, preventing orphaned
records in workspaces.

The internal pseudo-event mechanism (`EventCallback`,
`ElementEntityProcessor`) that was previously used has been removed. This is an
internal change that does not affect the public API.

..  index:: Backend, PHP-API, ext:workspaces
