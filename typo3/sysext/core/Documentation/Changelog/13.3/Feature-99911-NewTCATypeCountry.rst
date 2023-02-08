.. include:: /Includes.rst.txt

.. _feature-99911-1675976882:

========================================
Feature: #99911 - New TCA type "country"
========================================

See :issue:`99911`

Description
===========

A new TCA field type called :php:`country` has been added to TYPO3 Core. Its main
purpose is to use the newly introduced Country API to provide a country selection in the backend.

The new TCA type displays all filtered countries including the configurable name and the corresponding flag.

.. code-block:: php

    'country' => [
        'label' => 'Country',
        'config' => [
            'type' => 'country',
            // available options: name, localizedName, officialName, localizedOfficialName, iso2, iso3
            'labelField' => 'localizedName',
            // countries which are listed before all others
            'prioritizedCountries' => ['AT', 'CH'],
            // sort by the label
            'sortItems' => [
                'label' => 'asc'
            ],
            'filter' => [
                // restrict to the given country ISO2 or ISO3 codes
                'onlyCountries' => ['DE', 'AT', 'CH', 'FR', 'IT', 'HU', 'US', 'GR', 'ES'],
                // exclude by the given country ISO2 or ISO3 codes
                'excludeCountries' => ['DE', 'ES'],
            ],
            'items' => [
                ['label' => '', 'value' => ''],
            ],
            'default' => 'HU',
        ],
    ],

.. index:: Backend, TCA, ext:core
