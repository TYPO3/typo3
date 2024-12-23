..  include:: /Includes.rst.txt

..  _important-105310-1736154829:

===================================================================
Important: #105310 - Create CHAR and BINARY as fixed-length columns
===================================================================

See :issue:`105310`

Description
===========

TYPO3 parses `ext_tables.sql` files into a Doctrine DBAL object schema to define
a virtual database scheme, enriched with :php:`DefaultTcaSchema` information for
TCA-managed tables and fields.

Fixed and variable length variants have been parsed already in the past, but missed
to flag the column as :php:`$fixed = true` for the fixed-length database field types
:sql:`CHAR` and :sql:`BINARY`. This resulted in the wrong creation of these columns as
:sql:`VARCHAR` and :sql:`VARBINARY`, which is now corrected.

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

Not all database systems (RDBMS) act the same way for fixed-length columns. Implementation
differences need to be respected to ensure the same query/data behaviour across all supported
database systems.

..  warning::

    Using fixed-length :sql:`CHAR` and :sql:`BINARY` column types requires to carefully work
    with data being persisted and retrieved from the database due to differently
    behaviour specifically of PostgreSQL.


Fixed-length :sql:`CHAR`
------------------------

**Key Difference Between CHAR and VARCHAR**

The main difference between :sql:`CHAR` and :sql:`VARCHAR` is how the database
stores character data in a database. :sql:`CHAR`, which stands for `character`,
is a fixed-length data type, meaning it always reserves a specific amount of
storage space for each value, regardless of whether the actual data occupies
that space entirely. For example, if a column is defined as :sql:`CHAR(10)` and
the word `apple` is stored inside of it, it will still occupy 10 characters worth of
space (not just 5). Unusued characters are padded with extra spaces.

On the other hand, :sql:`VARCHAR`, short for `variable character`, is a
variable-length data type. It only uses as much storage space as needed
to store the actual data without padding. So, storing the word `apple` in a
:sql:`VARCHAR(10)` column will only occupy 5 characters worth of
space, leaving the remaining table row space available for other data.

The main difference from `PostgreSQL` to `MySQL`/`MariaDB`/`SQLite` is:
`PostgreSQL` also returns the filler-spaces for a value not having the
column length (returning `apple[space][space][space][space][space]`).

On top of that, the filled-up spaces are also respected for query conditions, sorting
or data calculations (:sql:`concat()` for example). These two facts makes a huge
difference and **must** be carefully taken into account when using :sql:`CHAR`
field.

**Rule of thumb for fixed-length** :sql:`CHAR` **columns**

*   Only use with **ensured fixed-length values** (so that no padding occurs).

*   For 255 or more characters :sql:`VARCHAR` or :sql:`TEXT` must be used.

**More hints for fixed-length** :sql:`CHAR` **columns**

*   Ensure to write fixed-length values for :sql:`CHAR` (non-space characters),
    for example use hash algorithms which produce fixed-length hash identifier
    values.

*   Ensure to use query statements to `trim` OR `rightPad` the value within
    :sql:`WHERE`, :sql:`HAVING` or :sql:`SELECT` operations, when values are
    not guaranteed to contain fixed-length values.

    ..  tip::

    Helper :php:`\TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder`
    expressions can be used, for example
    :php-short:`\TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder->trim()` or
    :php-short:`\TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder->rightPad()` to.

*   Usage of :sql:`CHAR` **must** be avoided when using the column with the
    `Extbase ORM`, because fixed-value length cannot be ensured due to the
    lack of using `trim/rightPad` within the ORM generated queries. Only with ensured
    fixed-length values, it is usable with `Extbase ORM`.

*   Cover custom queries extensively with `functional tests` executed against
    all supported database platforms. Code within public extensions **should** ensure to test
    queries and their operations against all officially TYPO3-supported database platforms.


Example of difference in behaviour of fixed-length :sql:`CHAR` types
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

..  code-block:: sql
    :caption: Example ext_tables.sql defining a fixed-length tt_content field

    CREATE TABLE `tt_content` (
        `some_label` CHAR(10) DEFAULT '' NOT NULL,
    );

Now, add some data. One row which fits exactly to 10 characters, and one row that only uses
6 characters:

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

Now see the difference in retrieving these records:

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

Depending on the used database platform, the retrieved rows would contain these strings:

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

but for PostgreSQL

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

or as a `diff` to make this even more visible:

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

    Because of this, retrieved values need to be trimmed OR padded AFTER
    the query results are fetched, to ensure the same retrieved value across all
    supported database systems. Another option is to ensure that the persisted
    data always has a fixed-value length, like by using the aforementioned hashing
    algorithms (making results not human-readable).

To raise the awareness for problems on this topic, using the trimmed value inside
a :sql:`WHERE` condition will match the record, but the returned value will be different
from the value used in the condition:

..  code-block:: php
    :caption: Retrieve with trimmed value
    :emphasize-lines: 14,21,22

    <?php

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


On PostgreSQL, performing a query for a space-padded value will **not** actually
return the expected row:

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

Additional :php-short:`\TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder`
methods can be used to ensure same behaviour on all platforms:

*   :php-short:`\TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder::trim()`
*   :php-short:`\TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder::rightPad()`

Recommendation
==============

:sql:`CHAR` and :sql:`BINARY` fields can be used (for storage or performance adjustments),
but only when composed data and queries take care of database-system differences.

Otherwise, the "safe bet" is to consistently utilize :sql:`VARCHAR` and :sql:`VARBINARY`
columns types.

..  index:: Database, ext:core
