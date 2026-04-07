..  include:: /Includes.rst.txt

..  _feature-109444-1744012800:

===========================================================================
Feature: #109444 - Add default value support for CountrySelect form element
===========================================================================

See :issue:`109444`

Description
===========

The TYPO3 Form Framework's :yaml:`CountrySelect` element now supports a
:yaml:`defaultValue` property. Previously only :yaml:`prioritizedCountries`,
:yaml:`onlyCountries`, and :yaml:`excludeCountries` could be configured — there
was no way to pre-select a country when the form is initially rendered.

The default value can be set in two ways:

*  Directly in the YAML form definition.
*  Through the new :yaml:`Inspector-CountrySingleSelectEditor` in the Form
   Editor backend module.

YAML form definition example:

..  code-block:: yaml

    type: CountrySelect
    identifier: country-1
    label: 'Country'
    defaultValue: 'DE'
    properties:
      onlyCountries:
        - DE
        - AT
        - CH

The new inspector editor is a single-select country dropdown that appears in
the Form Editor's inspector panel. It lists all available countries regardless
of any configured :yaml:`onlyCountries`, :yaml:`excludeCountries`, or
:yaml:`prioritizedCountries` filters. A description hint reminds editors to
ensure the selected country is not excluded by the configured country filters.

If the configured :yaml:`defaultValue` refers to a country that is excluded by
a country filter, the default value has no visible effect on the frontend
because the corresponding :html:`<option>` element will not be rendered.

On the frontend the :yaml:`defaultValue` is resolved through the existing form
framework property binding, so no additional rendering logic is required.

Impact
======

Integrators and editors can now pre-select a country for :yaml:`CountrySelect`
form elements. The default value can be configured via YAML or through the
Form Editor backend module without writing custom code.

.. index:: Backend, ext:form
