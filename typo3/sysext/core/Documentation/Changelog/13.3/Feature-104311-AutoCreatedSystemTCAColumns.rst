.. include:: /Includes.rst.txt

.. _feature-104311-1720176189:

==================================================
Feature: #104311 - Auto created system TCA columns
==================================================

See :issue:`104311`

Description
===========

Introduction
------------

There are various :php:`TCA` table :php:`ctrl` settings that define fields used
to enable certain TYPO3 table capabilities and to specify the database column
to store this row state.

An example is :php:`['ctrl']['enablecolumns']['starttime'] = 'starttime';`, which
makes the table "starttime aware", resulting in the automatic exclusion of a record
if the given starttime is in the future, when rendered in the frontend.

Such :php:`ctrl` settings require TCA :php:`columns` definitions. Default definitions
of such :php:`columns` are now automatically added to :php:`TCA` if not manually
configured. Extension developers can now remove and avoid a significant amount
of boilerplate field definitions in :php:`columns` and rely on TYPO3 core to create
them automatically. Note the core does *not* automatically add such columns to TCA
:php:`types` or :php:`palettes` definitions: Developers still need to place them,
to show the columns when editing record rows, and need to add according access
permissions.

Let's have a quick look on what happened within TCA and its surrounding code lately,
to see how this feature embeds within the general TYPO3 core strategy in this area
and why the above feature has been implemented at this point in time:

TCA has always been a central cornerstone of TYPO3. The core strives to maintain
this central part while simplifying and streamlining less desirable details.

Core version v12 aimed to simplify single column definitions by implementing new
column types like :php:`file`, :php:`category`, :php:`email`, and more. These are
much easier to understand and require far fewer single property definitions than
previous solutions. With this in place, auto-creation of database column
definitions derived from TCA has been established with TYPO3 core v13, making the
manual definition of database table schemas in :file:`ext_tables.sql` largely
unnecessary. Additionally, an object-oriented approach called :php:`TcaSchema` has
been introduced to harmonize and simplify information retrieval from TCA.

With the step described in this document - the auto-creation of TCA columns from
:php:`ctrl` properties - the amount of manual boilerplate definitions is
significantly reduced, and the core gains more control over these columns to
harmonize these fields throughout the system. Note that the TYPO3 core has not yet
altered the structure of TCA :php:`types` and :php:`palettes`. This will be one of
the next steps in this area, but details have not been decided upon yet.

All these steps streamline TCA and its surrounding areas, simplify the system,
and reduce the amount of details developers need to be aware of when defining
their own tables and fields.

This document details the "column auto-creation from 'ctrl' fields" feature: It
first lists all affected settings with their derived default definitions. It
concludes with a section relevant for instances that still need to override
certain defaults of these columns by explaining the order of files and classes
involved in building TCA and the available options to change defaults and where
to place these changes.

Auto-created columns from 'ctrl'
--------------------------------

The configuration settings below enable single table capabilities. Their values
are a database column name responsible for storing the row data of the capability.

If a setting is defined in a "base" TCA table file (:file:`Configuration/TCA`, not
in :file:`Configuration/TCA/Overrides`), the core will add default :php:`columns`
definition for this field name if no definition exists in a base file.

:php:`['ctrl']['enablecolumns']['disabled']`
............................................

This setting makes database table rows "disable aware": A row with this flag
being set to 1 is not rendered in the frontend to casual website users.

Typical usage:

.. code-block:: php

    'ctrl' => [
        'enablecolumns' => [
            'disabled' => 'disabled',
        ],
    ],

Default configuration added by the core:

.. code-block:: php

    'disabled' => [
        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
        'exclude' => true,
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 0,
            'items' => [
                [
                    'label' => '',
                    'invertStateDisplay' => true,
                ],
            ],
        ],
    ],

:php:`['ctrl']['enablecolumns']['starttime']`
.............................................

This setting makes database table rows "starttime aware": A row having a start
time in the future is not rendered in the frontend.

Typical usage:

.. code-block:: php

    'ctrl' => [
        'enablecolumns' => [
            'starttime' => 'starttime',
        ],
    ],

Default configuration added by the core:

.. code-block:: php

    'starttime' => [
        'exclude' => true,
        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
        'config' => [
            'type' => 'datetime',
            'default' => 0,
        ],
    ],

:php:`['ctrl']['enablecolumns']['endtime']`
...........................................

This setting makes database table rows "endtime aware": A row having an end
time in the past is not rendered in the frontend.

Typical usage:

.. code-block:: php

    'ctrl' => [
        'enablecolumns' => [
            'endtime' => 'endtime',
        ],
    ],

Default configuration added by the core:

.. code-block:: php

    'endtime' => [
        'exclude' => true,
        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
        'config' => [
            'type' => 'datetime',
            'default' => 0,
            'range' => [
                'upper' => mktime(0, 0, 0, 1, 1, 2106),
            ],
        ],
    ],

:php:`['ctrl']['enablecolumns']['fe_group']`
............................................

This setting makes database table rows "frontend group aware": A row can be defined
to be shown only to frontend users who are a member of selected groups.

Typical usage:

.. code-block:: php

    'ctrl' => [
        'enablecolumns' => [
            'fe_group' => 'fe_group',
        ],
    ],

Default configuration added by the core:

.. code-block:: php

    'fe_group' => [
        'exclude' => true,
        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.fe_group',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectMultipleSideBySide',
            'size' => 5,
            'maxitems' => 20,
            'items' => [
                [
                    'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hide_at_login',
                    'value' => -1,
                ],
                [
                    'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.any_login',
                    'value' => -2,
                ],
                [
                    'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.usergroups',
                    'value' => '--div--',
                ],
            ],
            'exclusiveKeys' => '-1,-2',
            'foreign_table' => 'fe_groups',
        ],
    ],

:php:`['ctrl']['editlock']`
...........................

This setting makes database table rows "backend lock aware": A row with this
being flag enabled can only be edited by backend administrators.

Typical usage:

.. code-block:: php

    'ctrl' => [
        'editlock' => 'editlock',
    ],

Default configuration added by the core:

.. code-block:: php

    'endtime' => [
        'displayCond' => 'HIDE_FOR_NON_ADMINS',
        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:editlock',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
        ],
    ],

:php:`['ctrl']['descriptionColumn']`
....................................

This setting makes database table rows "description aware": Backend editors
have a database field to add row specific notes.

Typical usage:

.. code-block:: php

    'ctrl' => [
        'descriptionColumn' => 'description',
    ],

Default configuration added by the core:

.. code-block:: php

    'description' => [
        'exclude' => true,
        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.description',
        'config' => [
            'type' => 'text',
            'rows' => 5,
            'cols' => 30,
            'max' => 2000,
        ],
    ],

:php:`['ctrl']['languageField']` and :php:`['ctrl']['transOrigPointerField']`
.............................................................................

These setting make database table rows "localization aware": Backend editors
can create localized versions of a record. Note when :php:`languageField` is
set, and :php:`transOrigPointerField` is not, the core will automatically set
:php:`transOrigPointerField` to :php:`l10n_parent` since both fields must be
always set in combination.

Typical usage:

.. code-block:: php

    'ctrl' => [
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
    ],

Default configuration added by the core, note string :php:`$table` corresponds
to the current table name.

.. code-block:: php

    'sys_language_uid' => [
        'exclude' => true,
        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
        'config' => [
            'type' => 'language',
        ],
    ],
    'l10n_parent' => [
        'displayCond' => 'FIELD:sys_language_uid:>:0',
        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                [
                    'label' => '',
                    'value' => 0,
                ],
            ],
            'foreign_table' => $table,
            'foreign_table_where' => 'AND {#' . $table . '}.{#pid}=###CURRENT_PID### AND {#' . $table . '}.{#' . $languageFieldName . '} IN (-1,0)',
            'default' => 0,
        ],
    ],

:php:`['ctrl']['transOrigDiffSourceField']`
...........................................

This setting makes database table rows "parent language record change aware": Backend
editors can have an indicator when the parent column has been changed.

Typical usage:

.. code-block:: php

    'ctrl' => [
        'transOrigDiffSourceField' = 'l10n_diffsource',
    ],

Default configuration added by the core:

.. code-block:: php

    'l10n_diffsource' => [
        'config' => [
            'type' => 'passthrough',
            'default' => '',
        ],
    ],

:php:`['ctrl']['translationSource']`
....................................

This setting makes database table rows "parent language source aware" to
determine the difference between "connected mode" and "free mode".

Typical usage:

.. code-block:: php

    'ctrl' => [
        'translationSource' = 'l10n_source',
    ],

Default configuration added by the core:

.. code-block:: php

    'l10n_source' => [
        'config' => [
            'type' => 'passthrough',
            'default' => '',
        ],
    ],

Load order when building TCA
----------------------------

To understand if and when TCA column auto-creation from :php:`ctrl` definitions
kicks in, it is important to have an overview of the order of the single loading
steps:

#. Load single files from extension :file:`Configuration/TCA` files
#. NEW - Enrich :php:`columns` from :php:`ctrl` settings
#. Load single files from extension :file:`Configuration/TCA/Overrides` files
#. Apply TCA migrations
#. Apply TCA preparations

As a result of this strategy, :php:`columns` fields are *not* auto-created, when
a :php:`ctrl` capability is added in a :file:`Configuration/TCA/Overrides`
file, and *not* in a :file:`Configuration/TCA` "base" file. In general, such
capabilities should be set in base files only: Adding them at a later point - for
example in a different extension - is brittle and there is a risk the main
extension can not deal with such an added capability properly.

Overriding definitions from auto-created TCA columns
----------------------------------------------------

I most cases, developers do not need to change definitions of :php:`columns`
auto-created by the core. In general, it is advisable to not actively do this.
Developers who still want to change detail properties of such columns should
generally stick to "display" related details only.

There are two options to have own definitions: When a column is already defined
in a "base" TCA file (:file:`Configuration/TCA`), the core will not override it.
Alternatively, a developer can decide to let the core auto-create a column, to
then override single properties in :file:`Configuration/TCA/Overrides` files.

As example, "base" :php:`pages` file defines this (step 1 above):

.. code-block:: php

    'ctrl' => [
        'enablecolumns' => [
            'disabled' => 'disabled',
        ],
    ],

The core thus creates this :php:`columns` definition (step 2 above):

.. code-block:: php

    'columns' => [
        'disabled' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
            'exclude' => true,
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
                'items' => [
                    [
                        'label' => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
    ],

When an editor creates a new page, it should be "disabled" by default to
avoid having a new page online in the website before it is set up completely.
A :file:`Configuration/TCA/Overrides/pages.php` file does this:

.. code-block:: php

    <?php
    // New pages are disabled by default
    $GLOBALS['TCA']['pages']['columns']['hidden']['config']['default'] = 1;


Impact
======

Extension developers can typically remove :php:`columns` definitions of all the
above fields and rely on TYPO3 core creating them with a good default
definition.

It is only required to define the desired table capabilities in :php:`ctrl` with
its field names, and the system will create the according :php:`columns`
definitions automatically.


.. index:: TCA, ext:core
