..  include:: /Includes.rst.txt

..  _deprecation-110865-1790803661:

============================================================================
Deprecation: #110865 - uuid option enableCopyToClipboard moved to appearance
============================================================================

See :issue:`110865`

Description
===========

The option :php:`enableCopyToClipboard` of TCA type :php:`uuid` has been
moved to :php:`['appearance']['copyToClipboard']`, in line with TCA type
:php:`password` and the appearance options of other types.

Impact
======

TCA using :php:`enableCopyToClipboard` for a field of type :php:`uuid`,
including :php:`columnsOverrides` and FlexForm fields, is migrated
automatically and a deprecation message is logged.

Overriding the option with TSconfig
:typoscript:`TCEFORM.<table>.<field>.config.enableCopyToClipboard` still
works, but triggers a PHP :php:`E_USER_DEPRECATED` error and will stop
working in TYPO3 v16.0.

Affected installations
======================

Installations with TCA or TSconfig disabling or enabling the copy to
clipboard button of a field of type :php:`uuid` via
:php:`enableCopyToClipboard`.

Migration
=========

Move the option to :php:`appearance`:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/tx_myextension_domain_model_item.php

    // Before
    'identifier' => [
        'label' => 'Identifier',
        'config' => [
            'type' => 'uuid',
            'enableCopyToClipboard' => false,
        ],
    ],

    // After
    'identifier' => [
        'label' => 'Identifier',
        'config' => [
            'type' => 'uuid',
            'appearance' => [
                'copyToClipboard' => false,
            ],
        ],
    ],

Adjust TSconfig accordingly:

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/page.tsconfig

    # Before
    TCEFORM.tx_myextension_domain_model_item.identifier.config.enableCopyToClipboard = 0

    # After
    TCEFORM.tx_myextension_domain_model_item.identifier.config.appearance.copyToClipboard = 0

..  index:: Backend, TCA, TSConfig, NotScanned, ext:backend
