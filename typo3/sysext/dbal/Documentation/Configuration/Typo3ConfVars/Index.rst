.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _typo3-conf-vars:

$TYPO3\_CONF\_VARS['EXTCONF']['dbal']
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The DBAL is configurable through ``$TYPO3_CONF_VARS['EXTCONF']['dbal']``
entered in ``ext_localconf.php`` / ``localconf.php``. This table is an
overview of the main keys in this array:


.. _handlercfg-handlerkey:

handlerCfg[ *handlerKey* ]
""""""""""""""""""""""""""

.. container:: table-row

	Key
		handlerCfg[ *handlerKey* ]

	Datatype
		:ref:`->handlerCfg <handlercfg>`

	Description
		Configuration of each data handler you want to use in the system.

		Each handler is identified with a string (``handlerKey``) which is used in
		the ``table2handlerKeys`` configuration (see below) to pair table names
		with handlers.

		There is *always* a default handler needed which has the handlerKey
		"\_DEFAULT". By default this handler is configured with the classic
		username/password/host and database settings from ``localconf.php`` in
		TYPO3.

		If you want to use ADOdb or just need to store a table in another
		database you can configure a handler here and map the tables you need
		to that handler (with ``table2handlerKeys``, see below).


.. _table2handlerkeys-tablename:

table2handlerKeys[ *tablename* ]
""""""""""""""""""""""""""""""""

.. container:: table-row

	Key
		table2handlerKeys[ *tablename* ]

	Datatype
		handlerKey

	Description
		Using other handlers than the "\_DEFAULT" handler key is possible on a
		per-table basis and simply done by entering the table name as key in
		this array and letting the value be the handlerKey you want to use for
		this table!

		**Beware:** The table names here are the values of ``mapTableName`` and
		not the names that TYPO3 will use; thus the real table names.

		**Notice:** If tables are joined *both tables* must use the same
		handlerKey. If they do not TYPO3 will exit with a fatal error!

		You can use the debug options to track all table joins and assess
		which tables can safely be handled together.


.. _mapping-tablename:

mapping[ *tablename* ]
""""""""""""""""""""""

.. container:: table-row

	Key
		mapping[ *tablename* ]

	Datatype
		:ref:`->mapping <mapping>`

	Description
		Configuration of mapping of table and fieldnames. For instance you can
		configure that TYPO3 should use a physical table in the database named
		``typo3_pages`` instead of ``pages``. Or you can map fieldname in a
		similar fashion.

		The point is that TYPO3 always sees a table or field names as TYPO3
		requires internally but in reality the table- or field name could be
		something different in the physical database source.

		There is a performance loss by configuring such mapping of course:
		Result rows are preprocessed before being returned and all SQL queries
		are parsed, transformed and re-compiled again before execution.


.. _debugoptions:

debugOptions
""""""""""""

.. container:: table-row

	Key
		debugOptions

	Datatype
		:ref:`->debug <debug>`

	Description
		Options for various debugging in the DBAL.


