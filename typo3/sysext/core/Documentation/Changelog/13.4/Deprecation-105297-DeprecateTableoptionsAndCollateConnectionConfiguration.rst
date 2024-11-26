..  include:: /Includes.rst.txt

..  _deprecation-105297-1728836814:

============================================================================
Deprecation: #105297 - `tableoptions` and `collate` connection configuration
============================================================================

See :issue:`105297`

Description
===========

The possibility to configure default table options like charset and collation for the database
analyzer has been introduced using the array
:php:`$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['tableoptions']` with
sub-array keys :php:`charset` and :php:`collate`. These were only used for MySQL and MariaDB
connections.

Since TYPO3 v11 the :php:`tableoptions` keys were silently migrated to
:php:`defaultTableOptions`, which is the proper Doctrine DBAL connection option for
for MariaDB and MySQL.

Furthermore, Doctrine DBAL 3.x switched from using they array key :php:`collate` to
:php:`collation`, ignoring the old array key with Doctrine DBAL 4.x. This was silently
migrated by TYPO3, too.

These options and migration are now deprecated in favor of using the final array
keys and will be removed with TYPO3 v15 (or later) as breaking change.

..  note::

    When migrating, make sure to remove the old :php:`tableoptions` array key, otherwise
    it will take precedence over setting the new :php:`defaultTableOptions` key in TYPO3 v13.

Impact
======

Instances using the database connection options in
:php:`$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['tableoptions']`
array, or using the :php:`collate` key will trigger a :php:`E_USER_DEPRECATED`
notification.


Affected installations
======================

All instances using the mentioned options.

Migration
=========

Review :php:`settings.php` and :php:`additional.php` and adapt the deprecated
configuration by renaming affected array keys.

..  code-block:: php

    # before
    'DB' => [
        'Connections' => [
            'Default' => [
                'tableoptions' => [
                    'collate' => 'utf8mb4_unicode_ci',
                ],
            ],
        ],
    ],

    # after
    'DB' => [
        'Connections' => [
            'Default' => [
                'defaultTableOptions' => [
                    'collation' => 'utf8mb4_unicode_ci',
                ],
            ],
        ],
    ],


..  index:: Database, LocalConfiguration, NotScanned, ext:core
