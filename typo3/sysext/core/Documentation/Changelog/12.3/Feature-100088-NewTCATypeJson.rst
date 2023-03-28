.. include:: /Includes.rst.txt

.. _feature-100088-1677965005:

======================================
Feature: #100088 - New TCA type "json"
======================================

See :issue:`100088`

Description
===========

In our effort of introducing dedicated TCA types for special use cases,
a new TCA field type called :php:`json` has been added to TYPO3 Core.
Its main purpose is to simplify the TCA configuration when working with
fields, containing JSON data. It therefore :ref:`replaces <important-100088-1677950866>`
the previously introduced :php:`dbtype=json` of TCA type :php:`user`.

Using the new type, TYPO3 automatically takes care of adding the corresponding
database column.

The TCA type :php:`json` features the following column configuration:

- :php:`behaviour`: :php:`allowLanguageSynchronization`
- :php:`cols`
- :php:`default`
- :php:`enableCodeEditor`
- :php:`fieldControl`
- :php:`fieldInformation`
- :php:`fieldWizard`
- :php:`placeholder`
- :php:`readOnly`
- :php:`required`
- :php:`rows`

..  note::

    In case :php:`enableCodeEditor` is set to :php:`true`, which is the default
    and the system extension `t3editor` is installed and active, the JSON value
    is rendered in the corresponding code editor. Otherwise it is rendered in a
    standard `textarea` HTML element.

The following column configuration can be overwritten by page TSconfig:

- :typoscript:`cols`
- :typoscript:`rows`
- :typoscript:`readOnly`



Impact
======

It is now possible to use a dedicated TCA type for rendering of JSON fields.
Using the new TCA type, corresponding database columns are added automatically.

.. index:: Backend, PHP-API, TCA, ext:backend
