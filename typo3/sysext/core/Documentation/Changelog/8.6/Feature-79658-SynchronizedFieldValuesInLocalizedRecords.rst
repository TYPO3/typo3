.. include:: ../../Includes.txt

================================================================
Feature: #79658 - Synchronized field values in localized records
================================================================

See :issue:`79658`

Description
===========

The localized record overlay behaviour has been changed to make localization-rows standalone.

Previously, if fields in :code:`TCA` columns were set to :code:`l10n_mode` :code:`exclude`
or :code:`mergeIfNotBlank`, the localized record overlay did not contain values, and those
values were "pulled up" from the underlying default language records.

This has been changed, the :code:`DataHandler` now copies those values over to the localized
record and synchronizes them if the default language record is changed.

As a substitution of the :code:`mergeIfNotBlank` feature, the new configuration :code:`allowLanguageSynchronization`
has been added. Setting this adds a wizard to single fields and an editor can select if a field of a localized record
should be kept in sync with the default language record, or the localized record it was derived from.

A typical configuration looks like that:

.. code-block:: php

    'columns' => [
        ...
        'header' => [
            'label' => 'My header',
            'config' => [
                'type' => 'input',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
    ],

:code:`TCA` tables that configure the language localization get the field :code:`l10n_state` added by the schema analyzer
which stores a json string with field names and the values :code:`custom`, :code:`parent` or :code:`source` to
specify if and from which record a single field gets its value.

.. index:: Backend, Database, Frontend, PHP-API, TCA
