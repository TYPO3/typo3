.. include:: /Includes.rst.txt

.. _feature-86913-1673955088:

======================================================================================
Feature: #86913 - Automatic support for language files of languages with region suffix
======================================================================================

See :issue:`86913`

Description
===========

TYPO3's native support for label files - that is: translatable text for system labels such as
from plugins, and for texts within TYPO3 Backend - supports over 50 languages. The languages are
identified by their "language key" of the ISO 639-1 standard also allow to use a region-specific
language. This happens mostly in countries/regions that have a variation of the language, such
as "en-US" for American English, or "de-CH" for the German Language in Switzerland.

In order to support these region-specific language keys, which are composed of ISO 639-1
and ISO 3166-1 and separated with `-`, TYPO3 integrators had to manually
configure the additional language to translate region-specific terms.

Common examples are "Behavior" (American English) vs. "Behaviour" (British English), or
"Offerte" (Swiss German) vs. "Angebot" (German), where all labels except a few terms should stay the same.


Impact
======

TYPO3 now allows integrators to use a custom label file with the locale prefix "de_CH.locallang.xlf"
in an extension next to "de.locallang.xlf" and "locallang.xlf" (default language english).

When integrators then use "de-CH" within their site configuration, TYPO3 first checks if
a term is available in "de-CH", and then automatically falls back to the non-region-specific "de"
label file "de.locallang.xlf" without any further configuration to TYPO3.

Previously such region-specific locales had to be configured via:

.. code-block:: php

     $GLOBALS['TYPO3_CONF_VARS']['SYS']['localization']['locales']['user'] = [
          'de-CH' => 'German (Switzerland)',
     ];

The same fallback functionality also works when overriding labels via TypoScript:

.. code-block:: typoscript

     plugin.tx_myextension._LOCAL_LANG.de = Angebot
     plugin.tx_myextension._LOCAL_LANG.de-CH = Offerte

.. index:: LocalConfiguration, TypoScript, ext:core
