.. include:: /Includes.rst.txt

.. _feature-97159:

=====================================
Feature: #97159 - New TCA type "link"
=====================================

See :issue:`97159`

Description
===========

Especially TCA type :php:`input` has a wide range of use cases, depending
on the configured :php:`renderType` and the :php:`eval` options. Determination
of the semantic meaning is therefore usually quite hard and often leads to
duplicated checks and evaluations in custom extension code.

In our effort of introducing dedicated TCA types for all those use
cases, the TCA type :php:`link` has been introduced. It replaces the
:php:`renderType=inputLink` of TCA type :php:`input`.

The TCA type :php:`link` features the following column configuration:

-   :php:`allowedTypes`
-   :php:`appearance`: :php:`enableBrowser`, :php:`browserTitle`,
    :php:`allowedOptions`, :php:`allowedFileExtensions`
-   :php:`autocomplete`
-   :php:`behaviour`: :php:`allowLanguageSynchronization`
-   :php:`default`
-   :php:`fieldControl`
-   :php:`fieldInformation`
-   :php:`fieldWizard`
-   :php:`mode`
-   :php:`nullable`
-   :php:`placeholder`
-   :php:`readOnly`
-   :php:`required`
-   :php:`search`
-   :php:`size`
-   :php:`valuePicker`

..  note::
    The soft reference definition :php:`softref=typolink` is automatically applied
    to all TCA type :php:`link` columns.

..  note::
    The value of TCA type :php:`link` columns is automatically trimmed before
    being stored in the database. Therefore, the :php:`eval=trim` option is no
    longer needed and should be removed from the TCA configuration.

The following column configurations can be overwritten by page TSconfig:

*   :typoscript:`readOnly`
*   :typoscript:`size`

The previously configured :php:`linkPopup` field control is now integrated
into the new TCA type directly. Additionally, instead of exclude lists
(:php:`[blindLink[Fields|Options]`) the new type now use include lists.
Those lists are furthermore no longer comma-separated, but PHP arrays,
with each option as a separate value.

The replacement for the previously used :php:`blindLinkOptions` option is the
:php:`allowedTypes` configuration. The :php:`blindLinkFields` option is
now configured via :php:`appearance[allowedOptions]`. While latter only
affects the display in the Link Browser, the :php:`allowedTypes` configuration
is also evaluated in the :php:`DataHandler`, preventing the user from adding
links of non-allowed types.

To allow all link types, skip the :php:`allowedTypes` configuration or set
it to :php:`['*']`. It's not possible to deny all types.

To allow all options in the Link Browser, skip the
:php:`appearance[allowedOptions]` configuration or set it to :php:`['*']`. To
deny all options in the Link Browser, set the :php:`appearance[allowedOptions]`
configuration to :php:`[]` (empty :php:`array`).

The :php:`allowedExtensions` option is renamed to :php:`allowedFileExtensions`
and also moved to :php:`appearance`. Now it requires to be an :php:`array`.
To allow all extensions, skip the :php:`appearance[allowedFileExtensions]`
configuration or set it to :php:`['*']`. It's not possible to deny all
extensions.

With :php:`appearance[browserTitle]`, a custom title for the Link Browser
can be defined. To disable the Link Browser, :php:`appearance[enableBrowser]`
has to be set to :php:`false`.

A complete migration from :php:`renderType=inputLink` to :php:`type=link`
looks like the following:

..  code-block:: php

    // Before

    'a_link_field' => [
        'label' => 'Link',
        'config' => [
            'type' => 'input',
            'renderType' => 'inputLink',
            'required' => true,
            'nullable' => true,
            'size' => 20,
            'max' => 1024,
            'eval' => 'trim',
            'fieldControl' => [
                'linkPopup' => [
                    'disabled' => true,
                    'options' => [
                        'title' => 'Browser title',
                        'allowedExtensions' => 'jpg,png',
                        'blindLinkFields' => 'class,target,title',
                        'blindLinkOptions' => 'mail,folder,file,telephone',
                    ],
                ],
            ],
            'softref' => 'typolink',
        ],
    ],

   // After

    'a_link_field' => [
        'label' => 'Link',
        'config' => [
            'type' => 'link',
            'required' => true,
            'nullable' => true,
            'size' => 20,
            'allowedTypes' => ['page', 'url', 'record'],
            'appearance' => [
                'enableBrowser' => false,
                'browserTitle' => 'Browser title',
                'allowedFileExtensions' => ['jpg', 'png'],
                'allowedOptions' => ['params', 'rel'],
            ],
        ]
    ]

An automatic TCA migration is performed on the fly, migrating all occurrences
to the new TCA type and triggering a PHP :php:`E_USER_DEPRECATED` error
where code adoption has to take place.

.. note::

    The corresponding FormEngine class has been renamed from :php:`InputLinkElement`
    to :php:`LinkElement`. An entry in the "ClassAliasMap" has been added for
    extensions calling this class directly, which is rather unlikely. The
    extension scanner will report any usage, which should then be migrated.

Allowed type "record"
=====================

One of the primary tasks of the corresponding TCA migration is to migrate
the exclude lists to include lists. To achieve this, the migration would need
to know all possible values. Since the LinkHandler API provides the possibility
to use the :php:`RecordLinkHandler` as basis for various custom record types,
whose availability however depends on the page context, it's not possible for
the migration to add the custom record identifiers correctly. Therefore, the
:php:`record` type is added to the :php:`allowedTypes`, enabling all custom
record identifiers. The actually available identifiers are then resolved
automatically in the :php:`link` element, depending on the context.

To limit this in TCA already, replace the :php:`record` value with the
desired record identifiers.

..  code-block:: php

    // Before
    'allowedTypes' => ['page', 'url', 'record'],

    // After
    'allowedTypes' => ['page', 'url', 'tx_news', 'tt_address'],

Impact
======

It's now possible to simplify the TCA configuration by using the new
dedicated TCA type :php:`link`.

.. index:: Backend, TCA, ext:backend
