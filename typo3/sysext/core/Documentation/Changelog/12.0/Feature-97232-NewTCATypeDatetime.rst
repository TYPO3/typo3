.. include:: /Includes.rst.txt

=========================================
Feature: #97232 - New TCA type "datetime"
=========================================

See :issue:`97232`

Description
===========

Especially TCA type :php:`input` has a wide range of use cases, depending
on the configured :php:`renderType` and the :php:`eval` options. Determination
of the semantic meaning is therefore usually quite hard and often leads to
duplicated checks and evaluations in custom extension code.

In our effort of introducing dedicated TCA types for all those use
cases, the TCA type :php:`datetime` has been introduced. It replaces the
:php:`renderType=inputDateTime` of TCA type :php:`input`.

The TCA type :php:`datetime` features the following column configuration:

- :php:`behaviour`: :php:`allowLanguageSynchronization`
- :php:`dbType`: :php:`date`, :php:`time`, :php:`datetime`
- :php:`default`
- :php:`disableAgeDisplay`
- :php:`eval`: :php:`null`
- :php:`fieldControl`
- :php:`fieldInformation`
- :php:`fieldWizard`
- :php:`format`: :php:`datetime` (default), :php:`date`, :php:`time`, :php:`timesec`
- :php:`mode`
- :php:`placeholder`
- :php:`range`: :php:`lower`, :php:`upper`
- :php:`readOnly`
- :php:`required`
- :php:`search`
- :php:`size`

.. note::

   The :php:`eval=int` option is now automatically set for the element in
   case no specific :php:`dbType` has been defined. It should therefore be
   removed from the TCA configuration. The only valid option for :php:`eval`
   is :php:`null`.

.. note::

   The :php:`format` option defines how the display of the field value
   will be in e.g. FormEngine. The storage format is defined via :php:`dbType`
   or :php:`eval=int`.

The following column configuration can be overwritten by Page TSconfig:

- :typoscript:`readOnly`
- :typoscript:`size`

A complete migration from :php:`renderType=inputDateTime` to :php:`type=datetime`
looks like the following:

.. code-block:: php

    // Before

    'a_datetime_field' => [
        'label' => 'Datetime field',
        'config' => [
            'type' => 'input',
            'renderType' => 'inputDateTime',
            'required' => true,
            'size' => 20,
            'max' => 1024,
            'eval' => 'date,int',
            'default' => 0,
        ],
    ],

   // After

    'a_datetime_field' => [
        'label' => 'Datetime field',
        'config' => [
            'type' => 'datetime',
            'format' => 'date',
            'required' => true,
            'size' => 20,
            'default' => 0,
        ]
    ]

An automatic TCA migration is performed on the fly, migrating all occurrences
to the new TCA type and triggering a PHP :php:`E_USER_DEPRECATED` error
where code adoption has to take place.

.. note::

    The corresponding FormEngine class has been renamed from :php:`InputDateTimeElement`
    to :php:`DatetimeElement`. An entry in the "ClassAliasMap" has been added
    for extensions calling this class directly, which is rather unlikely. The
    extension scanner will report any usage, which should then be migrated.

Impact
======

It's now possible to simplify the TCA configuration by using the new
dedicated TCA type :php:`datetime`.

.. index:: Backend, TCA, ext:backend
