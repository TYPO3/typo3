..  include:: /Includes.rst.txt

..  _feature-107105-1752687611:

=================================================================
Feature: #107105 - Introduce expression for accessing site locale
=================================================================

See :issue:`107105`

Description
===========

A new Symfony ExpressionLanguage expression :typoscript:`locale()` has been
introduced.

This expression allows integrators and developers to directly access
the current site locale, which is provided as a locale object.
All public methods of this object are available for use.

For more information, refer to the API documentation:
:doc:`API <t3coreapi:ApiOverview/Localization/LocalizationApi/Locale#api>`

Example
=======

..  code-block:: typoscript
    :caption: Using locale() in TypoScript conditions

    [locale().getName() == "en-US"]
        page.20.value = bar
    [END]
    [locale().getCountryCode() == "US"]
        page.30.value = foo
    [END]
    [locale().isRightToLeftLanguageDirection()]
        page.40.value = bar
    [END]

..  code-block:: yaml
    :caption: Using locale() in a form variant definition

    variants:
      - identifier: language-variant-1
        condition: 'locale().getName() == "en-US"'
        label: 'First name'

Impact
======

Developers can now compare or evaluate the site locale directly in expressions,
without using :typoscript:`siteLanguage("locale")`.

..  index:: TypoScript, ext:core, ext:form
