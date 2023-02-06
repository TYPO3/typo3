.. include:: /Includes.rst.txt

.. _breaking-102875-1705944556:

======================================================================
Breaking: #102875 - Changed Connection method signatures and behaviour
======================================================================

See :issue:`102875`

Description
===========

Signature and behaviour of following methods has been changed:

*   :php:`lastInsertId()` no longer accepts the sequence and field name.
*   :php:`quote()` no longer has a type argument and the value must be a string.

Public :php:`Connection::PARAM_*` class constants has been replaced with the
Doctrine DBAL 4 ParameterType and ArrayParameterType enum definitions.

..  note::
    Doctrine DBAL dropped the support for using the `\PDO::PARAM_*` constants in
    favour of the enum types on several methods. Be aware of this and use the
    `Connection::PARAM_*` constants to reduce required work on upgrading.

Impact
======

Calling :php:`quote()` with a non-string as first argument will result in a
PHP error. Still providing the second argument will not emit an error, but
may be detected by static code analysers.

Calling :php:`lastInsertId()` not directly after the record insert or inserting
records in another table in between will return the incorrect value.

Affected installations
======================

Only installations calling :php:`quote()` with a non-string as first argument
or not using :php:`lastInsertId()` directly after the record insert.

Migration
=========

:php:`lastInsertId()`
---------------------

Returns the last inserted id (auto-created) on the connection. If a record is
inserted and the identity value given, for example when

..  note::
    That means, that the `last inserted id` needs to be retrieved directly before
    inserting a record to another table. That should be the usual workflow used
    in the wild - but be aware of this.

**BEFORE**

..  code-block:: php

    use TYPO3\CMS\Core\Database\Connection as Typo3Connection;
    use TYPO3\CMS\Core\Database\ConnectionPool;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    /** @var Typo3Connection $connection */
    $connection = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getConnectionForTable('tx_myextension_mytable');

    $connection->insert(
        'tx_myextension_mytable',
        [
            'pid' => $pid,
            'some_string' => $someString,
        ],
        [
            Connection::PARAM_INT,
            Connection::PARAM_STR,
        ]
    );
    $uid = $connection->lastInsertId('tx_myextension_mytable');

**AFTER**

..  code-block:: php

    use TYPO3\CMS\Core\Database\Connection as Typo3Connection;
    use TYPO3\CMS\Core\Database\ConnectionPool;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    /** @var Typo3Connection $connection */
    $connection = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getConnectionForTable('tx_myextension_mytable');

    $connection->insert(
        'tx_myextension_mytable',
        [
            'pid' => $pid,
            'some_string' => $someString,
        ],
        [
            Connection::PARAM_INT,
            Connection::PARAM_STR,
        ]
    );
    $uid = $connection->lastInsertId();


.. index:: Database, PHP-API, NotScanned, ext:core
