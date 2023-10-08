.. include:: /Includes.rst.txt

.. _feature-97388:

==========================================================
Feature: #97388 - Introduce configurable password policies
==========================================================

See :issue:`97388`

Description
===========

TYPO3 now includes a PasswordPolicyValidator component which can be used to
validate passwords against configurable password policies. TYPO3 now also
includes a default password policy which ensures that passwords meet
the following requirements:

* At least 8 chars
* At least one number
* At least one upper case char
* At least one special char
* Must be different than current password (if available)

Password policies can be configured individually for both frontend and
backend context. It is also possible to extend a password policy with own
validation requirements.

As a first step, the included default password policy is applied to ext:setup
to ensure, that new passwords of backend users entered in "User Settings"
will match the default password requirements.

Impact
======

The new password of an existing TYPO3 backend user has to meet the default
password policy when set using ext:setup.

Configuring password policies
-----------------------------

A password policy is defined in the TYPO3 global configuration. Each policy
must have a unique identifier (the identifier `default` is reserved by TYPO3)
and must at least contain one validator.

The example below shows, how the password policy with the identifier  `simple`
is configured:

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies'] = [
        'simple' => [
            'validators' => [
                \TYPO3\CMS\Core\PasswordPolicy\Validator\CorePasswordValidator::class => [
                    'options' => [
                        'minimumLength' => 6,
                    ],
                ],
            ],
        ],
    ];

The password policy in the example uses the `CorePasswordValidator` with the
option to require a password with a minimum length of 6 chars.

The password policy identifier is used to assign the defined password policy
to the either backend and/or frontend context. By default, TYPO3 uses the
password policy `default` as shown below:

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordPolicy'] = 'default';
    $GLOBALS['TYPO3_CONF_VARS']['FE']['passwordPolicy'] = 'default';

Password policy validators
--------------------------

TYPO3 ships with two password policy validators, which are both used in the
default password policy.

\\TYPO3\\CMS\\Core\\PasswordPolicy\\Validator\\CorePasswordValidator
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

This validator has the ability to ensure a complex password with a defined
minimum length and 4 individual requirements.

The following options are available:

+------------------------------+-----------------------+---------+---------+
| Option                       | Description           | Type    | Default |
+------------------------------+-----------------------+---------+---------+
| `minimumLength`              | Minimum length        | Integer | 8       |
+------------------------------+-----------------------+---------+---------+
| `upperCaseCharacterRequired` | Upper case char check | Boolean | false   |
+------------------------------+-----------------------+---------+---------+
| `lowerCaseCharacterRequired` | Lower case char check | Boolean | false   |
+------------------------------+-----------------------+---------+---------+
| `digitCharacterRequired`     | Digit check           | Boolean | false   |
+------------------------------+-----------------------+---------+---------+
| `specialCharacterRequired`   | Special char check    | Boolean | false   |
+------------------------------+-----------------------+---------+---------+

\\TYPO3\\CMS\\Core\\PasswordPolicy\\Validator\\NotCurrentPasswordValidator
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

This validator can be used to ensure, that the new user password is not
equal to the old password. The validator must always be configured with
the exclude action :php:`\TYPO3\CMS\Core\PasswordPolicy\PasswordPolicyAction::NEW_USER_PASSWORD`,
because it should be excluded, when a new user account is created.

Disable password policies globally
----------------------------------

To disable the password policy globally (e.g. for local development) an
empty string has to be supplied as password policy for both frontend and
backend context as shown below:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordPolicy'] = '';
   $GLOBALS['TYPO3_CONF_VARS']['FE']['passwordPolicy'] = '';

Custom password validator
-------------------------

To create a custom password validator, a new class has to be created which
extends :php:`\TYPO3\CMS\Core\PasswordPolicy\Validator\AbstractPasswordValidator`.
It is required to overwrite the following functions:

* :php:`public function initializeRequirements(): void`
* :php:`public function validate(string $password, ?ContextData $contextData = null): bool`

Please refer to :php:`\TYPO3\CMS\Core\PasswordPolicy\Validator\CorePasswordValidator`
for a detailed implementation example.

.. index:: Backend
