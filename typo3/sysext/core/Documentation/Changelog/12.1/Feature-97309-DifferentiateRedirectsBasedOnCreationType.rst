.. include:: /Includes.rst.txt

.. _feature-97309:

================================================================
Feature: #97309 - Differentiate redirects based on creation type
================================================================

See :issue:`97309`

Description
===========

A new field :sql:`creation_type` has been added to the :sql:`sys_redirect` table.
This allows to differentiate between redirects created automatically when the
slug of a page has changed and the ones which are created in the backend
module by editors.

A new option in the :guilabel:`Redirects` module allows to filter by this type.

Impact
======

The distinction by the creation type helps users to administrate the redirect
records and helps to identify why a record has been created initially.

An update wizard updates all existing redirects and sets the type to "manual".

If desired, the available items of the field :sql:`creation_type` can be extended
with additional types by adjusting TCA for this field. This simplifies the
registration of additional types, which are automatically available in the backend
filtering. Possible use cases are, for example, synchronized redirects or migrated
from other / old sources sources.

..  code-block:: php

    $GLOBALS['TCA']['sys_redirect']['columns']['creation_type']['config']['items'][] = [
        'My extension redirects',
        91,
    ];

.. index:: Backend, ext:redirects
