..  include:: /Includes.rst.txt

..  _important-107342-1761324027:

===============================================================================
Important: #107342 - Extend listForms method in FormPersistenceManagerInterface
===============================================================================

See :issue:`107342`

Description
===========

With this change, the method signature of :php:`listForms()`, defined by the :php:`FormPersistenceManagerInterface`,
has been extended by two arguments: :php:`$orderField` and :php:`$orderDirection`.
The new definition is:
:php:`public function listForms(array $formSettings, string $orderField = '', ?SortDirection $orderDirection = null): array;`


Affected Installations
======================

Some TYPO3 installations may use this interface for their own FormPersistenceManager, even though it is marked as internal.


Possible Migration
==================

If you have implemented your own FormPersistenceManager, you need to update the method signature accordingly.

..  index:: Backend, ext:form, NotScanned
