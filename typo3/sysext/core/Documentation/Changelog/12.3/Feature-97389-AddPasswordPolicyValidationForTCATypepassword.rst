.. include:: /Includes.rst.txt

.. _feature-97389-1673972552:

======================================================================
Feature: #97389 - Add password policy validation for TCA type=password
======================================================================

See :issue:`97389`

Description
===========

It is now possible to assign a password policy to TCA fields of the type
`password`. For configured fields, the password policy validator will be used
in `DataHandler` to ensure, that the new password comply with the configured
password policy.

Password policy requirements are shown below the password field, when the focus
is changed to the password field.

The TCA field `password` for the tables `be_users` and `fe_users` does now by
default use the password policy configured in
`$GLOBALS['TYPO3_CONF_VARS']['FE']['passwordPolicy']` (fe_users) or
`$GLOBALS['TYPO3_CONF_VARS']['BE']['passwordPolicy']` (be_users).

Example configuration
---------------------

..  code-block:: php

    'password_field' => [
        'label' => 'Password',
        'config' => [
            'type' => 'password',
            'passwordPolicy' => 'default',
        ],
    ],

This example will use the password policy `default` for the field.

Impact
======

For TYPO3 frontend and backend users, the global password policy is used. A
new password is not saved, if it does not comply with the password policy.

.. index:: Backend, ext:core
