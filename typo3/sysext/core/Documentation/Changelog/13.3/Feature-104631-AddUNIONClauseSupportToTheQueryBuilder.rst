.. include:: /Includes.rst.txt

.. _feature-104631-1723714985:

=================================================================
Feature: #104631 - Add `UNION Clause` support to the QueryBuilder
=================================================================

See :issue:`104631`

Description
===========

The :sql:`UNION` clause is used to combine the result-set of two or more
:sql:`SELECT` statements, which all database vendors supports with usual
specialities for each.

Still, there is a common shared subset which works for all of them:

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
which introduces two new API method on the QueryBuilder:

* :php:`union(string|QueryBuilder $part)` to create first UNION query part
* :php:`addUnion(string|QueryBuilder $part, UnionType $type = UnionType::DISTINCT)`
  to add additional :sql:`UNION (ALL|DISTINCT)` query parts with the selected union
  query type.

TYPO3 decorates the Doctrine DBAL QueryBuilder to provide for most API methods automatic
quoting of identifiers and values **and**  to apply database restrictions automatically
for :sql:`SELECT` queries.

The Doctrine DBAL API has been adopted now to provide the same surface for the
TYPO3 :php:`\TYPO3\CMS\Core\Database\Query\QueryBuilder` and the intermediate
:php:`\TYPO3\CMS\Core\Database\Query\ConcreteQueryBuilder` to make it easier to
create :sql:`UNION` clause queries. The API on both methods allows to provide
dedicated QueryBuilder instances or direct queries as strings in case it is needed.

..  note::

    Providing :sql:`UNION` parts as plain string requires the developer to take
    care of proper quoting and escaping within the query part.

Another point worth to mention is, that only `named placeholder` can be used
and registered on the most outer :php:`QueryBuilder` object instance, similar
to advanced query creation using for example :sql:`SUB QUERIES`.

..  warning::

    :php:`QueryBuilder` can be used create :sql:`UNION` clause queries not
    compatible with all database, for example using LIMIT/OFFSET in each
    part query or other stuff.

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

    use TYPO3\CMS\Core\Database\Connection;
    use TYPO3\CMS\Core\Database\ConnectionPool;
    use TYPO3\CMS\Core\Database\Query\QueryBuilder;

    final readonly MyService {
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
        $firstPartQueryBuilder->getRestrictions()->removeAll();
        $secondPartQueryBuilder = $connection->createQueryBuilder();
        $secondPartQueryBuilder->getRestrictions()->removeAll();
        $expr = $unionQueryBuilder->expr();

        $firstPartQueryBuilder
          ->select('uid', 'pid', 'title')
          ->from('pages')
          ->where(
            $expr->eq(
              'pages.uid',
              $unionQueryBuilder->createNamedParameter($pageIdOne),
          );
        $secondPartQueryBuilder
          ->select('uid', 'pid', 'title')
          ->from('pages')
          ->where(
            $expr->eq(
              'pages.uid',
              $unionQueryBuilder->createNamedParameter($pageIdOne),
          );

          return $unionQueryBuilder
            ->union($firstPartQueryBuilder)
            ->addUnion($secondPartQueryBuilder, UnionType::DISTINCT)
            ->orderBy('uid', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();
      }
    }

which would create following query for MySQL with :php:`$pageIdOne = 100` and
:php:`$pageIdTwo = 10`:

..  code-block:: sql

        (SELECT `uid`, `pid`, `title` FROM pages WHERE `pages`.`uid` = 100)
    UNION
        (SELECT `uid`, `pid`, `title` FROM pages WHERE `pages`.`uid` = 10)
    ORDER BY `uid` ASC


Impact
======

Extension authors can use the new :php:`QueryBuilder` methods to build more
advanced queries.

.. index:: Database, PHP-API, ext:core
