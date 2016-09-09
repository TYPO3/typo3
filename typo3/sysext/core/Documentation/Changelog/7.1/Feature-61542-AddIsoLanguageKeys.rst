
.. include:: ../../Includes.txt

===============================================================
Feature: #61542 - Add two-letter ISO 639-1 keys to sys_language
===============================================================

See :issue:`61542`

Description
===========

The handling of the languages is done by the sys_language database table, which is usually referenced via the common
sys_language_uid. The commonly referenced ISO 639-1 two-letter-code is only in use when static_info_tables is installed,
which brings all ISO 639-1 letter-codes in a separate table. The CMS Core uses a hard-coded dependency on the extension
to retrieve the ISO codes where needed, however, already ships an empty and invisible field "static_lang_isocode" which is
already supplied by the CMS Core.

As a first step to identify languages by their proper ISO 639-1 two-letter code a new DB field for sys_language called
"language_isocode" is introduced, which is used in all places of the TYPO3 CMS Core.

Additionally the new TypoScript option `config.sys_language_isocode` can be used to set the existing
`$TSFE->sys_language_isocode` variable via TypoScript. Previously this was done via static_info_tables.

The ISO code is also used for the language attribute of the HTML tag. Therefore the setting `config.htmlTag_langKey`
is not needed anymore if it is the same as the ISO code.

Impact
======

Frontend:
The value `$TSFE->sys_language_isocode` is now filled at any time. It can be set via TypoScript, or is automatically
set if the `config.sys_language_uid` parameter is set > 0 from the language_isocode DB field.

.. code-block:: typoscript

	# danish by default
	config.sys_language_uid = 0
	config.sys_language_isocode_default = da

	[globalVar = GP:L = 1]
		# isocode is filled by the respective DB value from sys_language (uid 1)
		config.sys_language_uid = 1

		# you can override this of course
		config.sys_language_isocode = fr
	[GLOBAL]

The new field can be used in any TypoScript variable like

.. code-block:: typoscript

	page.10 = TEXT
	page.10.data = TSFE:sys_language_isocode
	page.10.wrap = <div class="main" data-language="|">

Backend:

All ISO code usages based on sys_language in the Backend (FormEngine, Translation Tools) is now done via the new field
but still uses static_info_tables as fallback if already in use.

Each sys_language record is now editable with the new DB field to select the respective ISO 639-1 code.

Migration
=========

The upgrade wizard makes sure that the new DB field is filled properly so no deprecation warnings are written
if static_info_tables in conjunction with the DB field "static_lang_isocode" was used before. If this field is
used in a 3rd party extension, the extension developers and site integrators need to make sure it is switched
to the new DB field sys_language.language_isocode.

If a site uses multiple languages without static_info_tables each sys_language record should be modified to select
the proper ISO 639-1 code for the languages.
