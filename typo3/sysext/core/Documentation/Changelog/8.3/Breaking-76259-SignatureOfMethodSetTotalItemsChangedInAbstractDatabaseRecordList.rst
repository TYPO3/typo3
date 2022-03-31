
.. include:: /Includes.rst.txt

=====================================================================================
Breaking: #76259 - Signature of setTotalItems() in AbstractDatabaseRecordList changed
=====================================================================================

See :issue:`76259`

Description
===========

As part of migrating the core code to use Doctrine DBAL the signature of the method
:php:`AbstractDatabaseRecordList::setTotalItems()` has changed.

The new signature is:

.. code-block:: php

    public function setTotalItems(string $table, int $pageId, array $constraints)
    {
        $queryBuilder = $this->getQueryBuilder($table, $pageId, $constraints);
        $this->totalItems = (int)$queryBuilder->count('*')
            ->execute()
            ->fetchColumn();
    }

The parameter :php:`$constraints` is expected to be an array of Doctrine Expressions
or SQL fragments.

In case of SQL fragments proper quoting needs to be ensured by the invoking method.
SQL fragments should not have a leading ` AND ` SQL operator.


Impact
======

3rd party extensions using :php:`AbstractDatabaseRecordList::setTotalItems()` need
to update the method invocation.


Affected Installations
======================

Installations using 3rd party extensions that use :php:`AbstractDatabaseRecordList::setTotalItems()`.


Migration
=========

Instead of passing an array of parameters built using the deprecated :php:`makeQueryArray()` method
explicitly pass in the table name, page id and any additional query restrictions required.

.. index:: Database, PHP-API, Backend
