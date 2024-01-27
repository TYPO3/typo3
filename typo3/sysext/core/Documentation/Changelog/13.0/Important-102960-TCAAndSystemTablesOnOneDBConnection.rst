.. include:: /Includes.rst.txt

.. _important-102960-1706383783:

===============================================================
Important: #102960 - TCA and system tables on one DB connection
===============================================================

See :issue:`102960`

Description
===========

TYPO3 v13 expects all database core system tables and especially all tables from
extensions that have :php:`TCA` attached to be configured for the main
:php:`Default` connection.

The TYPO3 core historically allowed configuration of any database table to
point to additional configured database connections. This technically allows
"ripping off" any table from the default connection table set, and have it on
a different database.

TYPO3 now needs to restrict this a bit more to unblock further development and
performance improvements: The core now declares that all "main" core tables
(especially :sql:`sys_*`, :sql:`pages`, :sql:`tt_content` and in general all
tables that have :php:`TCA`) must not be declared for any connection
other than the configured :php:`Default` connection.

The reasons for this are actually pretty obvious: When looking at performance
issues of bigger instances, the sheer amount of queries is usually the top-one
bottleneck. The core aims to reduce this mid-term using more clever queries that
join and prepare more data in fewer queries. Cross database joins are pretty much
impossible.

This restriction has practically been the case with earlier core versions already:
For instance when a :php:`TCA` table configured "categories" and used them, the
core already uses various joins to find categories attached to a record. Other
places have been adapted with TYPO3 v13 already, for instance the
:php:`ReferenceIndex`. The core will try to additionally simplify the current
API by avoiding :php:`getConnectionForTable()` with further patches.

Apart from this, instances can still configure additional database connections.
One target is directly querying data from some third party application in some
custom extension. Another use case are database based caches: Those will of
course never execute queries to join non-cache related data. A typical use is
configuring a special database server for speed over integrity and persistence
(for instance RAM driven) to power the "page" cache tables. This will continue to work,
but might be turned into a dedicated feature of specific database backends, later.

.. index:: Database, ext:core
