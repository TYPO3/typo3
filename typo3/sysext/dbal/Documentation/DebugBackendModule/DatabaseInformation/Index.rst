.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _cached-database-information:

Viewing cached database information
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The DBAL extension makes heavy use of caching for field information.
This includes primary keys, auto\_increment fields and native/meta
types of fields.

To see the cached data select  *View cached data* in the DBAL Debug
backend module. From there you can clear the cache file, if that seems
necessary (it is automatically cleaned whenever the database structure
is changed from within TYPO3).
