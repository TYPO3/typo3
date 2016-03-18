==================================================
Feature: #72337 - Charset Conversion Autodetection
==================================================

Description
===========

The Charset Converter which is used to handle multi-byte charset conversions now
auto-detects which conversion strategy - either ``mbstring``, ``iconv`` or the
TYPO3-internal functionality - should be used, based on the available PHP modules
in the system.

``mbstring`` takes precedence over ``iconv`` and the TYPO3-internal functionality.


Impact
======

The options ``$TYPO3_CONF_VARS['SYS'][t3lib_cs_utils]`` and
``$TYPO3_CONF_VARS[SYS][t3lib_cs_convMethod]`` have no effect anymore and can be
removed. TYPO3 chooses the best strategy at runtime.