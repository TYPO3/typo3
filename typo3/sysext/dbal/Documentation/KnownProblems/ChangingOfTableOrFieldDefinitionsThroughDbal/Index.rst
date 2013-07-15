.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _changing-of-table-or-field-definitions-through-dbal:

Changing of table or field definitions through DBAL
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

MySQL allows the user to change nearly everything at runtime, you can
change field types, constraints, defaults and more on a field and
MySQL handles data conversion and similar tasks transparently. This
does work very differently with many other DB systems, some things do
not work at all. Since we rely on existing abstraction libraries, we
are bound to what they offer. There may be cases in which it simply is
impossible to do a needed change to the database automatically.

It is even problematic to add fields to existing tables, although this
seems like a rather simple thing to do. But, alas, it is not.

It must be noted that e.g. the recent version 8.0 of PostgreSQL was a
huge step forward in that direction, as it allows a lot more of those
operations in an easy way, than earlier versions. So if you want to
run TYPO3 on PostgreSQL, use version 8.0 or later, if possible.
