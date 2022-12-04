.. include:: /Includes.rst.txt

.. _deprecation-98996-1667549770:

=================================================================================================
Deprecation: #98996 - Doctrine DBAL: BackendWorkspaceRestriction and FrontendWorkspaceRestriction
=================================================================================================

See :issue:`98996`

Description
===========

TYPO3's Database Abstraction Layer works with restrictions to limit the selection
based on TYPO3's TCA information for certain database tables.

With the introduction of Doctrine DBAL and the Database Restrictions in TYPO3 v8,
the two restrictions
:php:`\TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction`
and :php:`\TYPO3\CMS\Core\Database\Query\Restriction\FrontendWorkspaceRestriction`
were introduced, which had some conceptual flaws. The usages to these restrictions
were removed subsequently within TYPO3 Core since TYPO3 v9, as various improvements
were made to the database layer when working with Workspaces.

In TYPO3 v9.5.x a new restriction :php:`\TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction`
:ref:`was added <important-84985>`, which superseded both existing
Workspace-related restrictions, solving almost all cases needed when reading
rows from the database.

The former restriction classes have now been marked as deprecated.


Impact
======

Instantiating any of the classes

* :php:`\TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction`
* :php:`\TYPO3\CMS\Core\Database\Query\Restriction\FrontendWorkspaceRestriction`

will trigger a PHP deprecation notice.


Affected installations
======================

TYPO3 installations with custom extensions explicitly using one of the
restrictions. Affected extensions can be detected via the Extension Scanner
in the Install Tool / Maintenance Area.


Migration
=========

Use the class :php:`\TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction`
instead. It allows to hand in the current workspace ID, which then fetches all
records from the database only for a certain workspace (unlike
:php:`FrontendWorkspaceRestriction` which did not limit the database query to
one workspace in certain cases).

When querying the database, ensure to use the Overlay APIs in
:php:`\TYPO3\CMS\Core\Domain\Repository\PageRepository->versionOL` (Frontend)
or :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL()` would then
filter the invalid records.

Example
-------

This shows a regular example within the TYPO3 backend to query records
within

..  code-block:: php

    $context = GeneralUtility::makeInstance(Context::class);
    $workspaceId = $context->getPropertyFromAspect('workspace', 'id', 0);

    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('tt_content');

    $queryBuilder->getRestrictions()
        ->removeAll()
        ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
        ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $workspaceId));

    $statement = $queryBuilder
        ->select('*')
        ->from('tt_content')
        ->where(
            $queryBuilder->expr()->eq('colPos', $queryBuilder->createNamedParameter(0))
        )
        ->execute();

    $records = [];
    while ($record = $statement->fetchAssociative()) {
        BackendUtility::workspaceOL('tt_content', $record, $workspaceId);
        if (is_array($record)) {
            $records[] = $record;
        }
    }
    return $records;

.. index:: Database, FullyScanned, ext:core
