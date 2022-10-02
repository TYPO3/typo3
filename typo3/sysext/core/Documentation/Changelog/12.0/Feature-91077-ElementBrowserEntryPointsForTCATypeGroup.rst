.. include:: /Includes.rst.txt

.. _feature-91077:

=================================================================================
Feature: #91077 - Element browser entry points for TCA types "group" and "folder"
=================================================================================

See :issue:`91077`

Description
===========

The TCA types "group" and "folder" allow an editor to create references
to folders or records from multiple tables in the system. By default,
an editor can select those records by either using the suggest wizard
or with the element browser. The latter displays the page or folder
tree (depending on the fields' configuration). The editor can then
select a page or folder to select records / folders from.

By default, the last selected page / folder is used when opening
the element browser. However, there are usually "storage pages"
in each system, which contain records of one specific type, e.g. a
storage for news records. Therefore, the editor always has to search
for this particular page, which might take some time, especially in
systems with large page trees.

This situation has now been improved by introducing a new TCA field
configuration `elementBrowserEntryPoints` for the TCA types "group" and "folder".
It's a PHP :php:`array`, containing `table => id` pairs. When
opening the element browser for a specific table (buttons below the
group field), the defined page or folder is then always selected by
default. There is also the special `_default` key, used for the
general element browser button (on the right side of the group field),
which is not dedicated to a specific table.

Making this even more useful, the new configuration also supports the known
markers `###SITEROOT###`, `###CURRENT_PID###` and `###PAGE_TSCONFIG_<key>###`.
Additionally, the configuration is also added to FormEngine's "allowOverrideMatrix".
This means, each `table => id` pair can be overridden via page TSconfig.

Let's see a simple example for a group field with one allowed table:

..  code-block:: php

    'simple_group' => [
        'label' => 'Simple group field',
        'config' => [
            'type' => 'group',
            'allowed' => 'tt_content',
            'elementBrowserEntryPoints' => [
                'tt_content' => 123,
            ]
        ]
    ],

This could then be overridden via page TSconfig:

..  code-block:: typoscript

    TCEFORM.my_table.simple_group.config.elementBrowserEntryPoints.tt_content = 321

Since only one table is allowed, the defined entry point is also automatically
used for the general element browser button. In case the group field allows
more than one table the `_default` key has to be set:

..  code-block:: php

    'extended_group' => [
        'label' => 'Extended group field',
        'config' => [
            'type' => 'group',
            'allowed' => 'tt_content,tx_news_domain_model_news',
            'elementBrowserEntryPoints' => [
                '_default' => '###CURRENT_PID###' // E.g. use a special marker
                'tt_content' => 123,
                'tx_news_domain_model_news' => 124,
            ]
        ]
    ],

Of course, the `_default` key can also be overridden via page TSconfig:

..  code-block:: typoscript

    TCEFORM.my_table.extended_group.config.elementBrowserEntryPoints._default = 122

For TCA type "folder" one can also define an entry point with the `_default` key:

..  code-block:: php

    'folder_group' => [
        'label' => 'Folder group field',
        'config' => [
            'type' => 'folder',
            'elementBrowserEntryPoints' => [
                '_default' => '1:/styleguide/'
            ]
        ]
    ],

It's also possible to use a special TSconfig key:

..  code-block:: php

    'folder_group' => [
        'label' => 'Folder group field',
        'config' => [
            'type' => 'folder',
            'elementBrowserEntryPoints' => [
                '_default' => '###PAGE_TSCONFIG_ID###'
            ]
        ]
    ],

This key has then to be defined on field level:

..  code-block:: typoscript

    TCEFORM.my_table.folder_group.PAGE_TSCONFIG_ID = 1:/styleguide/subfolder

In case an allowed table has no entry point defined, the `_default` is used.
In case `_default` is also not set or `elementBrowserEntryPoints` is not
used at all, the previous behaviour applies.

Impact
======

Editors workflow for selecting records or folders in TCA types "group" and "folder" fields
can now be improved by defining default entry points for tables and folders.

.. index:: TCA, ext:backend
