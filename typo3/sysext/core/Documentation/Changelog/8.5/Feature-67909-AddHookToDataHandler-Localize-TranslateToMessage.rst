.. include:: ../../Includes.txt

=========================================================================
Feature: #67909 - Add hook to DataHandler - localize - translateToMessage
=========================================================================

See :issue:`67909`

Description
===========

By introducing a new hook to the `localize()` function (the `translateToMessage` part in particular) you are now able to
use external translation services and speed-up translation of the content and even add a custom
transliteration function that would handle various content transformations.


Impact
======

A new hook is available at:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processTranslateToClass']

Implement it for example as follows:

.. code-block:: php

	class YourHookClass {
		public function processTranslateTo_copyAction(&$content, $lang, $dataHandler, $fieldName) {
			// Do something with content (translate, transliterate etc)
		}
	}

Note
======

Since Version 8.7.16 hooks now get a fourth parameter for the currently processed fieldname.

.. index:: PHP-API, Backend
