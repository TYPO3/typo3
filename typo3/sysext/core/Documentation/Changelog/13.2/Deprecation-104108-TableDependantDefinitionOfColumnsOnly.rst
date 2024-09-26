.. include:: /Includes.rst.txt

.. _deprecation-104108-1718354448:

================================================================
Deprecation: #104108 - Table dependant definition of columnsOnly
================================================================

See :issue:`104108`

Description
===========

When linking to the edit form it's possible to instruct the
:php:`EditDocumentController` to only render a subset of available
fields for relevant records using the `columnsOnly` functionality,
by adding the fields to be rendered as a comma-separated list.

However, the edit form can render records from many tables, and not just
a single table, in the same request.

Therefore, the limit fields functionality has been extended to allow
setting the fields to be rendered on a per-table basis. This means that
passing a comma-separated list of fields as a value for `columnsOnly`
has been deprecated.

Impact
======

Passing a comma-separated list of fields as value for `columnsOnly` will
trigger a PHP deprecation warning. A compatibility layer will automatically
set the field list for the required tables.


Affected installations
======================

All installations passing a comma-separated list of fields as a value for
`columnsOnly`.


Migration
=========

The fields to be rendered have to be passed as an :php:`array` under the
corresponding table name.

An example, building such link using the `UriBuilder`:

..  code-block:: php

    $urlParameters = [
        'edit' => [
            'pages' => [
                1 => 'edit',
            ],
        ],
        'columnsOnly' => 'title,slug'
        'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
    ];

    GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('record_edit', $urlParameters);

Above example has to be migrated to:

..  code-block:: php

    $urlParameters = [
        'edit' => [
            'pages' => [
                1 => 'edit',
            ],
        ],
        'columnsOnly' => [
            'pages' => [
                'title',
                'slug'
            ]
        ],
        'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
    ];

    GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('record_edit', $urlParameters);

Additionally, when rendering records from many tables, a configuration
could look like the following:

..  code-block:: php

    $urlParameters = [
        'edit' => [
            'pages' => [
                1 => 'edit',
            ],
            'tt_content' => [
                2 => 'edit',
            ],
        ],
        'columnsOnly' => [
            'pages' => [
                'title',
                'slug'
            ],
            'tt_content' => [
                'header',
                'subheader'
            ]
        ],
        'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
    ];

    // https:://example.com/typo3/record/edit?edit[pages][1]=edit&edit[tt_content][2]=edit&columnsOnly[pages][0]=title&columnsOnly[pages][1]=slug&columnsOnly[tt_content][0]=header&columnsOnly[tt_content][1]=subheader
    $link = GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('record_edit', $urlParameters);

.. index:: Backend, NotScanned, ext:backend
