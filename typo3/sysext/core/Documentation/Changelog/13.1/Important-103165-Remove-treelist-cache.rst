.. include:: /Includes.rst.txt

.. _important-103165-1708508519:

==========================================================
Important: #103165 - Database table cache_treelist removed
==========================================================

See :issue:`103165`

Description
===========

Database table :sql:`cache_treelist` has been removed, the database
analyzer will suggest to drop it if it exists.

That cache table was unused since a TYPO3 v12 patch level release, v13
removed leftover handling throughout the Core and removed the table itself.


.. index:: Database, ext:frontend
