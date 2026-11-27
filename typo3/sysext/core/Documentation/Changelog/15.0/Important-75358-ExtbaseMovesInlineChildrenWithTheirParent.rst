..  include:: /Includes.rst.txt

..  _important-75358-1795778408:

===================================================================
Important: #75358 - Extbase moves inline children with their parent
===================================================================

See :issue:`75358`

Description
===========

Extbase now persists a changed page ID of an existing entity. Until now,
calling :php:`setPid()` on an entity fetched from the database had no
effect on the stored record.

When the page ID of such an entity changes, the child records of its
inline relations that use :php:`foreign_field` move to the same page,
including children of those children. This is what the DataHandler does
when an editor moves a record in the backend. Children of relations that
were not loaded yet are loaded for that.

A field that sets
:php:`['config']['behaviour']['disableMovingChildrenWithParent'] = true`
keeps its children on their page, as it does in the DataHandler.

Extensions that call :php:`setPid()` on existing entities without
intending to move them need to remove that call.

..  index:: PHP-API, TCA, ext:extbase
