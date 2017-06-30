.. include:: ../../Includes.txt

==================================================
Important: #81751 - DBAL compatible quoting in TCA
==================================================

See :issue:`81751`

Description
===========

Names of tables and columns used in SQL fragments of :php:`TCA` definitions need proper quoting to be compatible with different database drivers. The database
framework of the core now applies proper quoting to table and column names if they are wrapped as :php:`{#tableName}.{#columnName}`

It is advised to adapt extensions accordingly to run successfully on databases like PostgreSQL.

Example for a :php:`TCA` definition snippet:

.. code-block:: php

    'columns' => [
        'aField' => [
            'config' => [
                'foreign_table' => 'tt_content',
                'foreign_table_where' => 'AND {#tt_content}.{#CType} IN (\'text\',\'textpic\',\'textmedia\') ORDER BY {#tt_content}.{#CType} ASC',
                ...
            ],
        ],
        ...
    ],

    'columns' => [
        'aField' => [
            'config' => [
                'type' => 'text',
                'search' => [
                    'andWhere' => '{#CType}=\'text\' OR {#CType}=\'textpic\' OR {#CType}=\'textmedia\''
                ],
                ...
            ],
        ],
        ...
    ],

.. index:: Database, Backend, TCA
