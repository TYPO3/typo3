..  include:: /Includes.rst.txt

..  _feature-107105-1752687611:

=================================================================
Feature: #107105 - Introduce expression for accessing site locale
=================================================================

See :issue:`107105`

Description
===========

A new Symfony ExpressionLanguage expression :typoscript:`locale()` has been introduced.

This expression allows integrators and developers to directly access
the current site locale, which is provided as a locale object.
All public methods of this object are available for use.

Have a look at the corresponding documentation for more details:
:doc:`API <t3coreapi:ApiOverview/Localization/LocalizationApi/Locale#api>`


Example
=======

..  code-block:: typoscript

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

    variants:
      -
        identifier: language-variant-1
        condition: 'locale().getName() == "en-US"'
        label: 'First name'

Impact
======

Developers can now easily compare the locale of the current site directly
in expression contexts, without the need for using :typoscript:`siteLanguage("locale")`.

..  index:: TypoScript, ext:core, ext:form
