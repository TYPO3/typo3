.. include:: /Includes.rst.txt

.. _feature-32051-1737628800:

================================================================
Feature: #32051 - Extbase Query expression builder for orderings
================================================================

See :issue:`32051`

Description
===========

Extbase queries now support SQL function expressions in ORDER BY clauses
through a new fluent API. This enables sorting results by computed values
using functions like CONCAT, TRIM, and COALESCE.

New methods have been added to :php:`\TYPO3\CMS\Extbase\Persistence\QueryInterface`:

*  :php:`orderBy()` - Sets a single ordering, replacing any existing orderings
*  :php:`addOrderBy()` - Adds an ordering to existing orderings
*  :php:`concat()` - Creates a CONCAT expression
*  :php:`trim()` - Creates a TRIM expression
*  :php:`coalesce()` - Creates a COALESCE expression

Examples
--------

Order by concatenated fields:

.. code-block:: php

    $query = $this->myRepository->createQuery();
    $query->orderBy(
        $query->concat('firstName', 'lastName'),
        QueryInterface::ORDER_ASCENDING
    );

Order by trimmed field:

.. code-block:: php

    $query->orderBy(
        $query->trim('title'),
        QueryInterface::ORDER_DESCENDING
    );

Order by first non-null value (fallback pattern):

.. code-block:: php

    $query->orderBy(
        $query->coalesce('nickname', 'firstName'),
        QueryInterface::ORDER_ASCENDING
    );

Chain multiple orderings:

.. code-block:: php

    $query
        ->orderBy($query->concat('firstName', 'lastName'))
        ->addOrderBy('createdAt', QueryInterface::ORDER_DESCENDING);

Nest expressions:

.. code-block:: php

    $query->orderBy(
        $query->concat(
            $query->trim('firstName'),
            $query->trim('lastName')
        )
    );

Backwards Compatibility
-----------------------

The existing :php:`setOrderings()` method with array syntax continues to work:

.. code-block:: php

    $query->setOrderings([
        'title' => QueryInterface::ORDER_ASCENDING,
        'date' => QueryInterface::ORDER_DESCENDING,
    ]);

Impact
======

Developers can now use SQL functions in Extbase query orderings without
resorting to raw SQL statements. This enables more flexible sorting logic
while maintaining the abstraction and security benefits of the Extbase
persistence layer.

.. index:: PHP-API, ext:extbase
