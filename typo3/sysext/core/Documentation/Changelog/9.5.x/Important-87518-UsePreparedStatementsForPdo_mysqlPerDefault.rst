.. include:: /Includes.rst.txt

=====================================================================
Important: #87518 - Use prepared statements for pdo_mysql per default
=====================================================================

See :issue:`87518`

Description
===========

Before this adaption, the `pdo_mysql` driver used emulated prepared statements per default.
With that, all returned values of a query were strings.

With this change the behavior changes to use the actual prepared statements,
which return native data types. Thus, if a column is defined as INTEGER,
the returned value in PHP will also be an INTEGER.

It is possible to deactivate this feature as follows:

You need to "overwrite" the option to set `PDO::ATTR_EMULATE_PREPARES`
(reference: https://www.php.net/manual/en/pdo.setattribute.php) in your database connection:

.. code-block:: php

   'Connections' => [
       'Default' => [
           'dbname' => 'some_database_name',
           'driver' => 'pdo_mysql',
           'driverOptions' => [
               \PDO::ATTR_EMULATE_PREPARES => true
            ],
           'password' => 's0meS3curePW!',
           'user' => 'someUser',
       ],
   ],


.. index:: Database, ext:core
