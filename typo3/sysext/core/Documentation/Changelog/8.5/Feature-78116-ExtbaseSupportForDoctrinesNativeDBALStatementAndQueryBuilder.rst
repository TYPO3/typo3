.. include:: /Includes.rst.txt

=======================================================================================
Feature: #78116 - Extbase support for Doctrine's native DBAL Statement and QueryBuilder
=======================================================================================

See :issue:`78116`

Description
===========

With the change to Doctrine DBAL Extbase's direct query functionality also supports `QueryBuilder` objects and instances of
`\Doctrine\DBAL\Statement` as prepared statements instead of only `\TYPO3\CMS\Core\Database\PreparedStatement`.

The following example could happen inside any Extbase Repository using native Doctrine DBAL statements:

.. code-block:: php

   $connection = $this->objectManager->get(ConnectionPool::class)->getConnectionForTable('mytable');
   $statement = $this->objectManager->get(
      \Doctrine\DBAL\Statement::class
      'SELECT * FROM mytable WHERE uid=? OR title=?',
      $connection
   );

   $query = $this->createQuery();
   $query->statement($statement, [$uid, $title]);


The following example shows the usage with the QueryBuilder object:

.. code-block:: php

   $queryBuilder = $this->objectManager->get(ConnectionPool::class)->getQueryBuilderForTable('mytable');

   ... do the SQL query with the query builder.

   $query = $this->createQuery();
   $query->statement($queryBuilder);

.. index:: Database, PHP-API, ext:extbase
