..  include:: /Includes.rst.txt

..  _feature-107088-1753085420:

=======================================================================
Feature: #107088 - Allow to utilize password validators in Install Tool
=======================================================================

See :issue:`107088`

Description
===========

The TYPO3 Install Tool password can now utilize validators as defined
via the :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies']['installTool']['validators']`
array. By default, this re-uses the 'default' validator
:php:`\TYPO3\CMS\Core\PasswordPolicy\Validator\CorePasswordValidator` with the configuration:

..  code-block:: php

    'SYS' => [
        'passwordPolicies' => [
            'installTool' => [
                'validators' => [
                    \TYPO3\CMS\Core\PasswordPolicy\Validator\CorePasswordValidator::class => [
                        'options' => [
                            'minimumLength' => 8,
                            'upperCaseCharacterRequired' => true,
                            'lowerCaseCharacterRequired' => true,
                            'digitCharacterRequired' => true,
                            'specialCharacterRequired' => true,
                        ],
                        'excludeActions' => [],
                    ],
                ],
            ],
        ],
    ],

This will require 8 characters minimum (as it was before) and now also require
at least one upper-case, one lower-case, one digit and one special character.

The validator is utilized in both scenarios when setting the Install Tool password
via CLI (`bin/typo3 install:password:set`) as well as the Install Tool GUI via
:guilabel:`Admin Tools > Settings > Change Install Tool Password` in the TYPO3 backend.

If a password is auto-generated via the mentioned CLI command, by default it uses
8 characters. The password-length for adapted validator configurations can then be
specified with the new `--password-length=XXX` argument.

Impact
======

Maintainers now need to set secure Install Tool passwords and can configure
validation rules.

Existing Install Tool passwords are not affected, making this a non-breaking feature.
However, these should be revisited by maintainers and maybe set to a more secure
password.

To disable password policies (not recommended!), the configuration option
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies']['installTool']['validators']`
can be set to an empty array (or null).

..  hint::

    Please note, you can only override this configuration directive through the
    `additional.php` configuration file, otherwise an empty array or null value
    will trigger merging the `DefaultConfiguration` over the defined settings,
    and re-adding default validators to your setup.

..  index:: CLI, ext:core, ext:install
