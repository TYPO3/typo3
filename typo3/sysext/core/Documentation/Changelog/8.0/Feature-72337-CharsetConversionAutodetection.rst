
.. include:: ../../Includes.txt

==================================================
Feature: #72337 - Charset Conversion Autodetection
==================================================

See :issue:`72337`

Description
===========

The Charset Converter which is used to handle multi-byte charset conversions now
always uses `mbstring`. In case `mbstring` is not present a polyfill will kick in.

Impact
======

The options `$TYPO3_CONF_VARS['SYS'][t3lib_cs_utils]` and
`$TYPO3_CONF_VARS[SYS][t3lib_cs_convMethod]` have no effect anymore and can be
removed.

.. index:: LocalConfiguration
