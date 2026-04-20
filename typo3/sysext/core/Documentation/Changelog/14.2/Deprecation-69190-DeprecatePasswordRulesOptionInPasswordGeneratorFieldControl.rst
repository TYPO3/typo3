..  include:: /Includes.rst.txt

..  _deprecation-69190-1770668741:

========================================================================================
Deprecation: #69190 - Deprecate random password generator for frontend and backend users
========================================================================================

See :issue:`69190`

Description
===========

The `passwordRules` option of the `passwordGenerator` field control has been
deprecated. Password generation is now configured through password policies
registered in :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies']`.

Each password policy can define a `generator` section with a class implementing
:php:`\TYPO3\CMS\Core\PasswordPolicy\Generator\PasswordGeneratorInterface`.
The field control references a policy by name via the new `passwordPolicy`
option instead of defining rules inline.

Impact
======

Using the `passwordRules` option in TCA field control configuration will
trigger a PHP deprecation warning. Support for `passwordRules` will be
removed in TYPO3 v15.

Affected installations
======================

Installations that use the `passwordGenerator` field control with the
`passwordRules` option in custom TCA configurations, for example in password
or secret token fields.

Migration
=========

Replace the `passwordRules` option with a `passwordPolicy` reference.

..  code-block:: diff
    :caption: EXT:my_extension/Configuration/TCA/Overrides/be_users.php

     'fieldControl' => [
         'passwordGenerator' => [
             'renderType' => 'passwordGenerator',
             'options' => [
     -           'passwordRules' => [
     -               'length' => 20,
     -               'upperCaseCharacters' => true,
     -               'lowerCaseCharacters' => true,
     -               'digitCharacters' => true,
     -               'specialCharacters' => false,
     -           ],
     +           'passwordPolicy' => 'myCustomPolicy',
             ],
         ],
     ],

The referenced password policy must be registered in
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies']`:

..  code-block:: php
    :caption: config/system/additional.php OR typo3conf/system/additional.php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies']['myCustomPolicy'] = [
        'generator' => [
            'className' => \TYPO3\CMS\Core\PasswordPolicy\Generator\PasswordGenerator::class,
            'options' => [
                'length' => 20,
                'upperCaseCharacters' => true,
                'lowerCaseCharacters' => true,
                'digitCharacters' => true,
                'specialCharacters' => false,
            ],
        ],
        'validators' => [],
    ];

..  note::

    For backend and frontend user password fields, the field control is now
    provided by the core automatically. If your TCA override only added the
    `passwordGenerator` field control with default rules, you can remove it
    entirely. The core uses the password policy configured in
    :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['passwordPolicy']` and
    :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['passwordPolicy']` respectively.

See :ref:`feature-69190-1770137533` for details on password policies and
custom password generators.

..  index:: Backend, Frontend, PHP-API, TCA, NotScanned
