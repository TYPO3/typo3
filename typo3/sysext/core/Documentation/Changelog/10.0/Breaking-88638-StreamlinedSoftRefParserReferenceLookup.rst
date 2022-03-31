.. include:: /Includes.rst.txt

=============================================================
Breaking: #88638 - Streamlined SoftRefParser reference lookup
=============================================================

See :issue:`88638`

Description
===========

The Soft Reference Parser is a registry to allow to find parsers (PHP Objects),
for a given Parser Type (images, internal links, email links) to keep track of
referenced records within arbitrary data (e.g. RTE text-fields).

Parsers can be added or overridden via the hook registry
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['softRefParser'][$parserType]`.

Previously, the API method for fetching the proper parsers
:php:`TYPO3\CMS\Backend\Utility\BackendUtility::softRefParserObj()` kept a runtime cache of created objects
per type within a global PHP array (:php:`T3_VAR`). This allowed to create objects
only once, even if there are multiple necessary parts required.

TYPO3's Core SoftRefParser does not keep any state, but the class now has a
:php:`SingletonInterface`, which means that the object is now a re-used object
as before.


Impact
======

Calling the SoftRefParser factory method does not keep state of the parser
objects via :php:`$GLOBALS['T3_VAR']['softRefParser']` anymore.

Instead, :php:`SingletonInterface` is recommended for re-using SoftRefParser objects
if they need to keep state.


Affected Installations
======================

TYPO3 installations with extensions that use the API with custom parsers,
or the global variable directly.


Migration
=========

Replace the global variable access via the API call to :php:`TYPO3\CMS\Backend\Utility\BackendUtility`, if this
is applicable.

If a custom parser is in use, it is recommended to evaluate whether it contains
re-usable data and switch to :php:`SingletonInterface` instead.

.. index:: Backend, FullyScanned
