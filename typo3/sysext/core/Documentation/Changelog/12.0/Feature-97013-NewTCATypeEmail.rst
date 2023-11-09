.. include:: /Includes.rst.txt

.. _feature-97013:

======================================
Feature: #97013 - New TCA type "email"
======================================

See :issue:`97013`

Description
===========

Especially TCA type :php:`input` has a wide range of use cases, depending
on the configured :php:`renderType` and the :php:`eval` options. Determination
of the semantic meaning is therefore usually quite hard and often leads to
duplicated checks and evaluations in custom extension code.

In our effort of introducing dedicated TCA types for all those use cases, the
TCA type :php:`email` has been introduced. It replaces the :php:`eval=email`
option of TCA type :php:`input`.

The TCA type :php:`email` features the following column configuration:

- :php:`autocomplete`
- :php:`behaviour`: :php:`allowLanguageSynchronization`
- :php:`default`
- :php:`eval`: :php:`unique` and :php:`uniqueInPid`
- :php:`fieldControl`
- :php:`fieldInformation`
- :php:`fieldWizard`
- :php:`mode`
- :php:`nullable`
- :php:`placeholder`
- :php:`readOnly`
- :php:`required`
- :php:`search`
- :php:`size`

.. note::

    The soft reference definition :php:`softref=>email[subst]` is automatically applied
    to all :php:`email` fields.

The following column configuration can be overwritten by page TSconfig:

- :typoscript:`readOnly`
- :typoscript:`size`

The migration from :php:`eval='email'` to :php:`type=email` is done like following:

..  code-block:: php

    // Before

    'email_field' => [
        'label' => 'Email',
        'config' => [
            'type' => 'input',
            'eval' => 'trim,email',
            'max' => 255,
        ]
    ]

    // After

    'email_field' => [
        'label' => 'Email',
        'config' => [
            'type' => 'email',
        ]
    ]

An automatic TCA migration is performed on the fly, migrating all occurrences
to the new TCA type and triggering a PHP :php:`E_USER_DEPRECATED` error
where code adoption has to take place.

.. note::

   The value of TCA type :php:`email` columns is automatically trimmed before
   being stored in the database. Therefore, the :php:`eval=trim` option is no
   longer needed and should be removed from the TCA configuration.

Impact
======

It's now possible to simplify the TCA configuration by using the new
dedicated TCA type :php:`email`.

.. index:: Backend, TCA, ext:backend
