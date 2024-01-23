.. include:: /Includes.rst.txt

.. _breaking-102875-1705944493:

========================================
Breaking: #102875 - QueryBuilder changes
========================================

See :issue:`102875`

Description
===========

Doctrine DBAL 4 removed methods from the :php:`QueryBuilder` which has been
adopted to the extended :php:`\TYPO3\CMS\Core\Database\Query\QueryBuilder`.

Removed methods:

*   :php:`QueryBuilder::add()`: Use new reset methods and normal set methods
    instead.
*   :php:`QueryBuilder::getQueryPart($partName)`: No replacement, internal state.
*   :php:`QueryBuilder::getQueryParts()`: No replacement, internal state.
*   :php:`QueryBuilder::resetQueryPart($partName)`: Replacement methods has been added,
    see list.
*   :php:`QueryBuilder::resetQueryParts()`: Replacement methods has been added,
    see list.
*   :php:`QueryBuilder::execute()`: Use :php:`QueryBuilder::executeQuery()` or
    :php:`QueryBuilder::executeStatement()` directly.
*   :php:`QueryBuilder::setMaxResults()`: Using `(int)0` as `max result` will
    no longer work and retrieve no records. Use `NULL` instead to allow all
    results.

Signature changes:

*   :php:`QueryBuilder::quote(string $value)`: Second argument has been dropped
    and the value must now be of type :php:`string`.

Impact
======

Calling any of the mentioned removed methods will result in a PHP error. Also
signature changes introducing type hint will result in a PHP error if called
with an invalid type.

Affected installations
======================

Only those installations that use the mentioned methods.

Migration
=========

Extension author need to replace the removed methods with the alternatives which


:php:`QueryBuilder::add('query-part-name')`
-------------------------------------------

Use the direct set/select methods instead:

..  csv-table:: Replacements
    :header: "before", "after"

    ":php:`->add('select', $array)`", ":php:`->select(...$array)`"
    ":php:`->add('where', $wheres)`", ":php:`->where(...$wheres)`"
    ":php:`->add('having', $havings)`", ":php:`->having(...$havings)`"
    ":php:`->add('orderBy', $orderBy)`", ":php:`->orderBy($orderByField, $orderByDirection)->addOrderBy($orderByField2)`"
    ":php:`->add('groupBy', $groupBy)`", ":php:`->groupBy($groupField)->addGroupBy($groupField2)`"

..  note::
    This can be done already in TYPO3 v12 with at least Doctrine DBAL 3.8.

:php:`QueryBuilder::resetQueryParts()` and :php:`QueryBuilder::resetQueryPart()`
--------------------------------------------------------------------------------

However, several replacements have been put in place depending on the
:php:`$queryPartName` parameter:

..  csv-table:: Replacements
    :header: "before", "after"

    "'select'", "Call :php:`->select()` with a new set of columns"
    "'distinct'", ":php:`->distinct(false)`"
    "'where'", ":php:`->resetWhere()`"
    "'having'", ":php:`->resetHaving()`"
    "'groupBy'", ":php:`->resetGroupBy()`"
    "'orderBy", ":php:`->resetOrderBy()`"
    "'values'", "Call :php:`->values()` with a new set of values."

..  note::
    This can be done already in TYPO3 v12 with at least Doctrine DBAL 3.8.

:php:`QueryBuilder::execute()`
------------------------------

Doctrine DBAL 4 removed :php:`QueryBuilder::execute()` in favour of the two
methods :php:`QueryBuilder::executeQuery()` for select/count and :php:`QueryBuilder::executeStatement()`
for insert, delete and update queries.

Before
~~~~~~

..  code-block:: php
    :emphasize-lines: 9,20

    use TYPO3\CMS\Core\Database\ConnectionPool;

    // select query
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
      ->getQueryBuilderForTable('pages');
    $rows = $queryBuilder
        ->select('*')
        ->from('pages')
        ->execute()
        ->fetchAllAssociative();

    // delete query
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
      ->getQueryBuilderForTable('pages');
    $deletedRows = (int)$queryBuilder
        ->delete('pages')
        ->where(
          $queryBuilder->expr()->eq('pid', $this->createNamedParameter(123),
        )
        ->execute();

After
~~~~~

..  code-block:: php
    :emphasize-lines: 9,20

    use TYPO3\CMS\Core\Database\ConnectionPool;

    // select query
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
      ->getQueryBuilderForTable('pages');
    $rows = $queryBuilder
        ->select('*')
        ->from('pages')
        ->executeQuery()
        ->fetchAllAssociative();

    // delete query
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
      ->getQueryBuilderForTable('pages');
    $deletedRows = (int)$queryBuilder
        ->delete('pages')
        ->where(
          $queryBuilder->expr()->eq('pid', $this->createNamedParameter(123),
        )
        ->executeStatement();

:php:`QueryBuilder::quote(string $value)`
-----------------------------------------

:php:`quote()` uses :php:`Connection::quote()` and therefore adopts the changed
signature and behaviour.

Before
~~~~~~

..  code-block:: php
    :emphasize-lines: 15

    use TYPO3\CMS\Core\Database\Connection as Typo3Connection;
    use TYPO3\CMS\Core\Database\ConnectionPool;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    // select query
    $pageId = 123;
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('pages');
    $rows = $queryBuilder
        ->select('*')
        ->from('pages')
        ->where(
            $queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->quote($pageId, Typo3Connection::PARAM_INT)
            ),
        )
        ->executeQuery()
        ->fetchAllAssociative();

After
~~~~~

..  code-block:: php
    :emphasize-lines: 14

    use TYPO3\CMS\Core\Database\ConnectionPool;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    // select query
    $pageId = 123;
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('pages');
    $rows = $queryBuilder
        ->select('*')
        ->from('pages')
        ->where(
            $queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->quote((string)$pageId)
            ),
        )
        ->executeQuery()
        ->fetchAllAssociative();

..  tip::
    To provide TYPO3 v12 and v13 with one code base, :php:`->quote((string)$value)`
    can be used to ensure dual Core compatibility.

:php:`QueryBuilder::setMaxResults()`
------------------------------------

Using `(int)0` as `max result` will no longer work and retrieve no records.
Use `NULL` instead to allow all results.

Before
~~~~~~

..  code-block:: php
    :emphasize-lines: 12

    use TYPO3\CMS\Core\Database\ConnectionPool;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    // select query
    $pageId = 123;
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('pages');
    $rows = $queryBuilder
        ->select('*')
        ->from('pages')
        ->setFirstResult(0)
        ->setMaxResults(0)
        ->executeQuery()
        ->fetchAllAssociative();

After
~~~~~

..  code-block:: php
    :emphasize-lines: 12

    use TYPO3\CMS\Core\Database\ConnectionPool;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    // select query
    $pageId = 123;
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('pages');
    $rows = $queryBuilder
        ->select('*')
        ->from('pages')
        ->setFirstResult(0)
        ->setMaxResults(null)
        ->executeQuery()
        ->fetchAllAssociative();


.. index:: Database, PHP-API, NotScanned, ext:core
