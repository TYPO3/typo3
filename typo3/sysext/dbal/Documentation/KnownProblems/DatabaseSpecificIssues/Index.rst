.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _database-specific-issues:

Database-specific issues
^^^^^^^^^^^^^^^^^^^^^^^^


.. _native-handler:

Native handler
""""""""""""""

The native handler currently does not support importing static data
through the install tool or EM, the import will be incomplete in most
cases.


.. _postgresql:

PostgreSQL
""""""""""

Currently altering an existing column is not supported, as the
underlying ADOdb library needs a full table description to do that, as
on PostgreSQL you need to drop and recreate a table to change it's
type (this has changed in PostgreSQL 8, but ADOdb doesn't support this
yet).


.. _mssql:

MSSQL
"""""

Tests have shown you need to

- enable ANSI quotes (``SET QUOTED_IDENTIFIER ON``). This can also be done
  through the management console:MS SQL Server Management Studio
  (Express) -> Choose DB (context menu) -> Properties -> Options ->
  Miscellaneous / Quoted Identifiers Enabled : true

- set the max text length in ``php.ini`` to a value higher than the default
  of 4kB; Valid range 0 - 2147483647. Default = 4096.mssql.textlimit =
  5000000; Valid range 0 - 2147483647. Default = 4096.mssql.textsize =
  5000000

- Problems with persistent connections were reported, so if you run into
  trouble, disable them in ``php.ini``

- On SQL Server there are many options regarding the handling of ``NULL`` in
  comparisons. You can make ``"a"=NULL`` to return undefined, ``FALSE``, or ``TRUE``
  depending on a server config. Maybe this helps with certain
  problems...

More problems will arise, depending on the setup details. Further
fixes and documentation is being worked on.
