.. include:: /Includes.rst.txt

.. _important-104693-1725960199:

==============================================================================
Important: #104693 - Setting allowLanguageSynchronization via columnsOverrides
==============================================================================

See :issue:`104693`

Description
===========

Setting the TCA option :php:`allowLanguageSynchronization` for a specific
column in a record type via :php:`columnsOverrides` is currently not supported
by TYPO3 and therefore might lead to exceptions in the corresponding field wizard
(:php:`LocalizationStateSelector`). To mitigate this, the option is now
automatically removed from the TCA configuration via a TCA migration. A
corresponding deprecation log entry is added to inform integrators about
the necessary code adjustments.

Migration
=========

Remove the :php:`allowLanguageSynchronization` option from :php:`columnsOverrides`
for now.

.. code-block:: php

    // Before
    'types' => [
        'text' => [
            'showitem' => 'header',
            'columnsOverrides' => [
                'header' => [
                    'config' => [
                        'behaviour' => [
                            'allowLanguageSynchronization' => true
                        ],
                    ],
                ],
            ],
        ],
    ],

    // After
    'types' => [
        'text' => [
            'showitem' => 'header',
        ],
    ],

.. index:: Backend, TCA, ext:backend
