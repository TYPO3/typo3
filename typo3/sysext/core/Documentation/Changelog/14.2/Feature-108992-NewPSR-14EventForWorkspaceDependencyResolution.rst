.. include:: /Includes.rst.txt

.. _feature-108992-1739706000:

=======================================================================
Feature: #108992 - New PSR-14 event for workspace dependency resolution
=======================================================================

See :issue:`108992`

Description
===========

A new PSR-14 event
:php:`\TYPO3\CMS\Workspaces\Event\IsReferenceConsideredForDependencyEvent`
has been added. It is dispatched per `sys_refindex` row when the workspace
dependency resolver evaluates which references constitute structural
dependencies during publish, stage, discard, or display operations.

Listeners decide whether a given reference should be treated as a workspace
dependency. References are opt-in: the default is "not a dependency", and
listeners must explicitly mark relevant references.

The event provides the following methods:

-  :php:`getTableName()`: The table owning the field (refindex ``tablename``).
-  :php:`getRecordId()`: The record owning the field (refindex ``recuid``).
-  :php:`getFieldName()`: The TCA field name (refindex ``field``).
-  :php:`getReferenceTable()`: The referenced table (refindex ``ref_table``).
-  :php:`getReferenceId()`: The referenced record id (refindex ``ref_uid``).
-  :php:`getAction()`: The
   :php:`\TYPO3\CMS\Workspaces\Dependency\DependencyCollectionAction` enum
   value (``Publish``, ``StageChange``, ``Discard``, or ``Display``).
-  :php:`getWorkspaceId()`: The current workspace id.
-  :php:`isDependency()` / :php:`setDependency()`: Read or change whether this
   reference is a structural dependency.

TYPO3 Core registers a listener that marks ``type=inline``, ``type=file``
(with ``foreign_field``), and ``type=flex`` fields as dependencies.

A new enum :php:`\TYPO3\CMS\Workspaces\Dependency\DependencyCollectionAction`
has been added to represent the action context.

Example
=======

A third-party extension that stores parent-child relationships in a custom
field can register a listener to include those references as workspace
dependencies:

..  code-block:: php

    <?php

    namespace Vendor\MyPackage\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Workspaces\Event\IsReferenceConsideredForDependencyEvent;

    #[AsEventListener('my-package/workspace-dependency')]
    final class WorkspaceDependencyListener
    {
        public function __invoke(IsReferenceConsideredForDependencyEvent $event): void
        {
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
`ElementEntityProcessor`) used previously has been removed. This is an
internal change that does not affect public API.

.. index:: Backend, PHP-API, ext:workspaces
