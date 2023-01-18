.. include:: /Includes.rst.txt

.. _feature-99618-1674063182:

==========================================================================
Feature: #99618 - List of countries in the world and their localized names
==========================================================================

See :issue:`99618`

Description
===========

TYPO3 now ships a list of countries of the world. The list is based on the
ISO 3166-1 standard, with the Alpha-numeric short name ("FR" or "FRA" in its
three-letter short name), the english name ("France"), the official name
("Republic of France"), also the numerical code, and the country's flag as
emoji (UTF-8 representation).

This list is based on Debian's ISO code list https://salsa.debian.org/iso-codes-team/iso-codes,
and shipped statically as PHP content in a new Country API.


Impact
======

It is now possible to load a list of all countries via PHP:

.. code-block:: php

    $countryProvider = GeneralUtility::makeInstance(CountryProvider:php);
    $france = $countryProvider->getByIsoCode('FR');
    // or
    $france = $countryProvider->getByName('France');
    // or
    $france = $countryProvider->getByAlpha3IsoCode('FRA');

A country object can be used to fetch all information about this,
also with translatable labels:

.. code-block:: php

    $languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('de');
    echo $france->getName();    // "France"
    echo $languageService->sL($france->getLocalizedNameLabel()); // "Frankreich"
    echo $france->getOfficialName();    // "French Republic"
    echo $languageService->sL($france->getLocalizedOfficalNameLabel()); // "Französische Republik"
    echo $france->getNumericRepresentation(); // 250
    echo $france->getAlpha2IsoCode(); // "FR"
    echo $france->getFlag(); // "🇫🇷"

A Fluid ViewHelper is also shipped with TYPO3 to render a dropdown
for forms:

.. code-block:: html

    <f:form.countrySelect
        name="country"
        value="AT"
        sortByOptionLabel="true"
        prioritizedCountries="{0: 'DE', 1: 'AT', 2: 'CH'}"
    />

Available options
---------------------

-   :php:`disabled`: Specifies that the form element should be disabled when the page loads.
-   :php:`required`: If set no empty value is allowed.
-   :php:`errorClass`: Specify the CSS class to be set if there are errors for this ViewHelper.
-   :php:`sortByOptionLabel`: Whether the country list should be sorted by option label or not.

-   :php:`optionLabelField`: Specify the type of label of the country list. Available options are: "name", "localizedName", "officialName" or "localizedOfficialName". Default option is "localizedName".
-   :php:`alternativeLanguage`: If specified, the country list will be shown in the given language.

-   :php:`prioritizedCountries`: Define a list of countries which should be listed as first options in the form element.
-   :php:`onlyCountries`: Restrict the countries to be rendered in the list.
-   :php:`excludeCountries`: Define which countries should not be shown in the list.

-   :php:`prependOptionLabel`: Provide an additional option at first position with the specified label.
-   :php:`prependOptionValue`: Provide an additional option at first position with the specified value.

Hint:
A combination of :php:`optionLabelField` and :php:`alternativeLanguage` is
possible. For instance if you want to show the localized official names but not
in your default language but in French.
You can achieve this by using the following combination:

.. code-block:: html

    <f:form.countrySelect
        name="country"
        optionLabelField="localizedOfficialName"
        alternativeLanguage="fr"
        sortByOptionLabel="true"
    />

.. index:: Fluid, PHP-API, ext:core
