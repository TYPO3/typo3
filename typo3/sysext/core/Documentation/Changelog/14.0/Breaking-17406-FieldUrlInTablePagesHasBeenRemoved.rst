..  include:: /Includes.rst.txt

..  _breaking-17406-1762953718:

================================================================
Breaking: #17406 - Field "url" in table "pages" has been removed
================================================================

See :issue:`17406`

Description
===========

The former page type "External link" has been renamed to "Link" and now fully
supports all typolink capabilities. It now uses the field :sql:`link`.

Since the field `url` was only used by this former page type, it has been
removed.

Impact
======

Custom code that expects the field  `url` to exist in the table :sql:`pages`
will fail as the field is not found anymore.

Affected installations
======================

*   TYPO3 projects that used the former page type *External URL* to link to
    resources other than true external URLs, for example sections such as `#abc`.
*   TYPO3 projects with custom code that expects the field `url` to exist
    in the table :sql:`pages`.

Migration
=========

The upgrade wizard **"Migrate links of pages of type link"** automatically migrates
pages of the former type *External URL* to the new page type *Link*.

It migrates all links that resolve to external URLs. If your project used the
*External URL* field for other purposes — for example, to link sections such
as `#abc` — you must migrate those links manually.

If your project contains custom code that expects the field :sql:`url` to exist
in the table :sql:`pages`, you can reintroduce this field via a TCA override,
for example:

..  code-block::
    :caption: EXT:my_extension/Configuration/TCA/Overrides/pages.php

    <?php

    defined('TYPO3') || die('Access restricted.');

    $GLOBALS['TCA']['pages']['columns']['url'] = [
        'label' => 'External URL',
        'config' => [
            'type' => 'input',
            'size' => 50,
            'max' => 255,
            'required' => true,
            'eval' => 'trim',
            'softref' => 'url',
            'behaviour' => [
                'allowLanguageSynchronization' => true,
            ],
        ],
    ];

    // Adding column to a existing or new palette and configure showitem,
    // for a specific doktype the field is still required, for example:
    $GLOBALS['TCA']['pages']['palettes]['custom_url'] = [
        'showitem' => 'url',
    ];
    $GLOBALS['TCA']['pages']['types'][$customDokTypeValue] = [
        'showitem' => [
            --div--;core.form.tabs:general,
                doktype,
                --palette--;;title,
                --palette--;;custom_url,
        ],
    ];

Adding it back at least ensures that the database field is not renamed and
dropped by the Database Analyzer. In case TCA is not required while keeping
the database field it could be added to a extension `ext_tables.sql` file.

..  index:: TCA, NotScanned, ext:core
