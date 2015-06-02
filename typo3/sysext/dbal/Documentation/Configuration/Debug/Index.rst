.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _debug:

->debug
^^^^^^^

Debugging options


.. _enabled:

enabled
"""""""

.. container:: table-row

	Key
		enabled

	Datatype
		boolean

	Description
		If set, TYPO3 will log every SQL execution in the ``tx_dbal_debuglog``
		table.

		This option must be set for the other options below to work.

		You can view the log from the backend; There is a DBAL module in the
		Tools main module.


.. _printerrors:

printErrors
"""""""""""

.. container:: table-row

	Key
		printErrors

	Datatype
		boolean

	Description
		If set, SQL errors will be ``debug()``'ed to browser after any SQL
		execution.


.. _explain:

EXPLAIN
"""""""

.. container:: table-row

	Key
		EXPLAIN

	Datatype
		boolean

	Description
		Will log the result of a ``EXPLAIN SELECT...`` in case of select-queries.
		Can help you to benchmark the performance of your indexes in the
		database.

		When using Oracle (the ADOdb oci8 driver) you **must** create the
		necessary ``PLAN_TABLE`` manually, according to the Oracle version you
		use. See http://www.adp-gmbh.ch/ora/explainplan.html for some
		background information.


.. _parsequery:

parseQuery
""""""""""

.. container:: table-row

	Key
		parseQuery

	Datatype
		boolean

	Description
		Will parse all possible parts of the SQL queries, compile them again
		and match the results. If the parsed and recompiled queries did not
		match they will enter the log table and can subsequently be addressed.
		This will help you to spot "TYPO3 incompatible SQL" (as defined by the
		core parser of ``\TYPO3\CMS\Dbal\Database\SqlParser``).


.. _jointables:

joinTables
""""""""""

.. container:: table-row

	Key
		joinTables

	Datatype
		boolean

	Description
		Will log every SELECT query performed with a table join - necessary to
		make sure that all tables that may be joined in TYPO3 is also handled
		by the same handlerKey (which is required for obvious reasons!)


.. _numberrows:

numberRows
""""""""""

.. container:: table-row

	Key
		numberRows

	Datatype
		boolean

	Description
		Will log number of affected rows in previous INSERT, UPDATE or DELETE
		operation or number of returned rows in previous SELECT query.


.. _backtrace:

backtrace
"""""""""

.. container:: table-row

	Key
		backtrace

	Datatype
		integer

	Description
		If set, the given number of backtrace steps are logged with the query.



.. _debug-example:

Example
"""""""

This enables all debug options::

	$TYPO3_CONF_VARS['EXTCONF']['dbal']['debugOptions'] = array(
	    'enabled' => TRUE,        // Generally, enable debugging.
	    'printErrors' => TRUE,    // Enable output of SQL errors after query executions.
	    'EXPLAIN' => TRUE,        // EXPLAIN SELECT ...(Only on default handler)
	    'parseQuery' => TRUE,     // Parsing queries, testing parsability (All queries)
	    'joinTables' => TRUE,
	    'numberRows' => TRUE,     // Number of affected/returned rows (INSERT, UPDATE, DELETE or SELECT)
	);
