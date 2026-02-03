..  include:: /Includes.rst.txt

..  _feature-69190-1770137533:

=================================================
Feature: #69190 - Add password generator "wizard"
=================================================

See :issue:`69190`

Description
===========

Password generation in the backend is now driven by password policies. Each
password policy can define a `generator` section with a class implementing
:php:`TYPO3\CMS\Core\PasswordPolicy\Generator\PasswordGeneratorInterface`.

The `passwordGenerator` field control references a policy by name via the
`passwordPolicy` option. The dice icon button next to the field generates
a password using the configured generator.

The field control can be added to any password field via TCA configuration,
making it available for extension developers as well.

Password policies
-----------------

The policy to use is determined by context:

*   **Backend users**: :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['passwordPolicy']`
*   **Frontend users**: :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['passwordPolicy']`

All password policies are registered under
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies']`.

TYPO3 ships with three pre-configured policies:

*   `default` — Used for backend and frontend users
*   `installTool` — Used for install tool passwords
*   `secretToken` — Used for secret token fields (e.g. webhooks, reactions)

Each policy contains both a `generator` and `validators` section. The
generator is responsible for creating passwords, while validators enforce
password requirements. They are configured independently within the same policy.

Example
=======

The TYPO3 core ships a `PasswordGenerator` implementation which is
configured like this:

..  code-block:: php

    <?php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies']['default']['generator'] = [
        'className' => \TYPO3\CMS\Core\PasswordPolicy\Generator\PasswordGenerator::class,
        'options' => [
            'length' => 12,
            'upperCaseCharacters' => true,
            'lowerCaseCharacters' => true,
            'digitCharacters' => true,
            'specialCharacters' => true,
        ],
    ];

The `PasswordGenerator` supports the following options:

- `length`: Length of the generated password
- `upperCaseCharacters`: Whether to include upper case characters
- `lowerCaseCharacters`: Whether to include lower case characters
- `digitCharacters`: Whether to include digits
- `specialCharacters`: Whether to include special characters

Adjusting an existing policy:

..  code-block:: php
    :caption: config/system/additional.php OR typo3conf/system/additional.php

    <?php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies']['default']['generator']['options']['length'] = 20;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies']['default']['generator']['options']['specialCharacters'] = false;

Registering a custom password policy with a custom generator:

..  code-block:: php
    :caption: config/system/additional.php OR typo3conf/system/additional.php

    <?php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies']['customPolicy'] = [
        'generator' => [
            'className' => \Vendor\MyPackage\PasswordPolicy\Generator\MyPasswordGenerator::class,
            'options' => [
                'length' => 12,
                'myCustomOption' => 'my custom Value',
            ],
        ],
        'validators' => [
            // your custom validators
        ],
    ];

    $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordPolicy'] = 'customPolicy';
    $GLOBALS['TYPO3_CONF_VARS']['FE']['passwordPolicy'] = 'customPolicy';

Impact
======

Password generation for backend and frontend users is now configurable through
password policies. The Install Tool command
:bash:`vendor/bin/typo3 install:password:set` also respects the configured
policy.

..  index:: Backend, CLI, Frontend, PHP-API, TCA, ext:core
