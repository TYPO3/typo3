.. include:: /Includes.rst.txt

.. _important-102904-1706702424:

============================================================
Important: #102904 - Use TCA group field as foreign selector
============================================================

See :issue:`102904`

Description
===========

When using TCA type :php:`inline`, developers have the possibility to use the
"foreign selector" feature by defining the :php:`foreign_selector` option,
pointing to a field on the foreign (child) table. This way, editors can
use the corresponding selector field to choose existing child records,
to create a new inline relation. This can be further extended, using the
:php:`useCombination` appearance option, which allows to modify the child record
via the parent record globally.

The field referenced in :php:`foreign_selector` is usually a field with TCA type
:php:`select`, using the `foreign_table` option itself to provide the corresponding
items to choose.

It's nevertheless also possible to use a TCA type :php:`group` field as
:php:`foreign_selector`. In this case, the child records have to be selected
from the table, defined via the :php:`allowed` option. For this use case,
**only one table** can be defined. This means, the first table name in
:php:`allowed` is taken, no matter if there are multiple table names defined.

..  note::

    This unfortunately does not work out of the box for Extbase. Therefore, the
    corresponding table has to be defined additionally via the :php:`foreign_table`
    option. This option is only used as a
    `workaround <https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Group/Properties/ForeignTable.html>`__
    by Extbase and is not sufficient for the TYPO3 Form editor, which will always
    just consider the value from the :php:`allowed` option.

Example using an intermediate table and the :php:`useCombination` feature:

..  code-block:: php

    // Inline field in parent table "tx_extension_inline_usecombination"
    'inline' => [
        'label' => 'inline',
        'config' => [
            'type' => 'inline',
            'foreign_table' => 'tx_extension_inline_usecombination_mm',  // Referencing the intermediate table
            'foreign_field' => 'group_parent',
            'foreign_selector' => 'group_child',
            'foreign_unique' => 'group_child',
            'appearance' => [
                'useCombination' => true,
            ],
        ],
    ],

    // Reference fields in intermediate table "tx_extension_inline_usecombination_mm"
    'group_parent' => [
        'label' => 'group parent',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'foreign_table' => 'tx_extension_inline_usecombination', // Referencing the parent table
        ],
    ],
    'group_child' => [
        'label' => 'group child',
        'config' => [
            'type' => 'group',
            'allowed' => 'tx_extension_inline_usecombination_child', // Referencing the child table
            'foreign_table' => 'tx_extension_inline_usecombination_child', // ONLY USED FOR extbase!
        ],
    ],

    // Child table "tx_extension_inline_usecombination_child" does not have any relation fields

.. index:: Backend, PHP-API, TCA, ext:backend
