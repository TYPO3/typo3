.. include:: /Includes.rst.txt

.. _feature-97104:

=========================================
Feature: #97104 - New TCA type "password"
=========================================

See :issue:`97104`

Description
===========

Especially TCA type :php:`input` has a wide range of use cases, depending
on the configured :php:`renderType` and the :php:`eval` options. Determination
of the semantic meaning is therefore usually quite hard and often leads to
duplicated checks and evaluations in custom extension code.

In our effort of introducing dedicated TCA types for all those use cases, the
TCA type :php:`password` has been added. It replaces the :php:`eval=password`
and :php:`eval=saltedPassword` option of TCA type :php:`input`.

TCA password fields will be rendered as input :php:`type=password` fields.
By default, the :php:`autocomplete=off` attribute will be added to the
resulting input field. If :php:`autocomplete=true` is configured in TCA, a
:php:`autocomplete=new-fieldname` attribute will be added to the element.

The TCA type :php:`password` features the following column configuration:

- :php:`autocomplete`
- :php:`behaviour`: :php:`allowLanguageSynchronization`
- :php:`default`
- :php:`fieldControl`
- :php:`fieldInformation`
- :php:`fieldWizard`
- :php:`mode`
- :php:`nullable`
- :php:`placeholder`
- :php:`readOnly`
- :php:`required`
- :php:`size`
- :php:`hashed`

The following column configuration can be overwritten by page TSconfig:

- :typoscript:`readOnly`
- :typoscript:`size`

By default, TCA type :php:`password` will always save the field value
hashed to the database. The value will be hashed using the password hash
configuration for BE for all tables except :sql:`fe_users`, where the password hash
configuration for FE is used.

The TCA type :php:`password` introduces the new configuration :php:`hashed`,
which can be set to :php:`false`, if the field value should be saved as
plaintext to the database.

.. note::

    The configuration :php:`'hashed' => false` has no effect for all fields in
    the tables :sql:`be_users` and :sql:`fe_users`. In general it is not
    recommended to save passwords as plain text to the database.

The migration from :php:`eval='password'` and :php:`eval='saltedPassword'` to
:php:`type=password` is done like following:

..  code-block:: php

    // Before

    'password_field' => [
        'label' => 'Password',
        'config' => [
            'type' => 'input',
            'eval' => 'trim,password,saltedPassword',
        ]
    ]

    // After

    'password_field' => [
        'label' => 'Password',
        'config' => [
            'type' => 'password',
        ]
    ]

    // Before

    'another_password_field' => [
        'label' => 'Password',
        'config' => [
            'type' => 'input',
            'eval' => 'trim,password',
        ]
    ]

    // After

    'another_password_field' => [
        'label' => 'Password',
        'config' => [
            'type' => 'password',
            'hashed' => false,
        ]
    ]

An automatic TCA migration is performed on the fly, migrating all occurrences
to the new TCA type and triggering a PHP :php:`E_USER_DEPRECATED` error
where code adoption has to take place.

.. note::

   The value of TCA type :php:`password` column is automatically trimmed before
   being stored (and optionally hashed) in the database. Therefore, the :php:`eval=trim`
   option is no longer needed and should be removed from the TCA configuration.

Impact
======

It's now possible to simplify the TCA configuration by using the new
dedicated TCA type :php:`password`.

.. index:: Backend, TCA, ext:backend
