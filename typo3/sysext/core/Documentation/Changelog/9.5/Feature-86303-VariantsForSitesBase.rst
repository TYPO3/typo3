.. include:: ../../Includes.txt

==========================================
Feature: #86303 - Variants for site's base
==========================================

See :issue:`86303`
See :issue:`87831`

Description
===========

The site configuration allows to specify variants of the site's base.
Take the following example: The base of a site is set to `https://www.domain.tld` but the staging environment uses
`https://staging.domain.tld` and the local development uses `https://www.domain.local`.

The expression language feature is used to define which variant is taken into account.

Update since TYPO3 9.5.5:

This is also possible for site languages (however only via editing the respective `config.yaml` file
manually, not via the TYPO3 Backend yet). See example yaml file below.


Impact
======

The base of a site can be changed depending on a condition. Typical examples are:

- :typoscript:`applicationContext == "Production"`: Check the application context
- :typoscript:`getenv("mycontext") == "production`: Check a custom environment variable


This is an example where the default site has base variants, but also a translation (in this case German)
has a custom domain or sub-domain instead of a first part of the path segment of the URL.

.. code-block:: yaml

    rootPageId: 1
    base: 'https://www.example.com/'
    baseVariants:
      -
        base: 'https://example.local/'
        condition: 'applicationContext == "Development"'
      -
        base: 'https://staging.example.com/'
        condition: 'applicationContext == "Production/Sydney"'
      -
        base: 'https://testing.example.com/'
        condition: 'applicationContext == "Testing/Paris"'
    languages:
      -
        title: 'Global'
        enabled: true
        languageId: '0'
        base: /
        typo3Language: default
        locale: en_UK.UTF-8
        iso-639-1: en
        navigationTitle: English
        hreflang: gb-en
        direction: ''
        flag: gb
      -
        title: 'DE'
        enabled: true
        languageId: '1'
        base: https://example.de/'
        baseVariants:
          -
            base: 'https://de.example.local/'
            condition: 'applicationContext == "Development"'
          -
            base: 'https://staging.example.de/'
            condition: 'applicationContext == "Production/Sydney"'
          -
            base: 'https://testing.example.de/'
            condition: 'applicationContext == "Testing/Paris"'
        typo3Language: de
        locale: de_DE.UTF-8
        iso-639-1: de
        navigationTitle: Deutsch
        hreflang: de-de
        direction: ''
        fallbackType: strict
        flag: de

.. index:: Backend, Frontend, TypoScript, ext:core
