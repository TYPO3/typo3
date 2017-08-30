
.. include:: ../../Includes.txt

=====================================================================
Breaking: #75454 - LocalConfiguration DB config structure has changed
=====================================================================

See :issue:`75454`

Description
===========

To provide support for multiple database connections and remapping tables to different
database systems within the TYPO3 Core the configuration format for database connections
in `LocalConfiguration.php` / `$GLOBALS['TYPO3_CONF_VARS']['DB']` has changed.

The new configuration array structure:

.. code-block:: php

	'DB' => [
		'Connections' => [
			'Default' => [
				'driver' => 'mysqli',
				'dbname' => 'typo3_database',
				'password' => 'typo3',
				'host' => '127.0.0.1',
				'port' => 3306,
				'user' => 'typo3',
				'unix_socket' => '',
				'charset' => 'utf-8',
			],
		],
	],

Be aware that besides the deeper nesting below 'Connections/Default' some of the configuration
keys have been renamed. It is required to provide the new configuration key `driver` with a
value of `mysqli` explicitly.

The following table lists the changed configuration keys and the appropriate values if these
have changed.

============================   ===============================================
Old name                       New name
============================   ===============================================
DB/username                    DB/Connections/Default/user
DB/password                    DB/Connections/Default/password
DB/host                        DB/Connections/Default/host
DB/port                        DB/Connections/Default/port
DB/socket                      DB/Connections/Default/unix_socket
DB/database                    DB/Connections/Default/dbname
SYS/setDBinit                  DB/Connections/Default/initCommands
SYS/no_pconnect                DB/Connections/Default/persistentConnection
SYS/dbClientCompress           DB/Connections/Default/driverOptions
                               Valid values for MySQLi connections:
                               0  compression disabled
                               32 compression enabled
============================   ===============================================


Impact
======

Connections to the database will fail with an exception until the configuration has been migrated
to the new structure.


Affected Installations
======================

All Installations


Migration
=========

The Install Tool will migrate the configuration information for the default connection to the new
format. Installations overriding the database configuration using `AdditionalConfiguration.php`
or other means need to ensure the new format is being used.

.. index:: Database, LocalConfiguration
