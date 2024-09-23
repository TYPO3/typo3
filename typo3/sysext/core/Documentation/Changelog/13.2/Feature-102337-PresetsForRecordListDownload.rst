.. include:: /Includes.rst.txt

.. _feature-102337-1712597691:

=======================================================
Feature: #102337 - Presets for download of record lists
=======================================================

See :issue:`102337`

Description
===========

In the :guilabel:`Web > List` backend module, the data of records for each
database table (including pages and content records) can be downloaded.

This export takes the currently selected list of columns into consideration and
alternatively allows all columns to be selected.

A new feature has been introduced adding the ability to pick the exported
data columns from a list of configurable presets.

Those presets can be configured via page TSconfig, and can also be
overridden via user TSconfig (for example, to make certain presets
only available to specific users).

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/page.tsconfig

    mod.web_list.downloadPresets {
        pages {
            10 {
                label = Quick overview
                columns = uid, title, crdate, slug
            }

            20 {
                identifier = LLL:EXT:myext/Resources/Private/Language/locallang.xlf:preset2.label
                label = UID and titles only
                columns = uid, title
            }
        }
    }

Each entry of :typoscript:`mod.web_list.downloadPresets`
defines the table name on the first level (in this case `pages`), followed by
any number of presets.

Each preset contains a :typoscript:`label` (the displayed name of the preset,
which can be a locallang key), a comma-separated list of each column that
should be included in the export as :typoscript:`columns` and optionally
an :typoscript:`identifier`. If :typoscript:`identifier` is not provided,
the identifier is generated as a hash of the :typoscript:`label` and
:typoscript:`columns`.

This can be manipulated with user TSConfig by adding the :typoscript:`page.`
prefix. User TSConfig is loaded after page TSConfig, so you can overwrite
existing keys or replace the whole list of keys:

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/user.tsconfig

    page.mod.web_list.downloadPresets {
        pages {
            10 {
                label = Quick overview (customized)
                columns = uid, title, crdate, slug
            }

            30 {
                label = Short with URL
                columns = uid, title, slug
            }
        }
    }

Since any table can be configured for a preset, any extension
can deliver a defined set of presets through the
:file:`EXT:my_extension/Configuration/page.tsconfig` file and
their table name(s).

Additionally, the list of presets can be manipulated via the new
:ref:`BeforeRecordDownloadPresetsAreDisplayedEvent <feature-102337-1715591177>`.

Impact
======

Editors can now export data with specific presets as required
as identified by the website maintainer or extension developer(s).

It is no longer required to pick specific columns to export over and over again,
and the list of presets is controlled by the website maintainer.

Two new PSR-14 Events have been added to allow further manipulation:

*  :ref:`BeforeRecordDownloadIsExecutedEvent <feature-102337-1715591177>`
*  :ref:`BeforeRecordDownloadPresetsAreDisplayedEvent <feature-102337-1715591178>`

.. index:: Backend, TSConfig
