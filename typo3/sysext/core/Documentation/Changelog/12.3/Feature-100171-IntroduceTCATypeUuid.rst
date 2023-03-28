.. include:: /Includes.rst.txt

.. _feature-100171-1678869689:

==========================================
Feature: #100171 - Introduce TCA type uuid
==========================================

See :issue:`100171`

Description
===========

In our effort of introducing dedicated TCA types for special use cases,
a new TCA field type called :php:`uuid` has been added to TYPO3 Core.
Its main purpose is to simplify the TCA configuration when working with
fields, containing a UUID.

The TCA type :php:`uuid` features the following column configuration:

- :php:`enableCopyToClipboard`
- :php:`fieldInformation`
- :php:`required`: Defaults to :php:`true`
- :php:`size`
- :php:`version`

..  note::

    In case :php:`enableCopyToClipboard` is set to :php:`true`, which is the
    default, a button is rendered next to the input field, which allows to copy
    the UUID to the clipboard of the operating system.

..  note::

    The :php:`version` option defines the UUID version to be used. Allowed
    values are `4`, `6` or `7`. The default is `4`. For more information
    about the different versions, have a look at the corresponding
    `symfony documentation`_.

The following column configuration can be overwritten by page TSconfig:

- :typoscript:`size`
- :typoscript:`enableCopyToClipboard`

An example configuration looks like the following:

..  code-block:: php

    'identifier' => [
        'label' => 'My record identifier',
        'config' => [
            'type' => 'uuid',
            'version' => 6,
        ],
    ],

Impact
======

It is now possible to use a dedicated TCA type for rendering of a UUID field.
Using the new TCA type, corresponding database columns are added automatically.

.. _symfony documentation: https://symfony.com/doc/current/components/uid.html#uuids

.. index:: Backend, TCA, ext:backend
