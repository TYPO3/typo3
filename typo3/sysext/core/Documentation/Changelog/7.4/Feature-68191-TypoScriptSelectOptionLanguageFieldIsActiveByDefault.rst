
.. include:: ../../Includes.txt

==============================================================================
Feature: #68191 - TypoScript .select option languageField is active by default
==============================================================================

See :issue:`68191`

Description
===========

The TypoScript .select option which is used for Content Objects like "CONTENT", has the property "languageField". This option allows to set the name of the database field that has the information about the sys_language_uid value in order to have only records shown that are translated or set to "-1" (show in all langauges) when showing translated pages.

Previously this functionality had to be set explicitly:

.. code-block:: typoscript

	config.sys_language_uid = 2
	page.10 = CONTENT
	page.10 {
		table = tt_content
		select.where = colPos=0
		select.languageField = sys_language_uid
		renderObj = TEXT
		renderObj.field = header
		renderObj.htmlSpecialChars = 1
	}

The languageField line is not necessary anymore, as the information is now fetched automatically from the TCA information structure:

.. code-block:: typoscript

	config.sys_language_uid = 2
	page.10 = CONTENT
	page.10 {
		table = tt_content
		select.where = colPos=0
		renderObj = TEXT
		renderObj.field = header
		renderObj.htmlSpecialChars = 1
	}

If the functionality should be disabled, this can be achieved like this:

.. code-block:: typoscript

	config.sys_language_uid = 2
	page.10 = CONTENT
	page.10 {
		table = tt_content
		select.where = colPos=0
		select.languageField = 0
		renderObj = TEXT
		renderObj.field = header
		renderObj.htmlSpecialChars = 1
	}


Impact
======

All records that have language-relevant information in the TCA "ctrl"-section displayed via .select in the frontend on translated pages are now translated by default.


.. index:: TypoScript, Frontend
