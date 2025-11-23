..  include:: /Includes.rst.txt

..  _important-105310-1736154829:

===================================================================
Important: #105310 - Create CHAR and BINARY as fixed-length columns
===================================================================

See :issue:`105310`

Description
===========

TYPO3 parses :file:`ext_tables.sql` files into a Doctrine
:abbr:`DBAL (Database Abstraction Layer)` object schema to define a virtual
database scheme, enriched with
:php:`\TYPO3\CMS\Core\Schema\DefaultTcaSchema` information for
:abbr:`TCA (Table Configuration Array)` managed tables and fields.

Fixed- and variable-length variants have been parsed in the past, but failed
to flag the column as :php:`$fixed = true` for the fixed-length database
field types :sql:`CHAR` and :sql:`BINARY`. This resulted in the wrong
creation of these columns as :sql:`VARCHAR` and :sql:`VARBINARY`, which is
now corrected.

+----------------+---------------------+------------------+
| ext_tables.sql | created as (before) | created as (now) |
+================+=====================+==================+
| CHAR(10)       | VARCHAR(10)         | CHAR(10)         |
+----------------+---------------------+------------------+
| VARCHAR(10)    | VARCHAR(10)         | VARCHAR(10)      |
+----------------+---------------------+------------------+
| BINARY(10)     | VARBINARY(10)       | BINARY(10)       |
+----------------+---------------------+------------------+
| VARBINARY(10)  | VARBINARY(10)       | VARBINARY(10)    |
+----------------+---------------------+------------------+

Not all relational database management systems (RDBMS) behave the same way
for fixed-length columns. Implementation differences need to be respected to
ensure consistent query and data behaviour across all supported database
systems.


..  warning::

    Using fixed-length :sql:`CHAR` and :sql:`BINARY` column types requires
    careful handling of data being persisted to and retrieved from the
    database due to different behaviour, especially on PostgreSQL.

Fixed-length :sql:`CHAR`
------------------------

**Key difference between CHAR and VARCHAR**

The main difference between :sql:`CHAR` and :sql:`VARCHAR` is how the
database stores character data. :sql:`CHAR`, which stands for `character`,
is a fixed-length data type. It always reserves a specific amount of
storage space for each value, regardless of whether the actual data
occupies that space entirely. For example, if a column is defined as
:sql:`CHAR(10)` and the word `apple` is stored inside of it, it will still
occupy 10 characters (not just 5). Unused characters are padded with
spaces.

On the other hand, :sql:`VARCHAR`, short for `variable character`, is a
variable-length data type. It only uses as much storage space as needed to
store the actual data without padding. Thus, storing the word `apple` in a
:sql:`VARCHAR(10)` column will only occupy 5 characters.

The main difference between PostgreSQL and MySQL, MariaDB or SQLite is
that PostgreSQL also returns the padded spaces for values that do not fill
the full defined length (for example, `apple[space][space][space][space]
[space]`).

In addition, these padded spaces are respected in query conditions,
sorting or calculations (such as :sql:`concat()`). These differences make a
significant impact and **must** be considered when using :sql:`CHAR`
fields.

**Rule of thumb for fixed-length** :sql:`CHAR` **columns**

*   Use only with **guaranteed fixed-length values** to avoid padding.
*   For 255 or more characters, :sql:`VARCHAR` or :sql:`TEXT` must be used.

**More hints for fixed-length** :sql:`CHAR` **columns**

*   Ensure that stored values are fixed-length (non-space characters), for
    example by using hash algorithms that produce fixed-length identifiers.

*   Ensure that query statements use `trim` or `rightPad` within :sql:`WHERE`,
    :sql:`HAVING` or :sql:`SELECT` operations when values are not guaranteed
    to be fixed-length.

..  tip::

    Helper expressions from
    :php-short:`\TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder`
    can be used.

    For example: :php:`ExpressionBuilder->trim()` or :php:`ExpressionBuilder->rightPad()`.

    *   The use of :sql:`CHAR` **must** be avoided when the column is used with
        the Extbase object-relational mapper (ORM). Fixed-length values cannot be
        guaranteed because trimming or padding is not applied within ORM-generated
        queries. Only when fixed-length values are guaranteed is usage with
        Extbase ORM possible.

    *   Cover custom queries extensively with functional tests executed against
        all supported database platforms. Code within public extensions **should**
        ensure that queries and their operations are tested across all officially
        supported TYPO3 database platforms.

Example of differences in behaviour of fixed-length :sql:`CHAR` types
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The following examples illustrate how different relational database
management systems handle fixed-length :sql:`CHAR` values, and why the
behaviour must be carefully considered when storing or querying such data.

Creating a fixed-length field
"""""""""""""""""""""""""""""

..  code-block:: sql
    :caption: Example ext_tables.sql defining a fixed-length tt_content field

    CREATE TABLE `tt_content` (
        `some_label` CHAR(10) DEFAULT '' NOT NULL,
    );

Inserting example data
""""""""""""""""""""""

Two example rows are added below: one value fits exactly 10 characters, the
other uses only 6 characters.

..  code-block:: php
    :caption: Adding two example rows
    :emphasize-lines: 12,22

    <?php

    use TYPO3\CMS\Core\Database\ConnectionPool;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getConnectionForTable('tt_content');
    // adding a value with 10 chars
    $queryBuilder->insert(
        'tt_content',
        [
            'some_label' => 'some-label',
        ],
        [
            'some_label' => Connection::PARAM_STR,
        ],
    );
    // adding a value with only 6 chars
    $queryBuilder->insert(
        'tt_content',
        [
            'some_label' => 'label1',
        ],
        [
            'some_label' => Connection::PARAM_STR,
        ],
    );

Retrieving the records
""""""""""""""""""""""

..  code-block:: php
    :caption: Get all records from table

    <?php

    use TYPO3\CMS\Core\Database\ConnectionPool;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
      ->getQueryBuilderForTable('tt_content');
    $rows = $queryBuilder
        ->select('uid', 'some_label')
        ->from('tt_content')
        ->executeQuery()
        ->fetchAllAssociative();

Result differences across platforms
"""""""""""""""""""""""""""""""""""

The returned values differ depending on the database platform.

..  code-block:: php
    :caption: Result rows MySQL, MariaDB or SQLite
    :emphasize-lines: 6,10

    <?php

    $rows = [
        [
            'uid' => 1,
            'some_label' => 'some-label',
        ],
        [
            'uid' => 2,
            'some_label' => 'label1',
        ],
    ];

..  code-block:: php
    :caption: Result rows with PostgreSQL
    :emphasize-lines: 6,12

    <?php

    $rows = [
        [
            'uid' => 1,
            'some_label' => 'some-label',
        ],
        [
            'uid' => 2,
            // PostgreSQL applies the fixed length to the value directly,
            // filling it up with spaces
            'some_label' => 'label1    ',
        ],
    ];

..  code-block:: diff
    :caption: Result rows difference between database platforms (commented)

     <?php

     $rows = [
         [
             'uid' => 1,
             'some_label' => 'some-label',
         ],
         [
             'uid' => 2,
    -        'some_label' => 'label1',      // MySQL, MariaDB, SQLite
    +        'some_label' => 'label1    ',  // PostgreSQL
         ],
     ];

..  note::

    Because of this, retrieved values need to be trimmed or padded after
    fetching query results to ensure identical values across all supported
    database systems. Another option is to ensure that persisted data always
    has a fixed-length value, for example by using hashing algorithms (though
    these values are not human-readable).

Querying trimmed versus padded values
"""""""""""""""""""""""""""""""""""""

Using a trimmed value in a :sql:`WHERE` clause can match the row, but the
returned value will differ depending on the database platform.

..  code-block:: php
    :caption: Retrieve with trimmed value
    :emphasize-lines: 12,19,20

    use TYPO3\CMS\Core\Database\ConnectionPool;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
      ->getQueryBuilderForTable('tt_content');
    $rows = $queryBuilder
        ->select('uid', 'some_label')
        ->from('tt_content')
        ->where(
            $queryBuilder->eq(
                'some_label',
                $queryBuilder->createNamedParameter('label1'), // trimmed value!
            ),
        )
        ->executeQuery()
        ->fetchAllAssociative();

    // $rows contains the record for
    // PostgreSQL: $rows = [['uid' => 2, 'some_label' => 'label1    ']];
    // Others....: $rows = [['uid' => 2, 'some_label' => 'label1']];

Enforcing trimmed values in queries
"""""""""""""""""""""""""""""""""""

..  code-block:: php
    :caption: Retrieve with enforced trimmed value.
    :emphasize-lines: 13-17,25,31,32,33

    <?php

    use Doctrine\DBAL\Platforms\TrimMode;
    use TYPO3\CMS\Core\Database\ConnectionPool;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
      ->getQueryBuilderForTable('tt_content');
    $rows = $queryBuilder
        ->select('uid')
        ->addSelectLiteral(
            $queryBuilder->expr()->as(
                $queryBuilder->expr()->trim(
                    'fixed_title',
                    TrimMode::TRAILING,
                    ' '
                ),
                'fixed_title',
            ),
        )
        ->from('tt_content')
        ->where(
            $queryBuilder->eq(
                'some_label',
                $queryBuilder->createNamedParameter('label1'),
            ),
        )
        ->executeQuery()
        ->fetchAllAssociative();

    // $rows contains the record for
    // PostgreSQL: $rows = [['uid' => 2, 'some_label' => 'label1']];
    // Others....: $rows = [['uid' => 2, 'some_label' => 'label1']];
    // and ensures the same content across all supported database systems.

Querying space-padded values in PostgreSQL
""""""""""""""""""""""""""""""""""""""""""

..  code-block:: php
    :caption: Retrieve with space-padded value for PostgreSQL does not retrieve the record
    :emphasize-lines: 16,22

    <?php

    use TYPO3\CMS\Core\Database\ConnectionPool;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    // PostgreSQL specific query!

    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
      ->getQueryBuilderForTable('tt_content');
    $rows = $queryBuilder
        ->select('uid', 'some_label')
        ->from('tt_content')
        ->where(
            $queryBuilder->eq(
                'some_label',
                $queryBuilder->createNamedParameter('label1    '), // untrimmed value!
            ),
        )
        ->executeQuery()
        ->fetchAllAssociative();

    // $rows === []

Additional tools for consistent behaviour
-----------------------------------------

Additional :php-short:`\TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder`
methods can be used to ensure consistent behaviour across all supported
platforms:

*   :php-short:`\TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder::trim()`
*   :php-short:`\TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder::rightPad()`

Recommendation
==============

:sql:`CHAR` and :sql:`BINARY` fields can be used for storage or performance
adjustments, but only when composed data and queries account for the
differences between database systems.

Otherwise, the safe bet is to consistently use :sql:`VARCHAR` and
:sql:`VARBINARY` column types.


..  index:: Database, ext:core
