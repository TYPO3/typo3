
.. include:: /Includes.rst.txt

==========================================================
Breaking: #67792 - Class aliases of Indexed Search removed
==========================================================

See :issue:`67792`

Description
===========

The class aliases of the Indexed Search extension are removed.


Impact
======

These class aliases are not available anymore:

- `tx_indexedsearch`
- `Tx_IndexedSearch_Controller_SearchController`


Affected Installations
======================

Any third party code using the old class aliases.


Migration
=========

Use the new class names:

- `\TYPO3\CMS\IndexedSearch\Controller\SearchFormController`
- `\TYPO3\CMS\IndexedSearch\Controller\SearchController`


.. index:: PHP-API, ext:indexed_search
