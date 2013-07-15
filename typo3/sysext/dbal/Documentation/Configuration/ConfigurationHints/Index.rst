.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _configuration-hints:

Database-specific configuration hints
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Depending on the database you use, the meaning of the
host/username/password settings may slightly differ. This the case for
Oracle, and maybe other RDBMS as well.


.. _hints-mysql:

MySQL
"""""

.. container:: table-row

	RDBMS
		**MySQL**

	Host
		DB server

	Username
		Username

	Password
		Password

	DB Name
		Database name


.. _hints-postgresql:

PostgreSQL
""""""""""

.. container:: table-row

	RDBMS
		**PostgreSQL**

	Host
		DB server

	Username
		Username

	Password
		Password

	DB Name
		Database name


.. _hints-oracle:

Oracle
""""""

.. container:: table-row

	RDBMS
		**Oracle**

	Host
		DB server

	Username
		Username

	Password
		Password

	DB Name
		SID / Instance name

		Must be entered in ``localconf.php`` manually!


.. _hints-firebird:

Firebird
""""""""

.. container:: table-row

	RDBMS
		**Firebird**

	Host
		DB server

	Username
		Username

	Password
		Password

	DB Name
		Full path to the database file, e.g. ``/tmp/testfb.fdb``

		.. note::
			*Currently not working!*


.. _hints-ms-sql:

MS SQL Server (using ODBC)
""""""""""""""""""""""""""

.. container:: table-row

	RDBMS
		**MS SQL Server (using ODBC)**

	Host
		ODBC DNS

	Username
		Username

	Password
		Password

	DB Name
		Set to some dummy string!


If your RDBMS is not shown in the list, try with the usual meaning of
those parameters first, if that doesn't work, but you figure out how
to connect, then please let us know, so we can update this document.
