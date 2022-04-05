.. include:: /Includes.rst.txt

================================================================
Feature: #97309 - Differentiate redirects based on creation type
================================================================

See :issue:`97309`

Description
===========

A new field `creation_type` has been added to the redirects.
This allows to differentiate between redirects created automatically when the
slug of a page is changed and the ones which are created in the backend
module by editors.

A new option in the redirects module allows to filter by this type.

Impact
======

The distinction by the creation type helps users to administrate the redirect
records and helps to identify why a record has been created initially.

A update wizards updates all existing redirects and set the type to manually.

If desired, the available items of the field `creation_type` can be extended
with additional type by adjusting TCA for this field. This simplifies the
registration of additional types, which automatically are available in backend
filtering. Possible use-cases are for example synced redirects or migrated from
other / old sources sources.

.. code-block:: php

    $GLOBALS['TCA']['sys_redirect']['columns']['creation_type']['config']['items'][] = [
        'My extension redirects',
        91,
    ];

.. index:: Backend, ext:redirects
