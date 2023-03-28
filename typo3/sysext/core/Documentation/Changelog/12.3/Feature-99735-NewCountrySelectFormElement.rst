.. include:: /Includes.rst.txt

.. _feature-99735-1678701694:

=================================================
Feature: #99735 - New Country Select form element
=================================================

See :issue:`99735`

Description
===========

Since :ref:`feature-99618-1674063182`, TYPO3 provides a list of countries, together with an API
and a Fluid form ViewHelper. A new "Country select" form element has now been
added to the TYPO3 Form Framework for creating a country select in a form
easily. The new form element features a couple of configuration options, which
can either be configured via the :guilabel:`Forms` module or directly in the
corresponding YAML file.

Available options
-----------------

- `First option` (:yaml:`prependOptionLabel`): Define the "empty option", i.e. the first element of the select. You can use this to provide additional guidance for the user.
- `Prioritized countries` (:yaml:`prioritizedCountries`): Define a list of countries which should be listed as first options in the form element.
- `Only countries` (:yaml:`onlyCountries`): Restrict the countries to be rendered in the list.
- `Exclude countries` (:yaml:`excludeCountries`): Define which countries should not be shown in the list.

The new element will be rendered as single select (:html:`<select>`) HTML
element in the frontend.

Impact
======

The new "Country select" form element is now available in the Form
Framework with a couple of specific configuration options.

.. index:: ext:form
