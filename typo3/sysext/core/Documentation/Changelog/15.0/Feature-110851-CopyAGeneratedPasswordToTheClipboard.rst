..  include:: /Includes.rst.txt

..  _feature-110851-1790717834:

=============================================================
Feature: #110851 - Copy a generated password to the clipboard
=============================================================

See :issue:`110851`

Description
===========

Fields of TCA type `password` accept the new option
`copyToClipboard` below `appearance`.

When it is enabled, a button copying the password to the clipboard is
attached to the field.

A stored password is only shown obfuscated, so the button is offered
once the field holds a plain value, typically one created with the
`passwordGenerator` field control, and until the record is saved.

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/tx_myextension_token.php

    'secret' => [
        'label' => 'Secret',
        'config' => [
            'type' => 'password',
            'appearance' => [
                'copyToClipboard' => true,
            ],
            'fieldControl' => [
                'passwordGenerator' => [
                    'renderType' => 'passwordGenerator',
                    'options' => [
                        'allowEdit' => false,
                    ],
                ],
            ],
        ],
    ],

The option defaults to :php:`false`. The secrets of reactions and webhooks
in :guilabel:`Administration > Integrations` enable it.

Impact
======

A generated password shown in a field that may not be edited can be copied with
a single click. Until now its text could not be selected in such a field, so it
was lost once the record was saved.

..  index:: Backend, TCA, ext:backend
