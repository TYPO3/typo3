..  include:: /Includes.rst.txt

..  _feature-32051-1737628800:

================================================================
Feature: #32051 - Extbase query expression builder for orderings
================================================================

See :issue:`32051`

Description
===========

Extbase queries now support SQL expressions in :sql:`ORDER BY`
clauses through a new fluent API. This enables results to be sorted using
functions such as :sql:`CONCAT`, :sql:`TRIM`, and :sql:`COALESCE`.

The following methods have been added to the
:php:`\TYPO3\CMS\Extbase\Persistence\QueryInterface`:

*   :php:`orderBy()` - Sets a single ordering and replaces any existing
    orderings
*   :php:`addOrderBy()` - Adds an ordering to the existing orderings
*   :php:`concat()` - Creates a :sql:`CONCAT` expression
*   :php:`trim()` - Creates a :sql:`TRIM` expression
*   :php:`coalesce()` - Creates a :sql:`COALESCE` expression

Examples
--------

Order by concatenated fields:

..  code-block:: php

    $query = $this->myRepository->createQuery();
    $query->orderBy(
        $query->concat('firstName', 'lastName'),
        QueryInterface::ORDER_ASCENDING
    );

Order by a trimmed field:

..  code-block:: php

    $query->orderBy(
        $query->trim('title'),
        QueryInterface::ORDER_DESCENDING
    );

Order by the first non-null value (a fallback pattern):

..  code-block:: php

    $query->orderBy(
        $query->coalesce('nickname', 'firstName'),
        QueryInterface::ORDER_ASCENDING
    );

Chain multiple orderings:

..  code-block:: php

    $query
        ->orderBy($query->concat('firstName', 'lastName'))
        ->addOrderBy('createdAt', QueryInterface::ORDER_DESCENDING);

Nest expressions:

..  code-block:: php

    $query->orderBy(
        $query->concat(
            $query->trim('firstName'),
            $query->trim('lastName')
        )
    );

Backwards compatibility
-----------------------

The existing :php:`setOrderings()` method with its array syntax will continue to work:

..  code-block:: php

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

..  index:: PHP-API, ext:extbase
