.. include:: /Includes.rst.txt

.. _feature-97193:

=======================================
Feature: #97193 - New TCA type "number"
=======================================

See :issue:`97193`

Description
===========

Especially TCA type :php:`input` has a wide range of use cases, depending
on the configured :php:`renderType` and the :php:`eval` options. Determination
of the semantic meaning is therefore usually quite hard and often leads to
duplicated checks and evaluations in custom extension code.

In our effort of introducing dedicated TCA types for all those use
cases, the TCA type :php:`number` has been introduced. It replaces the
:php:`eval=int` and :php:`eval=double2` options of TCA type :php:`input`.

The TCA :php:`number` fields will be rendered with the html :html:`type`
attribute set to :html:`number`.

The TCA type :php:`number` features the following column configuration:

- :php:`autocomplete`
- :php:`behaviour`: :php:`allowLanguageSynchronization`
- :php:`default`
- :php:`fieldControl`
- :php:`fieldInformation`
- :php:`fieldWizard`
- :php:`format`: :php:`integer`, :php:`decimal`
- :php:`mode`
- :php:`nullable`
- :php:`placeholder`
- :php:`range`: :php:`lower`, :php:`upper`
- :php:`readOnly`
- :php:`required`
- :php:`search`
- :php:`size`
- :php:`slider`: :php:`step`, :php:`width`
- :php:`valuePicker`: :php:`items`, :php:`mode`

The following column configuration can be overwritten by page TSconfig:

- :typoscript:`readOnly`
- :typoscript:`size`

The TCA type :php:`number` introduces the new configuration :php:`format`,
which can be set to :php:`decimal` or :php:`integer`, which is the default.

.. note::

   The :php:`slider` option allows to define a visual slider element
   next to the input field. The steps can be defined with the :php:`step`
   option. The minimum and maximum value can be configured with the
   :php:`range[lower]` and :php:`range[upper]` options.

.. note::

   The :php:`valuePicker` option allows to define default values via
   :php:`items`. With :php:`mode`, one can define how the selected
   value should be added (replace, prepend or append).

.. note::

    The options :php:`range`, :php:`slider` as well as :php:`eval=double2`
    are no longer evaluated for TCA type :php:`input`.

Migration
---------

The migration from :php:`eval='int'` to :php:`type=number`
is done like following:

..  code-block:: php

    // Before

    'int_field' => [
        'label' => 'Int field',
        'config' => [
            'type' => 'input',
            'eval' => 'int',
        ]
    ]

    // After

    'int_field' => [
        'label' => 'Int field',
        'config' => [
            'type' => 'number',
        ]
    ]

The migration from :php:`eval=double2` to :php:`type=number`
is done like following:

..  code-block:: php

    // Before

    'double2_field' => [
        'label' => 'double2 field',
        'config' => [
            'type' => 'input',
            'eval' => 'double2',
        ]
    ]

    // After

    'double2_field' => [
        'label' => 'double2 field',
        'config' => [
            'type' => 'number',
            'format' => 'decimal'
        ]
    ]

An automatic TCA migration is performed on the fly, migrating all occurrences
to the new TCA type and triggering a PHP :php:`E_USER_DEPRECATED` error
where code adoption has to take place.

Impact
======

It's now possible to simplify the TCA configuration by using the new
dedicated TCA type :php:`number`. Setting the new :php:`format` option
to :php:`decimal` behaves like the former :php:`eval=double2`.

.. index:: TCA, ext:backend
