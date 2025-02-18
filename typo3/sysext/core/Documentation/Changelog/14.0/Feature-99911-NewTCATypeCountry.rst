.. include:: /Includes.rst.txt

.. _feature-99911-1675976882:

========================================
Feature: #99911 - New TCA type "country"
========================================

See :issue:`99911`

Description
===========

A new TCA field type called :php:`country` has been added to TYPO3 Core. Its main
purpose is to use the newly introduced
`Country API <https://docs.typo3.org/permalink/t3coreapi:country-api>`_ to provide
a country selection in the backend and use the stored representation in Extbase
or TypoScript output.

TCA Configuration
-----------------

The new TCA type displays all filtered countries including the configurable name and the corresponding flag.

.. code-block:: php
   :caption: Configuration/TCA/tx_myextension_mymodel.php

    'country' => [
        'label' => 'Country',
        'config' => [
            'type' => 'country',
            // available options: name, localizedName, officialName, localizedOfficialName, iso2, iso3
            'labelField' => 'localizedName',
            // countries which are listed before all others
            'prioritizedCountries' => ['AT', 'CH'],
            // sort by the label
            'sortItems' => [
                'label' => 'asc'
            ],
            'filter' => [
                // restrict to the given country ISO2 or ISO3 codes
                'onlyCountries' => ['DE', 'AT', 'CH', 'FR', 'IT', 'HU', 'US', 'GR', 'ES'],
                // exclude by the given country ISO2 or ISO3 codes
                'excludeCountries' => ['DE', 'ES'],
            ],
            'default' => 'HU',
            // When required=false, an empty selection ('') is possible
            'required' => false,
        ],
    ],

Note that extra items / countries should be added via the :ref:`new PSR-14 event BeforeCountriesEvaluatedEvent <feature-104168-1719373149>`.

Flexform Configuration
----------------------

Similar keys work for FlexForms:

..  code-block:: xml
    :caption: Configuration/FlexForms/example.xml

    <settings.country>
        <label>My Label</label>
        <config>
            <type>country</type>
            <labelField>officialName</labelField>
            <prioritizedCountries>
                <numIndex index="0">AT</numIndex>
                <numIndex index="1">CH</numIndex>
            </prioritizedCountries>
            <filter>
                <onlyCountries>
                    <numIndex index="0">DE</numIndex>
                    <numIndex index="1">AT</numIndex>
                    <numIndex index="2">CH</numIndex>
                    <numIndex index="1">FR</numIndex>
                    <numIndex index="3">IT</numIndex>
                    <numIndex index="4">HU</numIndex>
                    <numIndex index="5">US</numIndex>
                    <numIndex index="6">GR</numIndex>
                    <numIndex index="7">ES</numIndex>
                </onlyCountries>
                <excludeCountries>
                    <numIndex index="0">DE</numIndex>
                    <numIndex index="1">ES</numIndex>
                </excludeCountries>
            </filter>
            <sortItems>
                <label>asc</label>
            </sortItems>
            <default>HU</default>
            <required>1</required>
        </config>
    </settings.country>

Available config keys
---------------------

The TCA type :php:`country` features the following column configuration:

-   :php:`filter` (array): :php:`onlyCountries` (array), :php:`excludeCountries` (array) -
    filter/reduce specific countries
-   :php:`prioritizedCountries` (array) - items put first in the list
-   :php:`default` (string) - default value
-   :php:`labelField` (string) - display label (one of `localizedName`, `name`, `iso2`,
    `iso3`, `officialName`, `localizedOfficialName`)
-   :php:`sortItems` (string) - sort order (`asc`, `desc`)
-   :php:`required` (bool) - whether an empty selection can be made or not

Extbase usage
-------------

When using Extbase Controllers to fetch Domain Models containing
properties declared with the :php:`Country` type, these models
can be used with their usual getters, and passed along to Fluid
templates as usual.

..  code-block:: php
    :caption: Extbase Domain Model example

    use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
    use TYPO3\CMS\Core\Country\Country;

    class SomeDomainModel extends AbstractEntity
    {
        protected ?Country $country = null;

        public function setCountry(?Country $country): void
        {
            $this->country = $country;
        }

        public function getCountry(): ?Country
        {
            return $this->country;
        }
    }


..  code-block:: php
    :caption: Extbase Controller usage

    use Psr\Http\Message\ResponseInterface;
    use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
    use TYPO3\CMS\Core\Country\Country;

    class ItemController extends ActionController {
        // ...

        public function __construct(
            private readonly CountryProvider $countryProvider,
        ) {}

        public function singleAction(SomeDomainModel $model): ResponseInterface
        {
            // Do something in PHP, using the Country API
            if ($model->getCountry()->getAlpha2IsoCode() == 'DE') {
                $this->loadGermanLanguage();
            }
            $this->view->assign('model', $model);

            // You can access the `CountryProvider` API for additional country-related
            // operations, too (ideally use Dependency Injection for this):
            $this->view->assign('countries', $this->countryProvider->getAll());

            return $this->htmlResponse();
        }
    }

..  code-block:: html
    :caption: Fluid Template example

    Country: {model.country.flag}
     - <span title="{f:translate(key: model.country.localizedOfficialNameLabel)}">
         {model.country.alpha2IsoCode}
       </span>

You can use any of the :php:`getXXX()` methods available from
the `Country API <https://docs.typo3.org/permalink/t3coreapi:country-api>`_ via
the Fluid :html:`{model.country.XXX}` accessors.

If you use common Extbase CRUD (Create/Read/Update/Delete) with models using
a `Country` type, you can utilize the existing
ViewHelper :ref:`f:form.countrySelect <feature-99618-1674063182>` within
your `<f:form>` logic.

Please keep in mind that Extbase by default has no coupling (in terms of validation)
to definitions made in the `TCA` for the properties, as with other types like
file uploads or select items.

That means, if you restrict the allowed countries via `filter.onlyCountries` on
the backend (TCA) side, you also need to enforce this in the frontend.

It is recommended to use
`Extbase Validators <https://docs.typo3.org/permalink/t3coreapi:extbase-validation>`__
for this task. If you want to share frontend-based validation and TCA-based
validation non-redundantly, you could use data objects (DO/DTO) or ENUMs for returning
the list of allowed countries:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Domain/Validator/CountryValidator.php

    namespace MyExtension\Domain\Validator;

    use TYPO3\CMS\Extbase\Validation\Error;
    use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

    class ItemValidator extends AbstractValidator
    {
        /**
         * @param MyModel $value
         */
        protected function isValid(mixed $value): void
        {
            if ($value->getCountry() === null) {
                $error = new Error('Valid country (alpha2) must be set.', 4815162343);
                $this->result->forProperty('country')->addError($error);
            } else {
                $allowedCountries = ['DE', 'EN'];
                if (!in_array($value->getCountry()->getAlpha2IsoCode(), $allowedCountries)) {
                    $error = new Error('Country ' . $value->getCountry()->getAlpha2IsoCode() . ' not allowed.', 4815162344);
                    $this->result->forProperty('country')->addError($error);
                }
            }
        }
    }

..  code-block:: php
    :caption: EXT:my_extension/Classes/Controller/ItemController.php (excerpt)
    :emphasize-lines: 12-15

    namespace MyExtension\Controller;

    use TYPO3\CMS\Extbase\Annotation\Validate;
    use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
    use MyExtension\Domain\Model\Item;
    use MyExtension\Domain\Validator\ItemValidator;

    final class ItemController extends ActionController
    {
        // Excerpt ...

        #[Validate([
            'param' => 'item',
            'validator' => CountryValidator::class,
        ])]
        public function createAction(Item $item): ResponseInterface
        {
            $this->itemRepository->add($item);
            return $this->htmlResponse();
        }

        // ...
    }

A fleshed-out example for this (along with Extbase CRUD
implementation) can be found in
`EXT:tca_country_example Demo Extension <https://packagist.org/packages/garvinhicking/tca-country-example>`__.

Extbase / Fluid localization
----------------------------

The type :php:`Country` does not point to a real Extbase model, and thus has no inherent
localization or query-logic based on real records. It is just a pure
PHP data object with some getters, and a magic :php:`__toString()` method
returning a `LLL:...` translation key for the name of the country
(:php:`Country->getLocalizedNameLabel()`).

Here are some examples how to access them and provide localization:

..  code-block:: html
    :caption: EXT:my_extension/Resources/Private/Templates/Show.html

    <f:comment>Will show something like "AT" or "DE"</f:comment>
    Country ISO2:
        {item.country.alpha2IsoCode}

    <f:comment>Will show something like "CHE"</f:comment>
    Country ISO3:
        {item.country.alpha3IsoCode}

    <f:comment>Will show something a flag (UTF-8 character)</f:comment>
    Country flag:
        {item.country.flag}

    <f:comment>Will show something like "LLL:EXT:core/Resources/Private/Language/Iso/countries.xlf:AT.name"</f:comment>
    Country LLL label:
        {item.country}
    Actual localized country:
        <f:translate key="{item.country}" />

    <f:comment>Will show something like "LLL:EXT:core/Resources/Private/Language/Iso/countries.xlf:AT.official_name"</f:comment>
    Country LLL label:
        {item.country.localizedOfficialNameLabel}
    Actual localized official country name:
        <f:translate key="{item.country.localizedOfficialNameLabel}" />

    <f:comment>Will show something like "Germany" (always english)</f:comment>
        {item.country.name}

You can use the Extbase :php:`TYPO3\CMS\Extbase\Utility\LocalizationUtility`
in PHP-scope (Controllers, Domain Model)
to create a custom getter in your Domain Model to create a shorthand method:

..  code-block:: php
    :caption: EXT:my_extension/Domain/Model/Item.php
    :emphasize-lines: 22-24

    use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
    use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
    use TYPO3\CMS\Core\Country\Country;

    class SomeDomainModel extends AbstractEntity
    {
        protected ?Country $country = null;

        public function setCountry(?Country $country): void
        {
            $this->country = $country;
        }

        public function getCountry(): ?Country
        {
            return $this->country;
        }

        // Special getter to easily access `{item.localizedCountry}` in Fluid
        public function getLocalizedCountry(): string
        {
            return (string) LocalizationUtility::translate(
                (string) $this->getCountry()?->getLocalizedNameLabel()
            );
        }
    }

Extbase Repository access
-------------------------

As mentioned above, since `Country` has no database-record relations.
The single-country relation always uses the 2-letter ISO alpha2 key
(respectively custom country keys, when added via the PSR-14 event
`BeforeCountriesEvaluatedEvent`). Thus, queries need to utilize them
as string comparisons:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Domain/Repository/ItemRepository.php
    :emphasize-lines: 11

    namespace MyExtension\Domain\Repository;

    use TYPO3\CMS\Extbase\Persistence\Repository;
    use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

    class ItemRepository extends Repository
    {
        public function findByGermanMarkets(): QueryResultInterface {
            $query = $this->createQuery();
            $query->matching(
                $query->in('country', ['DE', 'AT', 'CH'])
            );
            return $query->execute();
        }
    }

The default Extbase repository magic method
:php:`$repository->findBy(['country' => 'DE'])` will work, too.

TypoScript rendering usage via `record-transformation`
------------------------------------------------------

Database records using 'country' type fields can be rendered
with the TypoScript-based record-transformation rendering
(data processor).

You can specify how a field containing a country is rendered in the output
(using the name, the flag icon, specific ISO keys) with regular fluid
logic then:

..  code-block:: typoscript
    :caption: Step 1: TypoScript utilizing `record-transformation`, defining a `Homepage.html` Fluid template

    page = PAGE
    page {
      # Just an example basic template for your site. The important section starts with `dataProcessing`!
      100 = FLUIDTEMPLATE
      100 {
        templateName = Homepage
        templateRootPaths {
          0 = EXT:myextension/Resources/Private/Templates/
        }
        dataProcessing {
          10 = database-query
          10 {
            as = mainContent
            # This table holds for example a TCA type=country definition for a field "country"
            table = tx_myextension_domain_model_mycountries
            # An extra boolean field "show_on_home_page" would indicate whether these
            # records are fetched and displayed on the home page
            where = show_on_home_page=1
            # Depending on your table storage you may need to set a proper pidInList constraint.
            #pidInList = 4711
            dataProcessing {
              # Makes all records available as `{mainContent.[0..].myRecord}` in the
              # Fluid file EXT:myextension/Resources/Private/Templates/Homepage.html
              10 = record-transformation
              10 {
                as = myRecord
              }
            }
          }
        }
      }
    }

..  code-block:: html
    :caption: Step 2: Fluid template `EXT:myextension/Resources/Private/Templates/Homepage.html`

    <f:if condition="{mainContent}">
      <f:for each="{mainContent}" as="element">
        <!-- given that your 'tx_myextension_domain_model_mycountries' has a TCA field called "storeCountry":
        Selected Country:
          <f:translate key="{element.myRecord.storeCountry.localizedOfficialNameLabel}" />
      </f:for>

      <!-- note that you can access any transformed record type object via 'element', also multiple country
           elements could be contained in 'element.myRecord'. -->
    </f:if>

..  hint::

    Instead of adding the data processor to the `PAGE` definition, you could create
    an own `country` Content Element type and set it for `tt_content.country`, and
    utilize a Content-Element specific Fluid template accessing this data, providing
    something like a "Store" Content Element associated with a country.


Impact
======

It is now possible to use a dedicated TCA type for storing a relation
to a country in a record.

Using the new TCA type, corresponding database columns are added automatically.
`Country`-annotated properties of Extbase Domain Models can be evaluated
in Extbase and via TypoScript.

.. index:: Backend, TCA, ext:core
