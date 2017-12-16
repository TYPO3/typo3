
.. include:: ../../Includes.txt

========================================================
Feature: #66370 - Add flexible Preview URL configuration
========================================================

See :issue:`66370`

Description
===========

It is now possible to configure the preview link generated for the save+view button in Backend.

This allows to have different preview URLs depending on the record type.

Common usecase is to have previews for blog or news records, but this feature now allows you to
define a different preview page for content elements as well, which might be handy if those are stored
in a sysfolder.


Impact
======

New page TSconfig is introduced. The options are:

.. code-block:: typoscript

	TCEMAIN.preview {
		<table name> {
			previewPageId = 123
			useDefaultLanguageRecord = 0
			fieldToParameterMap {
				uid = tx_myext_pi1[showUid]
			}
			additionalGetParameters {
				tx_myext_pi1.special = HELLO # results in tx_myext_pi1[special]
			}
		}
	}

The `previewPageId` is the uid of the page to use for preview. If this setting is omitted the current page will be used.
If the current page is not a normal page, the root page will be chosen.

The `useDefaultLanguageRecord` defaults to `1` and ensures that translated records will use the uid of the default record
for the preview link. You may disable this, if your extension can deal with the uid of translated records.

The `fieldToParameterMap` is a mapping which allows you to select fields of the record to be included as GET-parameters in
the preview link. The key specifies the field name and the value specifies the GET-parameter name.

Finally `additionalGetParameters` allow you to add arbitrary GET-parameters and even override others.

Predefined GET-parameters
^^^^^^^^^^^^^^^^^^^^^^^^^

The Core automatically sets the `no_cache` and the `L` parameter. The language matches the language of the current record.
You may override each parameter by using the `additionalGetParameters` configuration option.
