.. include:: ../../Includes.txt

===============================================================
Important: #80506 - Dbal compatible field quoting in TypoScript
===============================================================

See :issue:`80506`

Description
===========

Properties in :typoscript:`TypoScript` dealing with SQL fragments need proper quoting of field names to be
compatible with different database drivers. The database framework of the core now applies proper quoting
to field names if they are wrapped as :typoscript:`{#fieldName}`

It is advised to adapt extensions accordingly to run successfully on databases like PostgreSQL.

Example for a :typoscript:`select.where` TypoScript snippet:

.. code-block:: typoscript

    select.where = {#colPos}=0

.. index:: Database, Frontend, TypoScript
