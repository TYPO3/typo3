.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _handlercfg:

->handlerCfg
^^^^^^^^^^^^

Configuration of a data handler


.. _type:

type
""""

.. container:: table-row

	Key
		type

	Datatype
		handler type (string)

	Description
		The type of the handler.

		The type is a fixed keyword between these:

		- native
		- adodb
		- userdefined

		(See description of each in the introduction above)

		The "native" handler is used by default (and is MySQL-only!)

		The handler type will determine what options are available for
		"config"


.. _config:

config
""""""

.. container:: table-row

	Key
		config

	Datatype
		array

	Description
		Array containing configuration for the handler. See below for options.

		Notice that the options are supported depending on handler type. For
		this, see information in italic and square brackets.


.. _config-username:

config[username]
""""""""""""""""

.. container:: table-row

	Key
		config[username]

	Datatype
		string

	Description
		Username for connection

		.. warning::
			For the "_DEFAULT" handler this is overridden by
			``$typo_db_username`` from ``localconf.php``

		.. note::
			Only native / adodb


.. _config-password:

config[password]
""""""""""""""""

.. container:: table-row

	Key
		config[password]

	Datatype
		string

	Description
		Password for connection

		.. warning::
			For the "_DEFAULT" handler this is overridden by
			``$typo_db_password`` from ``localconf.php``

		.. note::
			Only native / adodb


.. _config-host:

config[host]
""""""""""""

.. container:: table-row

	Key
		config[host]

	Datatype
		string

	Description
		Host for the database server

		.. warning::
			For the "_DEFAULT" handler this is overridden by
			``$typo_db_host`` from ``localconf.php``

		.. note::
			Only native / adodb


.. _config-port:

config[port]
""""""""""""

.. container:: table-row

	Key
		config[port]

	Datatype
		integer

	Description
		Port for the database server

		.. note::
			Only native / adodb


.. _config-database:

config[database]
""""""""""""""""

.. container:: table-row

	Key
		config[database]

	Datatype
		string

	Description
		The database name

		.. warning::
			For the "_DEFAULT" handler this is overridden by
			``$typo_db`` from ``localconf.php``

		.. note::
			Only native / adodb


.. _config-driver:

config[driver]
""""""""""""""

.. container:: table-row

	Key
		config[driver]

	Datatype
		string

	Description
		Which driver, (eg. ``mysql``, ``oci8`` etc.). Depending on API (see ADOdb
		documentation for details)

		.. note::
			Only adodb


.. _config-driveroptions:

config[driverOptions]
"""""""""""""""""""""

.. container:: table-row

	Key
		config[driverOptions]

	Datatype
		array

	Description
		Key/value pairs of driver-specific options.

		E.g., ``array('connectSID' => TRUE)`` to connect to an Oracle database with
		a SID instead of a service name

		.. warning::
			Available options are found in ADOdb, in the class you use
			as driver to connect to your database

		.. note::
			Only adodb


.. _config-sequencestart:

config[sequenceStart]
"""""""""""""""""""""

.. container:: table-row

	Key
		config[sequenceStart]

	Datatype
		integer

	Description
		The number which is used as initial value for sequences when they are
		generated.

		.. note::
			Only adodb


.. _config-classfile:

config[classFile]
"""""""""""""""""

.. container:: table-row

	Key
		config[classFile]

	Datatype
		string

	Description
		Class file for user defined DB handler class.

		E.g., ``EXT:dbal/handlers/class.tx_dbal_handler_xmldb.php``

		Must be relative path to ``PATH_site``. The ``EXT:`` prefix can be used for
		locations inside of extensions.

		.. note::
			Only userdefined


.. _config-class:

config[class]
"""""""""""""

.. container:: table-row

	Key
		config[class]

	Datatype
		string

	Description
		Class name for the handler inside of config[classFile].

		E.g., ``tx_dbal_handler_xmldb``

		Please see examples/templates of userdefined handlers inside
		``dbal/handlers/`` directory.

		.. note::
			Only userdefined



.. _using-adodb-or-pear-db-for-the-default-handler:

Using ADOdb or PEAR::DB for the \_DEFAULT handler
"""""""""""""""""""""""""""""""""""""""""""""""""

.. code-block:: php
	:linenos:

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['handlerCfg'] = array(
	    '_DEFAULT' => array(
	        'type' => 'adodb',
	        'config' => array(
	            'driver' => 'mysql',
	        )
	    )
	);

If you need to use other databases, just change the value in line 5 to
the name of the other database driver. See ADOdb manual for details.


.. _using-another-mysql-database-for-the-tt-guest-and-sys-note-tables:

Using another MySQL database for the "tt_guest" and "sys_note" tables
"""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

.. code-block:: php
	:linenos:

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['handlerCfg'] = array(
	    '_DEFAULT' => array (
	        'type' => 'native',
	        'config' => array(
	            'username' => '',        // Set by default (overridden)
	            'password' => '',        // Set by default (overridden)
	            'host' => '',            // Set by default (overridden)
	            'database' => '',        // Set by default (overridden)
	        )
	    ),
	    'alternativeMySQLdb' => array(
	        'type' => 'native',
	        'config' => array(
	            'username' => 'your_username',
	            'password' => 'your_password',
	            'host' => 'localhost',
	            'database' => 'alternative_database_name',
	        )
	    ),
	);

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['table2handlerKeys'] = array(
	    'tt_guest' => 'alternativeMySQLdb',
	    'sys_note' => 'alternativeMySQLdb',
	);

In line 24 and 25 we configure the two tables to use the *handler
key* "alternativeMySQLdb" instead of the "\_DEFAULT" handler. In both
cases the handlers will connect natively to MySQL - but two different
databases at the "same time".


.. _storing-tt-guest-and-sys-note-tables-in-oracle:

Storing "tt_guest" and "sys_note" tables in Oracle
""""""""""""""""""""""""""""""""""""""""""""""""""

.. code-block:: php
	:linenos:

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['handlerCfg'] = array(
	    '_DEFAULT' => array(
	        'type' => 'native',
	        'config' => array(
	            'username' => '',        // Set by default (overridden)
	            'password' => '',        // Set by default (overridden)
	            'host' => '',            // Set by default (overridden)
	            'database' => '',        // Set by default (overridden)
	        )
	    ),
	    'oracleDB' => array(
	        'type' => 'adodb',
	        'config' => array(
	            'username' => 'your_username',
	            'password' => 'your_password',
	            'host' => 'localhost',
	            'database' => 'oracleDB',
	            'driver' => 'oci8'
	        )
	    ),
	);

	$TYPO3_CONF_VARS['EXTCONF']['dbal']['table2handlerKeys'] = array(
	    'tt_guest' => 'oracleDB',
	    'sys_note' => 'oracleDB',
	);

This example is basically similar to the former, just that the key
name was changed to "oracleDB" for convenience.

The real change is that

- line 12 configures ADOdb to be used and

- line 18 configures ADOdb to use the ``oci8`` driver instead of MySQL.


.. _storing-tt-guest-and-sys-note-tables-in-an-xml-file:

Storing "tt_guest" and "sys_note" tables in an XML file
"""""""""""""""""""""""""""""""""""""""""""""""""""""""

.. code-block:: php
	:linenos:

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['handlerCfg'] = array(
	    '_DEFAULT' => array(
	        'type' => 'native',
	        'config' => array(
	            'username' => '',        // Set by default (overridden)
	            'password' => '',        // Set by default (overridden)
	            'host' => '',            // Set by default (overridden)
	            'database' => '',        // Set by default (overridden)
	        )
	    ),
	    'xmlDB' => array(
	        'type' => 'userdefined',
	        'config' => array(
	            'classFile' => 'EXT:dbal/handlers/class.tx_dbal_handler_xmldb.php',
	            'class' => 'tx_dbal_handler_xmldb',
	            'tableFiles' => array(
	                'tt_guest' => 'fileadmin/tt_guest.xml',
	                'sys_note' => 'fileadmin/sys_note.xml',
	            )
	        )
	    ),
	);

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['table2handlerKeys'] = array(
	    'tt_guest' => 'xmlDB',
	    'sys_note' => 'xmlDB',
	);

In this example the handler key ``xmlDB` sets up a userdefined handler;
basically a PHP class with certain functions for INSERT / SELECT /
UPDATE and DELETE operations and data-to-disc I/O. In this case it is
just an example using the class ``tx_dbal_handler_xmldb`` which is
shipped with this extensions. Configuration might be different since
that class (at time of writing) is not finished.

Anyways, the point is that this userdefined, PHP written handler will
simulate an SQL server and allow to insert, select, update and delete
records which is actually stored in some XML files and not real
database tables!

This goes to show the possibilities, right... :-)


.. _notice-on-joins-and-tables-separated-into-different-databases:

Notice on joins and tables separated into different databases
"""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

If you chose to configure that some tables like ``sys_note`` and
``tt_guest`` will go into other databases as the example shows above,
you will have to make sure  *they are never joined with any tables
from other databases* . If they are, you will face a fatal error from
the DBAL; logically you cannot join tables across database systems!
