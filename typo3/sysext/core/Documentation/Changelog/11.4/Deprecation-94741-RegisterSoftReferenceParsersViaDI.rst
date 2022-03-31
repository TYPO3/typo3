.. include:: /Includes.rst.txt

===========================================================
Deprecation: #94741 - Register SoftReference parsers via DI
===========================================================

See :issue:`94741`

Description
===========

The former way of registering soft reference parsers in the global array
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['softRefParser']`
has been marked as deprecated.


Impact
======

Registering soft reference parsers in the global array will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All installations, that register user-defined soft reference parsers in the
global array
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['softRefParser']`.


Migration
=========

Use the new way of registering soft reference parsers by dependency injection
in the corresponding `Configuration/Services.(yaml|php)` file of your extension.

Before:

.. code-block:: php

  $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['softRefParser']['your_key'] = \VENDOR\Extension\SoftReference\YourSoftReferenceParser::class;

After:

.. code-block:: yaml

    VENDOR\Extension\SoftReference\YourSoftReferenceParser:
      tags:
        - name: softreference.parser
          parserKey: your_key

.. note::

   If a parser is registered in both ways with the same key, the registration
   in the global array takes precedence to ensure backwards-compatibility.

   To ensure compatibility with TYPO3 v10-v12, it is recommended to register
   both places at the same time.

Related
=======

*  :doc:`RegisterSoftReferenceParsersViaDI (Feature) <Feature-94741-RegisterSoftReferenceParsersViaDI>`
*  :doc:`SoftReferenceIndex (Deprecation) <Deprecation-94687-SoftReferenceIndex>`

.. index:: PHP-API, NotScanned, ext:core
