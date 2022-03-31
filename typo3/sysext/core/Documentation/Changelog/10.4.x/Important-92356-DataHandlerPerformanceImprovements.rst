.. include:: /Includes.rst.txt

========================================================
Important: #92356 - DataHandler performance improvements
========================================================

See :issue:`92356`

Description
===========

The core :php:`DataHandler` is the central backend heart of the system to
persist state changes in the database whenever editors change elements.

Some changes have been applied to reduce the database query load performed
by the :php:`DataHandler` and its related classes. Latest changes especially
dropped a number of useless queries and php operations when relations are handled.
Depending on the handled structure, the :php:`DataHandler` executes up to 30% less
queries than before. More improvements continue to happen and will be ported
to v10 if possible.

.. index:: Backend, Database, ext:core
