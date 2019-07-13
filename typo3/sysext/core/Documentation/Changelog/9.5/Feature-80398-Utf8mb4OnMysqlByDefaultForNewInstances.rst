.. include:: ../../Includes.txt

===============================================================
Feature: #80398 - utf8mb4 on mysql by default for new instances
===============================================================

See :issue:`80398`

Description
===========

New instances created by the TYPO3 installer now set `utf8mb4` as charset and `utf8mb4_unicode_ci`
collation by default for instances running on MySQL. This allows 4 byte unicode characters
like emojis in MySQL.

If upgrading instances, admins may change :file:`LocalConfiguration.php` to use this feature.
The core does not provide mechanisms to update the collation of existing tables
from `utf8_unicode_ci` to `utf8mb4_unicode_ci` for existing instances, though. Admins need
to manage that on their own if needed, the reports module shows an information if the
table schema uses mixed collations. This should be fixed after manually configuring
`utf8mb4` to avoid SQL errors when joining tables having different collations.

Also note that manually upgrading to `utf8mb4` may lead to index length issues: The maximum key
length on InnoDB tables is often 767 bytes and options to increase that have even been actively
removed, for instance in recent MariaDB versions.
A typical case is an index on a varchar(255) field: The DBMS assumes the worst case for the index
length, which is 3 bytes per character for a utf8 (utf8mb3), but 4 bytes for utf8mb4: With utf8,
the maximum index length is 3*255 + 1 = 766 bytes which fits into 767, but with utf8mb4, this
is 4*255 + 1 = 1021 bytes, which exceeds the maximum length and leads to SQL errors when setting
such an index.
This scenario gets more complex with combined indices and may need manual investigation when
upgrading an existing instance from from `utf8` to `utf8mb4`. One solution is to restrict the
index length in ext_tables.sql of the affected extension: :php:`KEY myKey (myField(191))`, which
in this case leads to 4*191 + 1 = 764 bytes as maximum used length.

The basic settings to use `utf8mb4` in :file:`LocalConfiguration.php` are:

.. code-block:: php

   'DB' => [
       'Connections' => [
           'Default' => [
               'driver' => 'mysqli',
               ...
               'charset' => 'utf8mb4',
               'tableoptions' => [
                    'charset' => 'utf8mb4',
                    'collate' => 'utf8mb4_unicode_ci',
               ],
           ],
       ],
   ],


Impact
======

`utf8mb4` is an allowed charset and `utf8mb4_unicode_ci` is an allowed collation and
used by default for new instances running on MySQL.

.. index:: PHP-API, LocalConfiguration, Database
