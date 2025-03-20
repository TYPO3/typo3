..  include:: /Includes.rst.txt

..  _important-106401-1742479303:

============================================================================
Important: #106401 - Treat 0 as a defined value for nullable datetime fields
============================================================================

See :issue:`106401`

Description
===========

For nullable integer-based datetime fields, the value `0` now explicitly
represents the Unix epoch time (`1970-01-01T00:00:00Z`) instead of being
interpreted as an empty value by FormEngine.

Only an explicit `null` database value will be considered an empty value.

The default database schema that is generated from TCA has been adapted
to generate datetime columns with :sql:`DEFAULT NULL` instead of
:sql:`DEFAULT 0` if they have been configured to be nullable.

Given the following TCA definition:


..  code-block:: php

    'columns' => [
        'mydatefield' => [
            'config' => [
                'type' => 'datetime',
                'nullable' => true,
            ],
        ],
    ],


The previously generated SQL statement will be changed from :sql:`DEFAULT 0` to
:sql:`DEFAULT NULL`:


..  code-block:: sql
    :caption: Nullable datetime schema before this change

    `mydatefield` bigint(20) DEFAULT 0


..  code-block:: sql
    :caption: Nullable datetime schema after this change

    `mydatefield` bigint(20) DEFAULT NULL


Fields that have not been explicitly configured to be nullable are unaffected
and will default to `0` as before.


..  index:: Backend, Database, JavaScript, ext:backend
