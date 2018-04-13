.. include:: ../../Includes.txt

=========================================
Feature: #84581 - Introduce Site Handling
=========================================

See :issue:`84581`

Description
===========

Site Handling has been added to TYPO3.

Its goal is to make managing multiple sites easier to understand and faster to do. Sites bring a variety of new
concepts to TYPO3 which we will explain below.

Take your time and read through the entire document since some concepts rely on each other.


typo3conf/sites folder
----------------------

New sites will live in the folder `typo3conf/sites/`. In the first iteration this folder will contain a file called
`config.yaml` which holds all configuration for a given site.

In the future this folder can (and should) be used for more files like Fluid templates, and Backend layouts.


config.yaml
-----------

.. code::

    site:
      # the rootPage Id (see below)
      rootPageId: 12
      # my base domain to run this site on. It either accepts a fully qualified URL or "/" to react to any domain name
      base: 'https://www.example.com/'
      # The language array
      languages:
        -
          # the TYPO3 sys_language_uid as you know it since... ever
          languageId: '0'
          # The internal name for this language. Unused for now, but in the future this will affect display in the backend
          title: English
          # optional navigation title which is used in HMENU.special = language
          navigationTitle: ''
          # Language base. Accepts either a fully qualified URL or a path segment like "/en/".
          base: /
          # sets the locale during frontend rendering
          locale: en_US.UTF-8
          # ???
          iso-639-1: en
          # FE href language
          hreflang: en-US
          # FE text direction
          direction: ltr
          # Language Identifier to use in localLang XLIFF files
          typo3Language: default
          # Flag Identifier
          flag: gb
        -
          languageId: '1'
          title: 'danish'
          navigationTitle: Dansk
          base: /da/
          locale: dk_DK.UTF-8
          iso-639-1: da
          hreflang: dk-DK
          direction: ltr
          typo3Language: default
          flag: dk
          fallbackType: strict
        -
          languageId: '2'
          title: Deutsch
          navigationTitle: ''
          base: 'https://www.beispiel.de'
          locale: de_DE.UTF-8
          iso-639-1: de
          hreflang: de-DE
          direction: ltr
          typo3Language: de
          flag: de
          # Enable content fallback
          fallbackType: fallback
          # Content fallback mode (order is important)
          fallbacks: '2,1,0'
      # Error Handling Array (order is important here)
      # Error Handlers will check the given status code, but the special value "0" will react to any error not configured
      # elsewhere in this configuration.
      errorHandling:
        -
          # HTTP Status Code to react to
          errorCode: '404'
          # The used ErrorHandler. In this case, it's "Display content from Page". See examples below for available options.
          errorHandler: Page
          # href to the content source to display (accepts both fully qualified URLs as well as TYPO3 internal link syntax
          errorContentSource: 't3://page?uid=8'
        -
          errorCode: '401'
          errorHandler: Fluid
          # Path to the Template File to show
          errorFluidTemplate: 'EXT:my_extension/Resources/Private/Templates/ErrorPages/401.html'
          # Optional Templates root path
          errorFluidTemplatesRootPath: 'EXT:my_extension/Resources/Private/Templates/ErrorPages'
          # Optional Layouts root path
          errorFluidLayoutsRootPath: 'EXT:my_extension/Resources/Private/Layouts/ErrorPages'
          # Optional Partials root path
          errorFluidPartialsRootPath: 'EXT:my_extension/Resources/Private/Partials/ErrorPages'
        -
          errorCode: '0'
          errorHandler: PHP
          # Fully qualified class name to a class that implements PageErrorHandlerInterface
          errorPhpClassFQCN: Vendor\ExtensionName\ErrorHandlers\GenericErrorhandler


All settings can also be edited via the backend module `Site Management > Configuration`.

Keep in mind that due to the nature of the module, comments or additional values in your :file:`config.yaml` file
**will** get deleted on saving.


site identifier
---------------

The site identifier is the name of the folder within `typo3conf/sites/` that will hold your configuration file(s). When
choosing an identifier make sure to stick to ASCII but you may also use `-`, `_` and `.` for convenience.


rootPageId
----------

Root pages are identified by one of these two properties:

* they are direct descendants of PID 0 (the root root page of TYPO3)
* they have the "Use as Root Page" property in `pages` set to true.


Impact
======

The following TypoScript settings will be set based on `config.yaml` rather than needing to have them in your TypoScript
template:

* config.language
* config.htmlTag_dir
* config.htmlTag_langKey
* config.sys_language_uid
* config.sys_language_mode
* config.sys_language_isocode
* config.sys_language_isocode_default


Links to pages within a site can now be generated via **any** access of TYPO3, so in both BE and FE as well as CLI mode.

.. index:: Backend, Frontend, TypoScript
