.. include:: /Includes.rst.txt

.. _feature-104631-1723714985:

=================================================================
Feature: #104631 - Add `UNION Clause` support to the QueryBuilder
=================================================================

See :issue:`104631`

Description
===========

The :sql:`UNION` clause is used to combine the result sets of two or more
:sql:`SELECT` statements, which all database vendors support, each with their
own specific variations.

However, there is a commonly shared subset that works across all of them:

..  code-block:: sql

    SELECT column_name(s) FROM table1
    WHERE ...

    UNION <ALL | DISTINCT>

    SELECT column_name(s) FROM table2
    WHERE ...

    ORDER BY ...
    LIMIT x OFFSET y

with shared requirements:

* Each SELECT must return the same fields in number, naming and order.
* Each SELECT must not have ORDER BY, expect MySQL allowing it to be used as sub
  query expression encapsulated in parentheses.

Generic :sql:`UNION` clause support has been contributed to `Doctrine DBAL` and
is included since `Release 4.1.0 <https://github.com/doctrine/dbal/releases/tag/4.1.0>`__
which introduces two new API method on the
:php-short:`\Doctrine\DBAL\Query\QueryBuilder`:

*   :php:`union(string|QueryBuilder $part)` to create first UNION query part
*   :php:`addUnion(string|QueryBuilder $part, UnionType $type = UnionType::DISTINCT)`
    to add additional :sql:`UNION (ALL|DISTINCT)` query parts with the selected union
    query type.

TYPO3 decorates the Doctrine DBAL :php-short:`\Doctrine\DBAL\Query\QueryBuilder`
to provide for most API methods automatic
quoting of identifiers and values **and**  to apply database restrictions automatically
for :sql:`SELECT` queries.

The Doctrine DBAL API has been adopted now to provide the same surface for the
TYPO3 :php:`\TYPO3\CMS\Core\Database\Query\QueryBuilder` and the intermediate
:php:`\TYPO3\CMS\Core\Database\Query\ConcreteQueryBuilder` to make it easier to
create :sql:`UNION` clause queries. The API on both methods allows to provide
dedicated :php-short:`\TYPO3\CMS\Core\Database\Query\QueryBuilder` instances
or direct queries as strings in case it is needed.

..  note::

    Providing :sql:`UNION` parts as plain string requires the developer to take
    care of proper quoting and escaping within the query part.

In queries containing subqueries, only named placeholders (such as `:username`)
can be used and must be registered on the outermost
:php-short:`\TYPO3\CMS\Core\Database\Query\QueryBuilder` object,
similar to advanced query creation with :sql:`SUB QUERIES`.


..  warning::

    :php-short:`\TYPO3\CMS\Core\Database\Query\QueryBuilder` can be used create
    :sql:`UNION` clause queries not compatible with all database providers,
    for example using :sql:`LIMIT/OFFSET` in each part query or other stuff.

UnionType::DISTINCT and UnionType::ALL
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Each subsequent part needs to be defined either as :sql:`UNION DISTINCT` or
:sql:`UNION ALL` which could have not so obvious effects.

For example, using :sql:`UNION ALL` for all parts in between except for the last
one would generate larger result sets first, but discards duplicates when adding
the last result set. On the other side, using :sql:`UNION ALL` tells the query
optimizer **not** to scan for duplicates and remove them at all which can be a
performance improvement - if you can deal with duplicates it can be ensured that
each part does not produce same outputs.

Example: Compose a :sql:`UNION` clause query
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

..  code-block:: php
    :caption: Custom service class using a UNION query to retrieve data.

    use Doctrine\DBAL\Query\UnionType;
    use TYPO3\CMS\Core\Database\Connection;
    use TYPO3\CMS\Core\Database\ConnectionPool;

    final readonly class MyService {
      public function __construct(
        private ConnectionPool $connectionPool,
      ) {}

      public function executeUnionQuery(
        int $pageIdOne,
        int $pageIdTwo,
      ): ?array {
        $connection = $this->connectionPool->getConnectionForTable('pages');
        $unionQueryBuilder = $connection->createQueryBuilder();
        $firstPartQueryBuilder = $connection->createQueryBuilder();
        $secondPartQueryBuilder = $connection->createQueryBuilder();
        // removing automatic TYPO3 restriction for the sake of the example
        // to match the PLAIN SQL example when executed. Not removing them
        // will generate corresponding restriction SQL code for each part.
        $firstPartQueryBuilder->getRestrictions()->removeAll();
        $secondPartQueryBuilder->getRestrictions()->removeAll();
        $expr = $unionQueryBuilder->expr();

        $firstPartQueryBuilder
          // The query parts **must** have the same column counts, and these
          // columns **must** have compatible types
          ->select('uid', 'pid', 'title')
          ->from('pages')
          ->where(
            $expr->eq(
              'pages.uid',
              // !!! Ensure to use most outer / top / main QueryBuilder
              //   instance for creating parameters and the complete
              //   query can be executed in the end.
              $unionQueryBuilder->createNamedParameter($pageIdOne, Connection::PARAM_INT),
            )
          );
        $secondPartQueryBuilder
          ->select('uid', 'pid', 'title')
          ->from('pages')
          ->where(
            $expr->eq(
              'pages.uid',
              // !!! Ensure to use most outer / top / main QueryBuilder instance
              $unionQueryBuilder->createNamedParameter($pageIdTwo, Connection::PARAM_INT),
            )
          );

        // Set first and second union part to the main (union)
        // QueryBuilder and return the retrieved rows.
        return $unionQueryBuilder
          ->union($firstPartQueryBuilder)
          ->addUnion($secondPartQueryBuilder, UnionType::DISTINCT)
          ->orderBy('uid', 'ASC')
          ->executeQuery()
          ->fetchAllAssociative();
      }
    }

This would create the following query for MySQL with :php:`$pageIdOne = 100` and
:php:`$pageIdTwo = 10`:

..  code-block:: sql

        (SELECT `uid`, `pid`, `title` FROM pages WHERE `pages`.`uid` = 100)
    UNION
        (SELECT `uid`, `pid`, `title` FROM pages WHERE `pages`.`uid` = 10)
    ORDER BY `uid` ASC


Impact
======

Extension authors can use the new
:php-short:`\TYPO3\CMS\Core\Database\Query\QueryBuilder` methods to build more
advanced queries.

.. index:: Database, PHP-API, ext:core
