.. include:: /Includes.rst.txt

.. _feature-104168-1719373149:

=======================================================
Feature: #104168 - PSR-14 event for modifying countries
=======================================================

See :issue:`104168`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Core\Country\Event\BeforeCountriesEvaluatedEvent`
has been introduced to modify the list of countries provided by
:php:`\TYPO3\CMS\Core\Country\CountryProvider`.

This event allows to add, remove and alter countries from the list used by the
provider class itself and ViewHelpers like :html:`<f:form.countrySelect />`.

..  note::
    The DTO :php:`\TYPO3\CMS\Core\Country\Country`
    uses `EXT:core/Resources/Private/Language/Iso/countries.xlf` for translating
    the country names.

    If additional countries are added, add translations to `countries.xlf`
    via :ref:`locallangXMLOverride <t3coreapi:xliff-translating-custom>`.


Example
=======

An example corresponding event listener class:

..  code-block:: php

    <?php
    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Country\Country;
    use TYPO3\CMS\Core\Country\Event\BeforeCountriesEvaluatedEvent;

    final readonly class EventListener
    {
        #[AsEventListener(identifier: 'my-extension/before-countries-evaluated')]
        public function __invoke(BeforeCountriesEvaluatedEvent $event): void
        {
            $countries = $event->getCountries();
            unset($countries['BS']);
            $countries['XX'] = new Country(
                'XX',
                'XYZ',
                'Magic Kingdom',
                '987',
                '🔮',
                'Kingdom of Magic and Wonders'
            );
            $event->setCountries($countries);
        }
    }

..  code-block:: php
    :caption: EXT:my_extension/ext_localconf.php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']
        ['EXT:core/Resources/Private/Language/Iso/countries.xlf'][]
            = 'EXT:my_extension/Resources/Private/Language/countries.xlf';

..  code-block:: xml
    :caption: EXT:my_extension/Resources/Private/Language/countries.xlf

    <?xml version="1.0" encoding="utf-8" standalone="yes" ?>
    <xliff version="1.0">
        <file source-language="en" datatype="plaintext" date="2024-01-08T18:44:59Z" product-name="my_extension">
            <body>
                <trans-unit id="XX.name" resname="XX.name" approved="yes">
                    <source>Magic Kingdom</source>
                </trans-unit>
                <trans-unit id="XX.official_name" resname="XX.official_name" approved="yes">
                    <source>Kingdom of Magic and Wonders</source>
                </trans-unit>
            </body>
        </file>
    </xliff>

Impact
======

Using the PSR-14 event :php:`BeforeCountriesEvaluatedEvent` allows
modification of countries provided by :php:`CountryProvider`.

.. index:: PHP-API, ext:core
