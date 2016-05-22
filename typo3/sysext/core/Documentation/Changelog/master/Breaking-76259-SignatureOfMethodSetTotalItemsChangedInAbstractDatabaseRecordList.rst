=====================================================================================
Breaking: #76259 - Signature of setTotalItems() in AbstractDatabaseRecordList changed
=====================================================================================

Description
===========

As part of the migration of the core code to use Doctrine the signature of the method
:php:``PageLayoutView::getResult()`` was changed.

The new signature is:

.. code-block:: php

    public function setTotalItems(string $table, int $pageId, array $constraints)
    {
        $queryBuilder = $this->getQueryBuilder($table, $pageId, $constraints);
        $this->totalItems = (int)$queryBuilder->count('*')
            ->execute()
            ->fetchColumn();
    }

The parameter ``$constraints`` is expected to be an array of Doctrine Expressions
or SQL fragments.

In the case of SQL fragments proper quoting needs to be ensured by the invoking method.
SQL fragments should not have a leading `` AND `` SQL operator.


Impact
======

3rd party extensions using :php:``AbstractDatabaseRecordList::setTotalItems()`` need
to update the method invokation.


Affected Installations
======================

Installations using 3rd party extensions that use :php:``AbstractDatabaseRecordList::setTotalItems()``.


Migration
=========

Instead of passing in an array of parameters built using the deprecated ::php::``makeQueryArray`` method
explictly pass in the table name, page id and any additional query restrictions required.

