.. include:: /Includes.rst.txt

.. _feature-98540:

===========================================================
Feature: #98540 - New TCA field control "passwordGenerator"
===========================================================

See :issue:`98540`

Description
===========

A new TCA field control :php:`passwordGenerator` has been introduced,
which can be used in combination with TCA type `password`. The control
renders a button next to the password field allowing the user to generate
a random password based on defined rules.

Using the control adds the generated password to the corresponding field.
The password is visible to the backend user only once and stored encrypted
in the database. Integrators are also able to define whether the user
is allowed to edit the generated password before saving.

Example configuration
---------------------

..  code-block:: php

    'password_field' => [
        'label' => 'Password',
        'config' => [
            'type' => 'password',
            'fieldControl' => [
                'passwordGenerator' => [
                    'renderType' => 'passwordGenerator',
                    'options' => [
                        'title' => 'Generate a password',
                        'allowEdit' => false,
                        'passwordRules' => [
                            'length' => 38,
                            'digitCharacters' => false,
                            'specialCharacters' => true,
                        ],
                    ],
                ],
            ],
        ],
    ],

This example will add the control with a custom title. The generated password
will be 38 characters long, will contain lowercase, uppercase and special
characters and no digit characters. The user won't be able to edit the
generated password.

Field control options
---------------------

-   :php:`title`: Define a title for the control button
-   :php:`allowEdit`: Whether the user can edit the generated password
-   :php:`passwordRules`: Define rules for the password.

Available password rules:

-   :php:`length`: Defines the number of characters for the password
    (minimum: :php:`8` - default: :php:`16`).
-   :php:`random`: Defines the encoding of random bytes. Overrules character
    definitions. Available encodings are :php:`hex` and :php:`base64`.
-   :php:`digitCharacters`: Whether digits should be used (Default: :php:`true`)
-   :php:`lowerCaseCharacters`: Whether lowercase characters should be used
    (Default: :php:`true`)
-   :php:`upperCaseCharacters`: Whether uppercase characters should be used
    (Default: :php:`true`)
-   :php:`specialCharacters`: Whether special characters should be used
    (Default: :php:`false`)

Random bytes
------------

The following example will generate a 40 characters long random hex string, which
could be used e.g. for secret tokens or similar:

..  code-block:: php

    'random_hex' => [
        'label' => 'Random hex',
        'config' => [
            'type' => 'password',
            'fieldControl' => [
                'passwordGenerator' => [
                    'renderType' => 'passwordGenerator',
                    'options' => [
                        'passwordRules' => [
                            'length' => 40,
                            'random' => 'hex',
                        ],
                    ],
                ],
            ],
        ],
    ],

..  note::

    Defining the special :php:`random` password rule always takes
    precedence over any character definition, which should therefore
    be omitted as soon as :php:`random` is set to one of the available
    encodings: :php:`hex` or :php:`base64`.

Impact
======

It is now possible to enhance the TCA type `password` with a field control
to generate a random password based on defined password rules.

.. index:: TCA, ext:backend
