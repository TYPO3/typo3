..  include:: /Includes.rst.txt

..  _important-106508-1743692685:

=====================================================================================
Important: #106508 - Respect column `CHARACTER SET` and `COLLATE` in `ext_tables.sql`
=====================================================================================

See :issue:`106508`

Description
===========

TYPO3 now reads column based `CHARACTER SET` and `COLLATION` from extension
:file:`ext_tables.sql` files and applies them on column level. This allows
`CHARACTER SET` and `COLLATION` column settings different than defaults defined
on table or schema level. This is limited to `MySQL` and `MariaDB` DBMS.

..  note::

    Setting different charset and collation comes with some technical impact
    during query time and requires for some queries special handling, for instance
    when joining field that have different charsets or collations. Setting special
    charsets and collations for single columns should only be used in rare
    cases. The support is `@internal` and should be used with care if at all.


For now, :sql:`CHARACTER SET ascii COLLATE ascii_bin` is used for
:sql:`sys_refindex.hash` to reduce required space for the index using
single bytes instead of multiple bytes per character.

The introduced database change is considerable non-breaking, because:

* Not applying the database changes still keeps a fully working state.
* Applying database schema change does not require data migrations.
* Targets only `MySQL` and `MariaDB`.

..  code-block:: sql
    :caption: ext_tables.sql example

    CREATE TABLE some_table (

        col1    CHAR(10) DEFAULT ''             NOT NULL CHARACTER SET ascii COLLATE ascii_bin,
        col2    CHAR(10) CHARACTER SET ascii COLLATE ascii_bin DEFAULT '' NOT NULL,
        col3    VARCHAR(10) DEFAULT ''          NOT NULL CHARACTER SET ascii COLLATE ascii_bin,
        col4    VARCHAR(10) CHARACTER SET ascii COLLATE ascii_bin DEFAULT '' NOT NULL,
        col5    TEXT DEFAULT ''                 NOT NULL CHARACTER SET ascii COLLATE ascii_bin,
        col6    TEXT CHARACTER SET ascii COLLATE ascii_bin DEFAULT '' NOT NULL,
        col7    MEDIUMTEXT DEFAULT ''           NOT NULL CHARACTER SET ascii COLLATE ascii_bin,
        col8    MEDIUMTEXT CHARACTER SET ascii COLLATE ascii_bin DEFAULT '' NOT NULL,
        col9    LONGTEXT DEFAULT ''             NOT NULL CHARACTER SET ascii COLLATE ascii_bin,
        col10   LONGTEXT CHARACTER SET ascii COLLATE ascii_bin DEFAULT '' NOT NULL,
);

..  index:: Database, ext:core
