.. include:: /Includes.rst.txt

.. _important-104153-1718790066:

==============================================================
Important: #104153 - About database error "Row size too large"
==============================================================

See :issue:`104153`

Description
===========

Introduction
------------

MySQL and MariaDB database engines sometimes generate a "Row size too large" error
when modifying the schema of tables with numerous columns. This document aims to
provide a detailed explanation of this error and presents solutions for TYPO3
instance maintainers to address it.

Note that TYPO3 Core v13 has implemented measures to mitigate this error in
most scenarios. Therefore, instance maintainers typically do not need to
address the specific details outlined below.


Preface
-------

First, it is important to recognize that there are two different error messages
that appear similar but have distinct root causes and potentially opposite solution
strategies. This will be elaborated on later in this document.

Secondly, we will not cover all possible variations of these errors, but will focus
on a subset most relevant to TYPO3. Therefore, later sections of the document assume
specific details. Correctly addressing these details may already resolve the issue
for instances running with a different setup.

The issue is most likely to occur with the database table :sql:`tt_content`, as this
table is often extended with many additional columns, increasing the likelihood of
encountering the error. This document uses table :sql:`tt_content` in code examples.
However, the diagnosis and solution strategies are applicable to other tables as well and
code examples may need corresponding adjustments.

Ensure storage engine is 'InnoDB'
.................................

TYPO3 typically utilizes the :sql:`InnoDB` storage engine for tables in MySQL / MariaDB
databases. However, instances upgraded from older TYPO3 Core versions might still
employ different storage engines for some tables. While the TYPO3 Core plans to
automatically detect and transition these to :sql:`InnoDB` in the future, it is advisable
for maintainers to manually verify the storage engine currently in use:

..  code-block:: sql

    SELECT `TABLE_NAME`,`ENGINE`
    FROM `information_schema`.`TABLES`
    WHERE `TABLE_SCHEMA`='my_database'
    AND `TABLE_NAME`='tt_content';

Tables *not* using :sql:`InnoDB` should be converted to :sql:`InnoDB`:

..  code-block:: sql

    USE `my_database`;
    ALTER TABLE `tt_content` ENGINE=InnoDB;

Ensure InnoDB row format is 'Dynamic'
.....................................

The :sql:`InnoDB` row format dictates how data is physically stored. The :sql:`Dynamic` row
format provides better support for tables with many variable-length columns and
has been the default format for some time. However, instances upgraded from
older TYPO3 Core versions and older MySQL / MariaDB engines might still use the
previous default format :sql:`Compact`. While the TYPO3 Core intends to automatically
detect and transition such tables to the :sql:`Dynamic` row format in the future, it
is recommended that maintainers manually verify the format currently in use:

..  code-block:: sql

    SELECT `TABLE_NAME`,`Row_format`
    FROM `information_schema`.`TABLES`
    WHERE `TABLE_SCHEMA`='my_database'
    AND `TABLE_NAME`='tt_content';

Tables *not* using 'Dynamic' should be converted:

..  code-block:: sql

    USE 'my_database`;
    ALTER TABLE `tt_content` ROW_FORMAT=DYNAMIC;

Database, table and column charset
..................................

The selected column charset impacts length calculations. This document assumes
:sql:`utf8mb4` for columns, which aligns with the default TYPO3 setup. Converting
an existing instance to :sql:`utf8mb4` can be a complex task depending on the
currently used charset and is beyond the scope of this document.

The key point regarding :sql:`utf8mb4` is this: When dealing with the :sql:`utf8mb4`
charset for :sql:`VARCHAR()` columns, storage and index calculations need to be
multiplied by four (4). For example, a :sql:`VARCHAR(20)` can take up to eighty
(80) *bytes* since each of the twenty (20) *characters* can use up to four (4)
*bytes*. In contrast, a :sql:`VARCHAR(20)` in a :sql:`latin1` column will consume
only twenty (20) *bytes*, as each *character* is only one byte long.

The TYPO3 Core may set individual columns to a charset like :sql:`latin1` in the
future to optimize storage needs for columns that store only ASCII characters,
but most content-related columns should usually be :sql:`utf8mb4` to avoid issues
with multi-byte characters.

Note that column types that do not store characters (like :sql:`INT`) do not have
a charset set at all. An overview of current charsets can be retrieved:

..  code-block:: sql

    # Default charset of the database, new tables use this charset when no
    # explicit charset is given with a "CREATE TABLE" statement:
    SELECT `SCHEMA_NAME`, `DEFAULT_CHARACTER_SET_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`
    WHERE `SCHEMA_NAME`='my_database';

    # Default charset of a table, new columns use this charset when no
    # explicit charset is given with a "ALTER TABLE" statement:
    SELECT `table`.`table_name`,`charset`.`character_set_name`
    FROM `information_schema`.`TABLES` AS `table`,`information_schema`.`COLLATION_CHARACTER_SET_APPLICABILITY` AS `charset`
    WHERE `charset`.`collation_name`=`table`.`table_collation`
    AND `table`.`table_schema`='my_database'
    AND `table`.`table_name`='tt_content';

    # List table columns, their column types with length and selected charsets:
    SELECT `column_name`,`column_type`,`character_set_name`
    FROM `information_schema`.`COLUMNS`
    WHERE `table_schema`='my_database'
    AND `table_name`='tt_content';

Ensure innodb_page_size is 16384
................................

Few instances modify the MySQL / MariaDB :sql:`innodb_page_size` system variable,
and it is advisable to keep it at the default value of :sql:`16384`. Verify the
current value:

..  code-block:: sql

    SHOW variables WHERE `Variable_name`='innodb_page_size';


Row size too large
------------------

This document now assumes MySQL / MariaDB, the table in question uses the :sql:`InnoDB`
storage engine with row format :sql:`Dynamic`, a system maintainer is aware of
specific column charsets, and :sql:`innodb_page_size` default :sql:`16384` is kept.


Error "Row size too large 65535"
--------------------------------

..  code-block:: plaintext

    ERROR 1118 (42000): Row size too large. The maximum row size for the used table type,
    not counting BLOBs, is 65535. This includes storage overhead, check the manual. You
    have to change some columns to TEXT or BLOBs

Explanation
...........

When altering the database schema of a table, such as adding or increasing the
size of a :sql:`VARCHAR` column, the above error might be encountered.

Note the statement: "The maximum row size [...] is 65535".

MySQL / MariaDB impose a global maximum size per table row of 65kB. The combined
length of all column types contribute to this limit, except for :sql:`TEXT` and
:sql:`BLOB` types, which are stored "off row" where only a "pointer" to the actual
storage location counts.

However, standard :sql:`VARCHAR` fields contribute their full maximum byte length
towards this 65kB limit. For instance, a :sql:`VARCHAR(2048)` column with the
:sql:`utf8mb4` character set (4 bytes per character) requires 4 * 2048 = 8192 bytes.
Therefore, only 65535 - 8192 = 57343 bytes remain available for the storage needs
of all other table columns.

As another example, consider the query below attempting to create a table with
a :sql:`VARCHAR(16383)` column alongside an :sql:`INT` column:

..  code-block:: sql

    # ERROR 1118 (42000): Row size too large. The maximum row size [...] is 65535
    CREATE TABLE test (c1 varchar(16383), c2 int) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

Let's break down the calculation:

..  code-block:: plaintext

    varchar 16383 characters = 16383 * 4 bytes = 65532 bytes
    int = 4 bytes
    Total: 65532 + 4 = 65536 bytes

This exceeds the maximum limit by one byte, causing the query to fail.

Mitigation
..........

The primary strategy to mitigate the 65kB limit is to minimize the use of
lengthy :sql:`VARCHAR` columns.

For instance, in the :sql:`tt_content` table of a default Core instance, there
are approximately a dozen :sql:`VARCHAR(255)` columns, totaling about 12kB,
alongside smaller :sql:`INT` and similar fields. This allocation leaves ample
room for additional custom :sql:`VARCHAR()` columns.

TYPO3 v13 introduced improvements in two key areas:

Firstly, TCA fields with :php:`type='link'` and :php:`type='slug'` have been
converted from :sql:`VARCHAR(2048)` (requiring 8kB of row space) to :sql:`TEXT`.
The :sql:`tt_content` table was affected by this change with at least one
column (:sql:`header_link`). This adjustment provides more space by default for
custom columns.

Additionally, the TYPO3 Core now defaults to using :sql:`TEXT` instead of
:sql:`VARCHAR()` for TCA fields with :php:`type='input'` when the TCA property
:php:`max` is set to a value greater than :php:`255` and extension authors utilize
the :ref:`column auto creation feature <feature-101553-1691166389>`.

Instances encountering the 65kB limit can consider adjusting fields with these
considerations in mind:

* Priority should be given to reconsidering long :sql:`VARCHAR()` columns first.
  Changing a single :sql:`utf8mb4` :sql:`VARCHAR(2048)` column to :sql:`TEXT`
  can free enough space for up to eight (8) :sql:`utf8mb4` :sql:`VARCHAR(255)`
  columns.

* Consider reducing the length of :sql:`VARCHAR()` columns. For instance, columns
  containing database table or column names can be limited to :sql:`VARCHAR(64)`,
  as MySQL / MariaDB restricts table and column names to a maximum of 64 characters.
  Similar considerations apply to "short" content fields, such as a column storing
  an author's name or similar potentially limited length information.

  However, be cautious as setting :sql:`VARCHAR()` columns to "too short" lengths
  may impose a different limit, as discussed below.

* Consider removing entries from :file:`ext_tables.sql` with TYPO3 Core v13: The
  :ref:`column auto creation feature <feature-101553-1691166389>` generally provides
  better-defined column definitions and ensures columns stay synchronized with TCA
  definitions automatically. The TYPO3 Core aims to provide sensible default
  definitions, often superior to a potentially imprecise definition by extension
  authors.

* Note that individual column definitions in :file:`ext_tables.sql` always override
  TYPO3 Core v13's column auto creation feature: In rare cases where TYPO3 Core makes
  unfavorable decisions, extension authors can always override these details.

* Note :sql:`utf8mb4` :sql:`VARCHAR(255)` and :sql:`TINYTEXT` are *not* the same:
  A :sql:`VARCHAR(255)` size limit is 255 *characters*, while a :sql:`TINYTEXT`
  is 255 *bytes*. The proper substitution for a (4 bytes per character) :sql:`utf8mb4`
  :sql:`VARCHAR(255)` field is :sql:`TEXT`, which allows for 65535 bytes.

* :sql:`TEXT` *may* negatively impact performance as it forces additional
  Input/Output operations in the database. This is typically not a significant issue
  with standard TYPO3 queries, as various other operations in TYPO3 have a greater
  impact on overall performance. However, indiscriminately changing all fields from
  :sql:`VARCHAR()` to :sql:`TEXT` or similar is *not* advisable.

* Be mindful of indices: When :sql:`VARCHAR()` columns that are part of an index
  are changed to :sql:`TEXT` or similar, these indexes may require adjustment.
  Ensure they are properly restricted in length to avoid a "Specified key was too long"
  error. The :sql:`InnoDB` key length limit with row format :sql:`Dynamic` is 3072
  *bytes* (not *characters*). In general, indexes on :sql:`VARCHAR()` and all other
  "longish" columns should be set with care and only if really needed since long
  indexes can negatively impact database performance as well, especially when a
  table has many write operations in production.


Error "Row size too large (> 8126)"
-----------------------------------

..  code-block:: plaintext

    ERROR 1118 (42000): Row size too large (> 8126). Changing some columns to TEXT
    or BLOB may help. In current row format, BLOB prefix of 0 bytes is stored inline.

Sometimes also an error similar to this in MySQL / MariaDB logs:

..  code-block:: plaintext

    [Warning] InnoDB: Cannot add field col1 in table db1.tab because after adding it,
    the row size is 8478 which is greater than maximum allowed size (8126) for a record
    on index leaf page.

Explanation
...........

This error may occur when adding or updating table rows, not only when altering table
schema.

Note the statement: "Row size too large (> 8126)". This differs from the
previous error message. This error is *not* about a general row size limit of
65535 bytes, but rather a limit imposed by InnoDB tables.

The root cause is that InnoDB has a maximum row size equivalent to half of the
:sql:`innodb_page_size` system variable value of 16384 bytes, which is 8192 bytes.

InnoDB mitigates this by storing certain variable-length columns on "overflow pages".
The decision regarding which columns are *actually* stored on overflow pages is made
dynamically when adding or changing rows. This is why the error can be raised at
runtime and not only when altering the schema. Additionally, it makes accurately
predicting whether the error will occur challenging. Furthermore, not all variable-length
columns *can* be stored on overflow pages. This is why the error can be raised when
altering table schema.

Variable-length columns of type :sql:`TEXT` and :sql:`BLOB` can always be stored on
overflow pages, thus minimally impacting the main data page limit of 8192 bytes.
However, :sql:`VARCHAR` columns can only be stored on overflow pages if their maximum
length exceeds 255 *bytes*. Therefore, an unexpected solution to the "Row size too
large 8192" error in many cases is to increase the length of some variable-length
columns, enabling InnoDB to store them on overflow pages.

Mitigation
..........

TYPO3 Core v13 modified several default columns to mitigate the issue for instances
with many custom columns. The TYPO3 Core maintainers expect this issue to occur
infrequently in practice.

Instances encountering the 8192 bytes limit can consider adjusting fields with these
considerations in mind:

* The calculation determining if a column can be stored on overflow pages is based
  on a minimum of 256 *bytes*, not *characters*. A typical :sql:`utf8mb4`
  :sql:`VARCHAR(255)` equates to 1020 bytes, which *can* be stored on overflow pages.
  Changing such fields makes no difference.

* Changing a :sql:`utf8mb4` :sql:`VARCHAR(63)` (or smaller) to :sql:`VARCHAR(64)`
  (64 characters utf8mb4 = 256 bytes) allows storing this column on overflow
  pages and *does* make a difference.

* Changing a :sql:`utf8mb4` :sql:`VARCHAR(63)` (or smaller) to :sql:`TINYTEXT` should
  allow storing this column on overflow pages as well. However, this may not be the
  optimal solution due to potential performance penalties, as discussed earlier in
  this chapter. Similarly, indiscriminately increasing the length of multiple
  variable-length columns is not advisable. Columns should ideally be kept as small
  as possible, only exceeding the 255-byte limit or converting to :sql:`TEXT` types
  if absolutely necessary. Also refer to the note on indexes above when single
  columns are part of indexes.

* Columns using :sql:`utf8mb4` that are smaller or equal to :sql:`VARCHAR(63)` and
  only store ASCII characters can be downsized by changing the charset to :sql:`latin1`.
  For instance, a :sql:`VARCHAR(60)` column occupies 4 * 60 = 240 bytes in row size,
  but only 60 bytes when using the :sql:`latin1` charset. Currently, TYPO3 Core does
  not interpret charset definitions for individual columns from :sql:`ext_tables.sql`.
  The Core Team anticipates implementing this feature in the future.

* Note that increasing the length of :sql:`VARCHAR` columns can potentially conflict
  with the 65kB limit mentioned earlier. This is another reason to avoid indiscriminately
  increasing the length of variable-length columns.


Further read
------------

This document is based on information from database vendors and other sites
found online. The following links may provide further insights:

* `(MariaDB) InnoDB Row Formats Overview <https://mariadb.com/kb/en/innodb-row-formats-overview/>`_
* `(MariaDB) Troubleshooting Row Size Too Large Errors with InnoDB <https://mariadb.com/kb/en/troubleshooting-row-size-too-large-errors-with-innodb/>`_
* `(Contao) MySQL Row size too large <https://github.com/contao/contao/issues/4159>`_


Final words
-----------

Navigating the two limits in MySQL / MariaDB requires a deep understanding of
database engine internals to manage effectively. The TYPO3 Core Team is confident
that version 13 has effectively mitigated the issue, ensuring that typical instances
will rarely encounter it. We trust this document remains helpful and welcome any
feedback in case something crucial has been overlooked.


.. index:: Database, ext:core
